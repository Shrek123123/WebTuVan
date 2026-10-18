<?php
session_start();
require_once __DIR__ . '/config.php';

function booking_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function booking_redirect_to_mediator(int $mediatorId): void
{
    header('Location: jobdetail.php?id=' . $mediatorId . '#dat-lich');
    exit();
}

function booking_set_flash(string $type, string $message): void
{
    $_SESSION['booking_flash'] = ['type' => $type, 'message' => $message];
}

if (empty($_SESSION['booking_csrf'])) {
    $_SESSION['booking_csrf'] = bin2hex(random_bytes(32));
}

$customerId = (int) ($_SESSION['customer_id'] ?? 0);
if ($customerId < 1 || !in_array($_SESSION['role'] ?? '', ['customer', 'jobseeker'], true)) {
    $redirect = $_SERVER['REQUEST_URI'] ?? 'bookings.php';
    header('Location: jobseekerlogin.php?redirect=' . rawurlencode($redirect));
    exit();
}

$customerStmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ? AND role = 'customer' AND status = 'active' LIMIT 1");
if (!$customerStmt) {
    throw new RuntimeException('Could not prepare customer session check: ' . $conn->error);
}
$customerStmt->bind_param('i', $customerId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc();
$customerStmt->close();
if (!$customer) {
    unset($_SESSION['customer_id'], $_SESSION['jobseeker_id'], $_SESSION['role']);
    header('Location: jobseekerlogin.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['booking_csrf'], $csrfToken)) {
        booking_set_flash('error', 'Phiên đặt lịch không hợp lệ. Vui lòng thử lại.');
        $postedMediatorId = filter_var($_POST['mediator_id'] ?? null, FILTER_VALIDATE_INT);
        if ($postedMediatorId && $postedMediatorId > 0) {
            booking_redirect_to_mediator((int) $postedMediatorId);
        }
        header('Location: bookings.php');
        exit();
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'create') {
        $mediatorId = filter_var($_POST['mediator_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $conflictType = (string) ($_POST['conflict_type'] ?? '');
        $allowedConflictTypes = [
            'TRO_NHA_O', 'VA_CHAM_GIAO_THONG', 'GIAO_DICH_MUA_BAN', 'VAY_MUON_TAI_CHINH',
            'KHONG_GIAN_SONG', 'MOC_TINH_CAM_GIA_DINH', 'HOC_TAP_VIEC_NHOM', 'KHAC',
        ];
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $mode = (string) ($_POST['consultation_mode'] ?? '');
        $location = trim((string) ($_POST['incident_location'] ?? ''));
        $urgency = (string) ($_POST['urgency_level'] ?? '');
        $durationValue = $_POST['duration_hours'] ?? null;
        $duration = is_scalar($durationValue) && is_numeric($durationValue) ? (float) $durationValue : 0.0;
        $scheduledAt = null;
        $scheduleError = false;

        if ($urgency === 'SCHEDULED') {
            $scheduledRaw = (string) ($_POST['scheduled_time'] ?? '');
            $scheduledDate = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $scheduledRaw);
            $dateErrors = DateTimeImmutable::getLastErrors();
            if (!$scheduledDate || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $scheduledDate->getTimestamp() <= time()) {
                $scheduleError = true;
            } else {
                $scheduledAt = $scheduledDate->format('Y-m-d H:i:s');
            }
        }

        if ($mediatorId === false || !in_array($conflictType, $allowedConflictTypes, true)
            || !in_array($mode, ['online', 'onsite'], true)
            || !in_array($urgency, ['IMMEDIATE', 'SCHEDULED'], true)
            || $title === '' || strlen($title) > 255 || strlen($description) > 10000
            || $duration < 0.5 || $duration > 8 || fmod($duration, 0.5) !== 0.0
            || ($mode === 'onsite' && ($location === '' || strlen($location) > 255))
            || $scheduleError) {
            booking_set_flash('error', 'Thông tin đặt lịch chưa hợp lệ. Vui lòng kiểm tra lại các trường bắt buộc.');
            booking_redirect_to_mediator((int) ($mediatorId ?: 0));
        }

        $mediatorStmt = $conn->prepare('SELECT id, hourly_rate FROM mediators WHERE id = ? LIMIT 1');
        if (!$mediatorStmt) {
            throw new RuntimeException('Could not prepare mediator booking lookup: ' . $conn->error);
        }
        $mediatorId = (int) $mediatorId;
        $mediatorStmt->bind_param('i', $mediatorId);
        $mediatorStmt->execute();
        $mediator = $mediatorStmt->get_result()->fetch_assoc();
        $mediatorStmt->close();
        if (!$mediator) {
            booking_set_flash('error', 'Không tìm thấy hồ sơ hòa giải viên này.');
            header('Location: searchpage.php');
            exit();
        }

        $location = $mode === 'online' ? 'Tư vấn trực tuyến' : $location;
        $hourlyRate = max(0.0, (float) $mediator['hourly_rate']);
        $totalPrice = round($hourlyRate * $duration, 2);
        if ($totalPrice > 99999999.99) {
            booking_set_flash('error', 'Chi phí dự kiến vượt mức cho phép. Vui lòng liên hệ hỗ trợ.');
            booking_redirect_to_mediator($mediatorId);
        }

        try {
            $conn->begin_transaction();
            $insertBooking = $conn->prepare(
                'INSERT INTO bookings
                    (customer_id, mediator_id, conflict_type, title, description, consultation_mode,
                     incident_location, urgency_level, scheduled_time, duration_hours, total_price)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            if (!$insertBooking) {
                throw new RuntimeException('Could not prepare booking creation: ' . $conn->error);
            }
            $insertBooking->bind_param(
                'iisssssssdd',
                $customerId,
                $mediatorId,
                $conflictType,
                $title,
                $description,
                $mode,
                $location,
                $urgency,
                $scheduledAt,
                $duration,
                $totalPrice
            );
            $insertBooking->execute();
            $bookingId = (int) $conn->insert_id;
            $insertBooking->close();

            $insertTransaction = $conn->prepare("INSERT INTO transactions (booking_id, amount, payment_method, payment_status) VALUES (?, ?, 'CASH', 'UNPAID')");
            if (!$insertTransaction) {
                throw new RuntimeException('Could not prepare booking transaction: ' . $conn->error);
            }
            $insertTransaction->bind_param('id', $bookingId, $totalPrice);
            $insertTransaction->execute();
            $insertTransaction->close();
            $conn->commit();
            header('Location: bookings.php?created=1');
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log('Booking creation failed: ' . $exception->getMessage());
            booking_set_flash('error', 'Chưa thể tạo yêu cầu đặt lịch. Dữ liệu của bạn chưa bị tính phí; vui lòng thử lại.');
            booking_redirect_to_mediator($mediatorId);
        }
    } elseif ($action === 'cancel') {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($bookingId === false) {
            booking_set_flash('error', 'Yêu cầu hủy lịch không hợp lệ.');
        } else {
            $cancelStmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
            if (!$cancelStmt) {
                throw new RuntimeException('Could not prepare booking cancellation: ' . $conn->error);
            }
            $bookingId = (int) $bookingId;
            $cancelStmt->bind_param('ii', $bookingId, $customerId);
            $cancelStmt->execute();
            $changed = $cancelStmt->affected_rows === 1;
            $cancelStmt->close();
            booking_set_flash($changed ? 'success' : 'error', $changed ? 'Đã hủy yêu cầu đặt lịch.' : 'Chỉ có thể hủy yêu cầu đang chờ xác nhận.');
        }
        header('Location: bookings.php');
        exit();
    } elseif ($action === 'review') {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($bookingId === false || $rating === false || strlen($comment) > 5000) {
            booking_set_flash('error', 'Đánh giá chưa hợp lệ.');
        } else {
            $bookingId = (int) $bookingId;
            $reviewBooking = $conn->prepare("SELECT mediator_id FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed' LIMIT 1");
            if (!$reviewBooking) {
                throw new RuntimeException('Could not prepare booking review lookup: ' . $conn->error);
            }
            $reviewBooking->bind_param('ii', $bookingId, $customerId);
            $reviewBooking->execute();
            $reviewTarget = $reviewBooking->get_result()->fetch_assoc();
            $reviewBooking->close();
            if (!$reviewTarget) {
                booking_set_flash('error', 'Chỉ có thể đánh giá ca tư vấn đã hoàn thành của bạn.');
            } else {
                try {
                    $conn->begin_transaction();
                    $mediatorId = (int) $reviewTarget['mediator_id'];
                    $reviewStmt = $conn->prepare('INSERT INTO reviews (booking_id, customer_id, mediator_id, rating, comment) VALUES (?, ?, ?, ?, ?)');
                    if (!$reviewStmt) {
                        throw new RuntimeException('Could not prepare review creation: ' . $conn->error);
                    }
                    $reviewStmt->bind_param('iiiis', $bookingId, $customerId, $mediatorId, $rating, $comment);
                    $reviewStmt->execute();
                    $reviewStmt->close();
                    $ratingStmt = $conn->prepare(
                        'UPDATE mediators SET rating = (SELECT AVG(rating) FROM reviews WHERE mediator_id = ?)
                         WHERE id = ?'
                    );
                    if (!$ratingStmt) {
                        throw new RuntimeException('Could not prepare mediator rating update: ' . $conn->error);
                    }
                    $ratingStmt->bind_param('ii', $mediatorId, $mediatorId);
                    $ratingStmt->execute();
                    $ratingStmt->close();
                    $conn->commit();
                    booking_set_flash('success', 'Cảm ơn bạn đã gửi đánh giá.');
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    if ((int) $exception->getCode() === 1062) {
                        booking_set_flash('error', 'Bạn đã đánh giá ca tư vấn này.');
                    } else {
                        error_log('Booking review failed: ' . $exception->getMessage());
                        booking_set_flash('error', 'Chưa thể lưu đánh giá. Vui lòng thử lại.');
                    }
                } catch (Throwable $exception) {
                    $conn->rollback();
                    error_log('Booking review failed: ' . $exception->getMessage());
                    booking_set_flash('error', 'Chưa thể lưu đánh giá. Vui lòng thử lại.');
                }
            }
        }
        header('Location: bookings.php');
        exit();
    } else {
        booking_set_flash('error', 'Thao tác không hợp lệ.');
        header('Location: bookings.php');
        exit();
    }
}

$flash = $_SESSION['booking_flash'] ?? null;
unset($_SESSION['booking_flash']);
$bookings = [];
$listStmt = $conn->prepare(
    'SELECT b.id, b.mediator_id, b.conflict_type, b.title, b.description, b.consultation_mode,
            b.incident_location, b.urgency_level, b.scheduled_time, b.duration_hours, b.total_price,
            b.status, b.created_at, m.full_name AS mediator_name, m.avatar_url AS mediator_avatar,
            t.payment_method, t.payment_status,
            EXISTS (SELECT 1 FROM reviews r WHERE r.booking_id = b.id) AS has_review
     FROM bookings b
     JOIN mediators m ON m.id = b.mediator_id
     LEFT JOIN transactions t ON t.booking_id = b.id
     WHERE b.customer_id = ?
     ORDER BY b.created_at DESC, b.id DESC'
);
if (!$listStmt) {
    throw new RuntimeException('Could not prepare customer booking list: ' . $conn->error);
}
$listStmt->bind_param('i', $customerId);
$listStmt->execute();
$listResult = $listStmt->get_result();
while ($booking = $listResult->fetch_assoc()) {
    $bookings[] = $booking;
}
$listStmt->close();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lịch tư vấn của tôi — Mediato</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <style>
        :root { color-scheme: light; --ink: #203b38; --teal: #176b62; --paper: #f4f6f2; --line: #dce4df; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--ink); font: 16px/1.55 Arial, sans-serif; }
        main { width: min(980px, calc(100% - 32px)); margin: 44px auto 80px; }
        h1 { margin: 0 0 8px; font: 500 clamp(2rem, 5vw, 3rem)/1.1 Georgia, serif; }
        .intro { margin: 0 0 24px; color: #647571; }
        .notice { margin: 18px 0; padding: 14px 16px; border: 1px solid var(--line); background: white; }
        .notice--error { border-color: #d8a4a0; color: #8d3029; }
        .booking-card { margin-top: 16px; padding: 22px; border: 1px solid var(--line); background: #fffefa; }
        .booking-card h2 { margin: 0; font: 500 1.4rem Georgia, serif; }
        .booking-meta { display: flex; flex-wrap: wrap; gap: 8px 20px; margin-top: 12px; color: #647571; font-size: .94rem; }
        .booking-description { white-space: pre-line; overflow-wrap: anywhere; }
        .booking-actions { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-top: 18px; }
        .booking-actions form { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        button, .link-button { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 9px 14px; border: 0; border-radius: 3px; background: var(--teal); color: white; font: inherit; font-weight: 700; text-decoration: none; cursor: pointer; }
        button.secondary { background: #e8efeb; color: var(--ink); }
        .booking-actions > a.link-button.secondary.mediator-profile-link { border: 1px solid #DDD4EA; background: #fff; color: #4D347F; }
        .booking-actions > a.link-button.secondary.mediator-profile-link:hover { border-color: #6547A5; background: #F7F5FA; color: #4D347F; }
        .booking-actions > a.link-button.secondary.mediator-profile-link:focus-visible { outline: 3px solid #6547A5; outline-offset: 2px; }
        select, textarea { min-height: 42px; padding: 8px; border: 1px solid var(--line); border-radius: 3px; font: inherit; }
        textarea { width: min(300px, 100%); min-height: 70px; }
        .empty { margin-top: 24px; padding: 28px; border: 1px dashed #b6c7c0; text-align: center; background: white; }
        @media (max-width: 600px) { main { margin-top: 24px; } .booking-card { padding: 17px; } }
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
    <?php require_once __DIR__ . '/homepage/header.php'; ?>
    <main>
        <h1>Lịch tư vấn của tôi</h1>
        <p class="intro">Theo dõi yêu cầu, trạng thái xác nhận và thanh toán.</p>
        <?php if (is_array($flash) && !empty($flash['message'])): ?>
            <div class="notice <?= ($flash['type'] ?? '') === 'error' ? 'notice--error' : '' ?>" role="status"><?= booking_escape($flash['message']) ?></div>
        <?php elseif (isset($_GET['created'])): ?>
            <div class="notice" role="status">Đã gửi yêu cầu. Mediato sẽ cập nhật trạng thái sau khi xác nhận với hòa giải viên.</div>
        <?php endif; ?>
        <?php if (!$bookings): ?>
            <div class="empty">
                <strong>Bạn chưa có yêu cầu tư vấn nào.</strong>
                <p>Khám phá danh bạ để chọn hòa giải viên phù hợp.</p>
                <a class="link-button" href="searchpage.php">Xem danh bạ hòa giải viên</a>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $booking): ?>
                <article class="booking-card">
                    <h2><?= booking_escape($booking['title']) ?></h2>
                    <div class="booking-meta">
                        <span>Hòa giải viên: <?= booking_escape($booking['mediator_name']) ?></span>
                        <span>Trạng thái: <?= booking_escape($booking['status']) ?></span>
                        <span><?= $booking['consultation_mode'] === 'online' ? 'Trực tuyến' : 'Tại hiện trường' ?></span>
                        <span>Địa điểm: <?= booking_escape($booking['incident_location']) ?></span>
                        <span>Thời lượng: <?= booking_escape($booking['duration_hours']) ?> giờ</span>
                        <span>Phí dự kiến: <?= number_format((float) $booking['total_price'], 0, ',', '.') ?> đ</span>
                        <span>Thanh toán: <?= booking_escape($booking['payment_method'] ?? 'CASH') ?> / <?= booking_escape($booking['payment_status'] ?? 'UNPAID') ?></span>
                        <span>Thời gian: <?= booking_escape($booking['scheduled_time'] ?: $booking['created_at']) ?></span>
                    </div>
                    <?php if (trim((string) $booking['description']) !== ''): ?>
                        <p class="booking-description"><?= nl2br(booking_escape($booking['description'])) ?></p>
                    <?php endif; ?>
                    <div class="booking-actions">
                        <a class="link-button secondary mediator-profile-link" href="jobdetail.php?id=<?= (int) $booking['mediator_id'] ?>">Xem hồ sơ hòa giải viên</a>
                        <?php if ($booking['status'] === 'pending'): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= booking_escape($_SESSION['booking_csrf']) ?>">
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <button class="secondary" type="submit">Hủy yêu cầu</button>
                            </form>
                        <?php elseif ($booking['status'] === 'completed' && !(bool) $booking['has_review']): ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= booking_escape($_SESSION['booking_csrf']) ?>">
                                <input type="hidden" name="action" value="review">
                                <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>">
                                <label>Đánh giá
                                    <select name="rating" required>
                                        <option value="5">5 sao</option><option value="4">4 sao</option><option value="3">3 sao</option><option value="2">2 sao</option><option value="1">1 sao</option>
                                    </select>
                                </label>
                                <textarea name="comment" maxlength="5000" placeholder="Chia sẻ trải nghiệm (không bắt buộc)"></textarea>
                                <button type="submit">Gửi đánh giá</button>
                            </form>
                        <?php elseif ((bool) $booking['has_review']): ?>
                            <span>Đã gửi đánh giá</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
    <?php require_once __DIR__ . '/homepage/footer.php'; ?>
</body>
</html>
