<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';
$orderCode = (int) ($_GET['orderCode'] ?? $_GET['invoice'] ?? 0);
$status = 'pending';
if ($orderCode > 0) {
    reconcileJobPaymentOrder($conn, $orderCode);
    $stmt = $conn->prepare('SELECT status FROM job_payments WHERE order_code = ? LIMIT 1');
    $stmt->bind_param('i', $orderCode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $status = (string) ($row['status'] ?? 'pending');
    $stmt->close();
}
?><!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Kết quả thanh toán</title>
<style>body{font-family:Arial,sans-serif;background:#f5f7fb;padding:40px}.box{max-width:560px;margin:auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 8px 30px #0001;text-align:center}.box-brand{display:block;width:min(190px,70%);height:auto;margin:0 auto 22px}.paid{color:#16803c}.pending{color:#9a6700}.failed{color:#b42318}a{display:inline-block;margin-top:20px;color:#b42318}</style>    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body><main class="box"><img class="box-brand" src="../image/logo.png" alt="ViecNhe" onerror="this.src='../image/favicon.svg'"><h1 class="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?php echo $status === 'paid' ? 'Thanh toán thành công' : 'Đang chờ xác nhận thanh toán'; ?></h1>
<p>Mã giao dịch: <?= htmlspecialchars((string) $orderCode, ENT_QUOTES, 'UTF-8') ?></p>
<?php if ($status === 'paid'): ?><p>Bài đăng đã được tạo và đang chờ admin duyệt.</p><?php else: ?><p>PayOS chưa xác nhận giao dịch thành công. Bài đăng chưa được tạo.</p><?php endif; ?>
<a href="my_job_posts.php">Quay lại danh sách bài đăng</a></main></body></html>