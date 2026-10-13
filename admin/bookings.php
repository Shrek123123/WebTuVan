<?php
session_start();
require_once __DIR__ . '/../config.php';

if (empty($_SESSION['admin_id']) || empty($_SESSION['usernameadmin'])) {
    header('Location: adminlogin.php');
    exit();
}

$adminId = (int) $_SESSION['admin_id'];
$adminStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' AND status = 'active' LIMIT 1");
if (!$adminStmt) {
    throw new RuntimeException('Could not verify administrator: ' . $conn->error);
}
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$isAdmin = (bool) $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();
if (!$isAdmin) {
    session_destroy();
    header('Location: adminlogin.php');
    exit();
}

if (empty($_SESSION['admin_bookings_csrf'])) {
    $_SESSION['admin_bookings_csrf'] = bin2hex(random_bytes(32));
}
$flash = $_SESSION['admin_bookings_flash'] ?? null;
unset($_SESSION['admin_bookings_flash']);
$transitions = [
    'pending' => ['accepted', 'rejected'],
    'accepted' => ['in_progress', 'cancelled'],
    'in_progress' => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
    'rejected' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $newStatus = (string) ($_POST['status'] ?? '');
    if (!hash_equals((string) $_SESSION['admin_bookings_csrf'], $csrfToken) || $bookingId === false) {
        $_SESSION['admin_bookings_flash'] = ['type' => 'error', 'message' => 'Yêu cầu cập nhật không hợp lệ.'];
    } else {
        try {
            $conn->begin_transaction();
            $bookingId = (int) $bookingId;
            $currentStmt = $conn->prepare('SELECT status, mediator_id FROM bookings WHERE id = ? FOR UPDATE');
            if (!$currentStmt) {
                throw new RuntimeException('Could not prepare booking status lookup: ' . $conn->error);
            }
            $currentStmt->bind_param('i', $bookingId);
            $currentStmt->execute();
            $current = $currentStmt->get_result()->fetch_assoc();
            $currentStmt->close();

            if (!$current || !in_array($newStatus, $transitions[$current['status']] ?? [], true)) {
                throw new DomainException('Trạng thái này không được phép hoặc đã được cập nhật.');
            }

            $updateStmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ? AND status = ?');
            if (!$updateStmt) {
                throw new RuntimeException('Could not prepare booking status update: ' . $conn->error);
            }
            $updateStmt->bind_param('sis', $newStatus, $bookingId, $current['status']);
            $updateStmt->execute();
            $updated = $updateStmt->affected_rows === 1;
            $updateStmt->close();
            if (!$updated) {
                throw new DomainException('Trạng thái đã thay đổi; hãy tải lại danh sách.');
            }

            if ($newStatus === 'completed') {
                $mediatorId = (int) $current['mediator_id'];
                $caseStmt = $conn->prepare('UPDATE mediators SET total_cases = total_cases + 1 WHERE id = ?');
                if (!$caseStmt) {
                    throw new RuntimeException('Could not prepare mediator case count update: ' . $conn->error);
                }
                $caseStmt->bind_param('i', $mediatorId);
                $caseStmt->execute();
                if ($caseStmt->affected_rows !== 1) {
                    throw new RuntimeException('Could not update completed case count: ' . $conn->error);
                }
                $caseStmt->close();
            }
            $conn->commit();
            $_SESSION['admin_bookings_flash'] = ['type' => 'success', 'message' => 'Đã cập nhật trạng thái yêu cầu tư vấn.'];
        } catch (DomainException $exception) {
            $conn->rollback();
            $_SESSION['admin_bookings_flash'] = ['type' => 'error', 'message' => $exception->getMessage()];
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log('Admin booking status update failed: ' . $exception->getMessage());
            $_SESSION['admin_bookings_flash'] = ['type' => 'error', 'message' => 'Chưa thể cập nhật trạng thái. Vui lòng thử lại.'];
        }
    }
    header('Location: bookings.php');
    exit();
}

$bookings = [];
$result = $conn->query(
    'SELECT b.id, b.title, b.conflict_type, b.consultation_mode, b.incident_location,
            b.urgency_level, b.scheduled_time, b.duration_hours, b.total_price, b.status, b.created_at,
            u.full_name AS customer_name, u.phone AS customer_phone, u.email AS customer_email,
            m.id AS mediator_id, m.full_name AS mediator_name, t.payment_method, t.payment_status
     FROM bookings b
     JOIN users u ON u.id = b.customer_id
     JOIN mediators m ON m.id = b.mediator_id
     LEFT JOIN transactions t ON t.booking_id = b.id
     ORDER BY b.created_at DESC, b.id DESC'
);
if (!$result) {
    throw new RuntimeException('Could not load booking requests: ' . $conn->error);
}
$bookings = $result->fetch_all(MYSQLI_ASSOC);

function admin_booking_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý lịch tư vấn — Mediato</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f6f2; color: #203b38; font: 15px/1.55 Arial, sans-serif; }
        .admin-nav { display: flex; gap: 18px; flex-wrap: wrap; padding: 14px max(16px, calc((100% - 1200px) / 2)); background: #173c37; }
        .admin-nav a { color: white; text-decoration: none; font-weight: 700; }
        main { width: min(1200px, calc(100% - 32px)); margin: 35px auto 70px; }
        h1, h2 { font-family: Georgia, serif; font-weight: 500; }
        .intro { color: #647571; }
        .flash { padding: 13px 15px; border: 1px solid #dce4df; background: white; }
        .flash.error { border-color: #d9a5a0; color: #8d3029; }
        .booking-card { margin-top: 14px; padding: 21px; border: 1px solid #dce4df; background: #fffefa; }
        .booking-card h2 { margin: 0; font-size: 1.35rem; }
        .meta { display: flex; flex-wrap: wrap; gap: 8px 18px; color: #647571; }
        .description { white-space: pre-line; }
        form { display: flex; flex-wrap: wrap; align-items: center; gap: 9px; margin-top: 16px; }
        select, button { min-height: 40px; padding: 8px 11px; border: 1px solid #cdd9d3; border-radius: 3px; font: inherit; }
        button { border: 0; background: #176b62; color: white; font-weight: 700; cursor: pointer; }
        .empty { margin-top: 20px; padding: 24px; background: white; text-align: center; }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body>
    <nav class="admin-nav" aria-label="Điều hướng quản trị">
        <a href="admindashboard.php">Bảng điều khiển</a>
        <a href="mediators.php">Hòa giải viên</a>
        <a href="bookings.php">Lịch tư vấn</a>
        <a href="user_table.php">Khách hàng và quản trị viên</a>
    </nav>
    <main>
        <h1>Yêu cầu tư vấn</h1>
        <p class="intro">Xác nhận và cập nhật tiến độ các lịch đặt. Chỉ ghi nhận ca tư vấn khi đã hoàn thành.</p>
        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <p class="flash <?= ($flash['type'] ?? '') === 'error' ? 'error' : '' ?>" role="status"><?= admin_booking_escape($flash['message']) ?></p>
        <?php endif; ?>
        <?php if (!$bookings): ?>
            <p class="empty">Chưa có yêu cầu tư vấn nào.</p>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <article class="booking-card">
                    <h2>#<?= (int) $booking['id'] ?> · <?= admin_booking_escape($booking['title']) ?></h2>
                    <p class="meta">
                        <span>Khách hàng: <?= admin_booking_escape($booking['customer_name']) ?> (<?= admin_booking_escape($booking['customer_phone']) ?>, <?= admin_booking_escape($booking['customer_email']) ?>)</span>
                        <span>Hòa giải viên: <?= admin_booking_escape($booking['mediator_name']) ?></span>
                        <span><?= $booking['consultation_mode'] === 'online' ? 'Trực tuyến' : 'Tại hiện trường' ?></span>
                        <span>Địa điểm: <?= admin_booking_escape($booking['incident_location']) ?></span>
                        <span>Phí: <?= number_format((float) $booking['total_price'], 0, ',', '.') ?> đ</span>
                        <span>Thanh toán: <?= admin_booking_escape($booking['payment_method'] ?? 'CASH') ?> / <?= admin_booking_escape($booking['payment_status'] ?? 'UNPAID') ?></span>
                        <span>Trạng thái: <?= admin_booking_escape($booking['status']) ?></span>
                        <span>Tạo lúc: <?= admin_booking_escape($booking['created_at']) ?></span>
                    </p>
                    <?php if (in_array($booking['status'], ['pending', 'accepted', 'in_progress'], true)): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= admin_booking_escape($_SESSION['admin_bookings_csrf']) ?>">
                            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                            <label for="status-<?= (int) $booking['id'] ?>">Cập nhật trạng thái</label>
                            <select id="status-<?= (int) $booking['id'] ?>" name="status" required>
                                <?php foreach ($transitions[$booking['status']] as $nextStatus): ?>
                                    <option value="<?= admin_booking_escape($nextStatus) ?>"><?= admin_booking_escape($nextStatus) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Lưu</button>
                        </form>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
