<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!in_array($_SESSION['role'] ?? '', ['customer', 'jobseeker'], true) || empty($_SESSION['customer_id'])) {
    header('Location: ../jobseekerlogin.php?redirect=' . rawurlencode('jobseekerprofile/ho_so_ung_vien.php'));
    exit();
}

$customerId = (int) $_SESSION['customer_id'];
$stmt = $conn->prepare("SELECT full_name, email, phone, avatar_url FROM users WHERE id = ? AND role = 'customer' AND status = 'active' LIMIT 1");
if (!$stmt) {
    throw new RuntimeException('Could not prepare customer account lookup: ' . $conn->error);
}
$stmt->bind_param('i', $customerId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    unset($_SESSION['customer_id'], $_SESSION['jobseeker_id'], $_SESSION['role'], $_SESSION['username']);
    header('Location: ../jobseekerlogin.php');
    exit();
}

function customer_account_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$avatar = trim((string) ($account['avatar_url'] ?? ''));
$avatarUrl = '';
if ($avatar !== '' && strpos($avatar, 'uploads/') === 0 && strpos($avatar, '..') === false) {
    $avatarUrl = $avatar;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tài khoản của tôi — Mediato</title>
    <base href="../">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f6f2; color: #203b38; font: 16px/1.55 Arial, sans-serif; }
        main { width: min(760px, calc(100% - 32px)); margin: 48px auto 80px; }
        h1 { margin: 0 0 8px; font: 500 clamp(2rem, 5vw, 3rem)/1.1 Georgia, serif; }
        .intro { margin: 0 0 24px; color: #647571; }
        .account-card { padding: clamp(22px, 5vw, 38px); border: 1px solid #dce4df; background: #fffefa; }
        .account-heading { display: flex; gap: 20px; align-items: center; margin-bottom: 28px; }
        .avatar { display: grid; flex: 0 0 88px; width: 88px; height: 88px; place-items: center; overflow: hidden; border: 1px solid #dce4df; border-radius: 50%; background: #e8efeb; color: #176b62; font: 500 2rem Georgia, serif; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .account-heading h2 { margin: 0; font: 500 1.7rem Georgia, serif; }
        dl { margin: 0; }
        .account-field { display: grid; grid-template-columns: minmax(120px, .65fr) 1.35fr; gap: 15px; padding: 15px 0; border-top: 1px solid #e5ebe7; }
        dt { color: #647571; font-weight: 700; }
        dd { margin: 0; overflow-wrap: anywhere; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 26px; }
        .actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #176b62; border-radius: 3px; background: #176b62; color: white; font-weight: 700; text-decoration: none; }
        .actions a.secondary { background: transparent; color: #176b62; }
        @media (max-width: 520px) { main { margin-top: 24px; } .account-field { grid-template-columns: 1fr; gap: 4px; } .account-heading { gap: 14px; } }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body>
    <?php require __DIR__ . '/../homepage/header.php'; ?>
    <main>
        <h1>Tài khoản</h1>
        <p class="intro">Thông tin tài khoản khách hàng Mediato của bạn.</p>
        <section class="account-card" aria-label="Thông tin tài khoản">
            <div class="account-heading">
                <div class="avatar">
                    <?php if ($avatarUrl !== ''): ?>
                        <img src="<?= customer_account_escape($avatarUrl) ?>" alt="Ảnh đại diện">
                    <?php else: ?>
                        <?= customer_account_escape(function_exists('mb_substr') ? mb_substr((string) $account['full_name'], 0, 1, 'UTF-8') : substr((string) $account['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h2><?= customer_account_escape($account['full_name']) ?></h2>
            </div>
            <dl>
                <div class="account-field"><dt>Họ và tên</dt><dd><?= customer_account_escape($account['full_name']) ?></dd></div>
                <div class="account-field"><dt>Email</dt><dd><?= customer_account_escape($account['email']) ?></dd></div>
                <div class="account-field"><dt>Số điện thoại</dt><dd><?= customer_account_escape($account['phone']) ?></dd></div>
            </dl>
            <div class="actions">
                <a href="jobseekerprofile/bookings.php">Xem lịch tư vấn</a>
                <a class="secondary" href="index.php">Về danh bạ hòa giải viên</a>
            </div>
        </section>
    </main>
</body>
</html>
