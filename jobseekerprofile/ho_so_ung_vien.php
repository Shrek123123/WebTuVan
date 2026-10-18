<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true)) {
    $_SESSION['lang'] = 'vi';
}
include __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';

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

if (empty($_SESSION['customer_profile_csrf'])) {
    $_SESSION['customer_profile_csrf'] = bin2hex(random_bytes(32));
}

function customer_account_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function customer_account_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    $characterCount = preg_match_all('/./us', $value, $matches);
    return $characterCount === false ? strlen($value) : $characterCount;
}

$formValues = [
    'full_name' => (string) $account['full_name'],
    'email' => (string) $account['email'],
    'phone' => (string) $account['phone'],
];
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues = [
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
    ];
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');

    $fullNameLength = customer_account_length($formValues['full_name']);
    $emailLength = customer_account_length($formValues['email']);
    $phoneLength = customer_account_length($formValues['phone']);

    if (!hash_equals((string) $_SESSION['customer_profile_csrf'], $csrfToken)) {
        $formError = 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif ($formValues['full_name'] === '' || $fullNameLength > 100) {
        $formError = 'Vui lòng nhập họ tên không quá 100 ký tự.';
    } elseif (!filter_var($formValues['email'], FILTER_VALIDATE_EMAIL) || $emailLength > 100) {
        $formError = 'Vui lòng nhập địa chỉ email hợp lệ (tối đa 100 ký tự).';
    } elseif ($formValues['phone'] === '' || $phoneLength > 20) {
        $formError = 'Vui lòng nhập số điện thoại không quá 20 ký tự.';
    } else {
        $emailStmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
        if (!$emailStmt) {
            throw new RuntimeException('Could not prepare email uniqueness check: ' . $conn->error);
        }
        $emailStmt->bind_param('si', $formValues['email'], $customerId);
        if (!$emailStmt->execute()) {
            throw new RuntimeException('Could not check email uniqueness: ' . $emailStmt->error);
        }
        $emailStmt->store_result();
        $emailInUse = $emailStmt->num_rows > 0;
        $emailStmt->close();

        if ($emailInUse) {
            $formError = 'Email này đã được tài khoản khác sử dụng.';
        } else {
            $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ? AND role = 'customer' AND status = 'active'");
            if (!$updateStmt) {
                throw new RuntimeException('Could not prepare customer profile update: ' . $conn->error);
            }
            $updateStmt->bind_param('sssi', $formValues['full_name'], $formValues['email'], $formValues['phone'], $customerId);
            try {
                if (!$updateStmt->execute()) {
                    throw new RuntimeException('Could not update customer profile: ' . $updateStmt->error);
                }
                $_SESSION['username'] = $formValues['full_name'];
                $formSuccess = 'Thông tin tài khoản đã được cập nhật.';
            } catch (mysqli_sql_exception $exception) {
                if ((int) $exception->getCode() === 1062) {
                    $formError = 'Email này đã được tài khoản khác sử dụng.';
                } else {
                    throw $exception;
                }
            } finally {
                $updateStmt->close();
            }
        }

        if ($formError === '') {
            $stmt = $conn->prepare("SELECT full_name, email, phone, avatar_url FROM users WHERE id = ? AND role = 'customer' AND status = 'active' LIMIT 1");
            if (!$stmt) {
                throw new RuntimeException('Could not prepare refreshed customer account lookup: ' . $conn->error);
            }
            $stmt->bind_param('i', $customerId);
            if (!$stmt->execute()) {
                throw new RuntimeException('Could not refresh customer account: ' . $stmt->error);
            }
            $account = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$account) {
                throw new RuntimeException('Customer account disappeared while updating the profile.');
            }
        }
    }
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
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f6f2; color: #203b38; font: 16px/1.55 Arial, sans-serif; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 10px 20px; background: #943737; color: #fff; }
        .top-bar .title { font-size: 15px; font-weight: 700; }
        .top-bar .company { display: flex; align-items: center; }
        .profile-sidebar-toggle { display: none; margin-left: auto; padding: 6px; border: 0; background: transparent; color: inherit; cursor: pointer; }
        .profile-sidebar-toggle span { display: block; width: 24px; height: 3px; margin: 4px 0; border-radius: 2px; background: currentColor; }
        .main { display: flex; min-height: calc(100vh - 60px); }
        .sidebar { flex: 0 0 180px; width: 180px; padding: 10px; border-right: 1px solid #ccc; background: #fff; }
        .sidebar button { width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #777; background: #fff; color: #333; font: inherit; font-size: 13px; text-align: left; cursor: pointer; }
        .sidebar button:hover, .sidebar button.active { background: #b23b3b; border-color: #b23b3b; color: #fff; }
        .change-info-wrap { position: relative; }
        .change-info-menu { position: absolute; top: 0; left: calc(100% + 10px); z-index: 50; display: none; min-width: 190px; padding: 10px; border: 1px solid #e8dede; background: #fff; box-shadow: 0 12px 28px rgba(0,0,0,.14); }
        .change-info-menu.show { display: flex; flex-direction: column; gap: 8px; }
        .content-area { flex: 1; min-width: 0; background: #f3f3f3; }
        .content-inner { width: min(760px, 100%); margin: 0 auto; padding: 30px; }
        .content-inner h1 { margin: 0 0 8px; font: 500 clamp(2rem, 5vw, 3rem)/1.1 Georgia, serif; }
        .intro { margin: 0 0 24px; color: #647571; }
        .account-card { padding: clamp(22px, 5vw, 38px); border: 1px solid #dce4df; background: #fffefa; }
        .account-heading { display: flex; gap: 20px; align-items: center; margin-bottom: 28px; }
        .avatar { display: grid; flex: 0 0 88px; width: 88px; height: 88px; place-items: center; overflow: hidden; border: 1px solid #dce4df; border-radius: 50%; background: #e8efeb; color: #176b62; font: 500 2rem Georgia, serif; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .account-heading h2 { margin: 0; overflow-wrap: anywhere; font: 500 1.7rem Georgia, serif; }
        .form-field { margin-top: 18px; }
        .form-field label { display: block; margin-bottom: 7px; color: #203b38; font-weight: 700; }
        .form-field input { width: 100%; min-height: 46px; padding: 10px 12px; border: 1px solid #cbd8d2; border-radius: 3px; background: #fff; color: #203b38; font: inherit; }
        .form-field input:focus { border-color: #176b62; outline: 3px solid rgba(23, 107, 98, .16); }
        .form-message { margin: 0 0 18px; padding: 11px 13px; border: 1px solid; }
        .form-message.error { border-color: #d8a4a0; background: #fff4f2; color: #8d3029; }
        .form-message.success { border-color: #a9cbb7; background: #f0faf3; color: #236b45; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 26px; }
        .actions button { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #176b62; border-radius: 3px; background: #176b62; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        .actions a { display: inline-flex; min-height: 44px; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #176b62; border-radius: 3px; background: #176b62; color: white; font-weight: 700; text-decoration: none; }
        .actions a.secondary { background: transparent; color: #176b62; }
        .account-card :focus-visible { outline-offset: 2px; }
        @media (max-width: 768px) {
            .top-bar { position: relative; flex-direction: row; }
            .profile-sidebar-toggle { display: block; }
            .main { display: block; min-height: 0; }
            .main > .sidebar { position: fixed; top: 0; right: 0; bottom: 0; z-index: 3000; width: min(280px, 86vw); height: 100vh; overflow-y: auto; border: 0; box-shadow: -3px 0 14px rgba(0,0,0,.2); transform: translateX(105%); transition: transform .25s ease; }
            .main > .sidebar.active { transform: translateX(0); }
            .change-info-menu { position: static; min-width: 0; margin-top: 5px; box-shadow: none; }
            .profile-sidebar-overlay { position: fixed; inset: 0; z-index: 2999; background: rgba(0,0,0,.4); opacity: 0; pointer-events: none; transition: opacity .2s ease; }
            .profile-sidebar-overlay.active { opacity: 1; pointer-events: auto; }
            .content-inner { padding: 24px 16px; }
        }
        @media (max-width: 520px) { .account-heading { gap: 14px; } .actions { flex-direction: column; } .actions > * { width: 100%; } }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=7">
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <div class="top-bar">
        <div class="title"><?= customer_account_escape($lang['jobseekerprofile'] ?? 'Hồ sơ ứng viên') ?></div>
        <button class="profile-sidebar-toggle" type="button" aria-label="Mở menu hồ sơ" aria-controls="profile-sidebar" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
    <div class="main">
        <aside class="sidebar" id="profile-sidebar" aria-label="Menu hồ sơ ứng viên">
            <button type="button" class="active" onclick="window.location.href='ho_so_ung_vien.php'"><?= customer_account_escape($lang['btn_myprofile'] ?? 'Hồ sơ của tôi') ?></button>
            <button type="button" onclick="window.location.href='don_ung_tuyen.php'"><?= customer_account_escape($lang['btn_myapplications'] ?? 'Đơn ứng tuyển') ?></button>
            <button type="button" onclick="window.location.href='cong_viec_da_luu.php'"><?= customer_account_escape($lang['btn_savedjobs'] ?? 'Việc đã lưu') ?></button>
            <button type="button" onclick="window.location.href='followed_company.php'"><?= customer_account_escape($lang['btn_interested_jobs'] ?? 'Công ty quan tâm') ?></button>
            <div class="change-info-wrap">
                <button id="btn-changeinfo" type="button"><?= customer_account_escape($lang['btn_changeinfo'] ?? 'Thay đổi thông tin') ?></button>
                <div id="changeInfoMenu" class="change-info-menu" aria-label="Thao tác thay đổi thông tin">
                    <button type="button" onclick="window.location.href='thay_doi_email.php'"><?= customer_account_escape($lang['change_email_action'] ?? 'Đổi email') ?></button>
                    <button type="button" onclick="window.location.href='thay_doi_mat_khau.php'"><?= customer_account_escape($lang['change_password_action'] ?? 'Đổi mật khẩu') ?></button>
                </div>
            </div>
        </aside>
        <div class="content-area">
          <main class="content-inner">
            <h1>Tài khoản</h1>
            <p class="intro">Cập nhật thông tin tài khoản khách hàng Mediato của bạn.</p>
            <section class="account-card" aria-label="Chỉnh sửa thông tin tài khoản">
            <div class="account-heading">
                <div class="avatar">
                    <?php if ($avatarUrl !== ''): ?>
                        <img src="<?= customer_account_escape($avatarUrl) ?>" alt="Ảnh đại diện">
                    <?php else: ?>
                        <?= customer_account_escape(function_exists('mb_substr') ? mb_substr($formValues['full_name'], 0, 1, 'UTF-8') : substr($formValues['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h2><?= customer_account_escape($formValues['full_name']) ?></h2>
            </div>
            <?php if ($formError !== ''): ?>
                <p class="form-message error" role="alert"><?= customer_account_escape($formError) ?></p>
            <?php elseif ($formSuccess !== ''): ?>
                <p class="form-message success" role="status"><?= customer_account_escape($formSuccess) ?></p>
            <?php endif; ?>
            <form method="post" action="ho_so_ung_vien.php">
                <input type="hidden" name="csrf_token" value="<?= customer_account_escape($_SESSION['customer_profile_csrf']) ?>">
                <div class="form-field">
                    <label for="full_name">Họ và tên</label>
                    <input id="full_name" name="full_name" type="text" maxlength="100" autocomplete="name" required value="<?= customer_account_escape($formValues['full_name']) ?>">
                </div>
                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" maxlength="100" autocomplete="email" required value="<?= customer_account_escape($formValues['email']) ?>">
                </div>
                <div class="form-field">
                    <label for="phone">Số điện thoại</label>
                    <input id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" required value="<?= customer_account_escape($formValues['phone']) ?>">
                </div>
                <div class="actions">
                    <button type="submit">Lưu thay đổi</button>
                    <a class="secondary" href="bookings.php">Xem lịch tư vấn</a>
                    <a class="secondary" href="../index.php">Về danh bạ hòa giải viên</a>
                </div>
            </form>
            </section>
          </main>
        </div>
    </div>
    <?php include __DIR__ . '/mobile_sidebar.php'; ?>
    <script>
        (function () {
            var changeInfoButton = document.getElementById('btn-changeinfo');
            var changeInfoMenu = document.getElementById('changeInfoMenu');
            if (changeInfoButton && changeInfoMenu) {
                changeInfoButton.addEventListener('click', function (event) {
                    event.stopPropagation();
                    changeInfoMenu.classList.toggle('show');
                    changeInfoButton.classList.toggle('active', changeInfoMenu.classList.contains('show'));
                });
                document.addEventListener('click', function (event) {
                    if (!changeInfoButton.contains(event.target) && !changeInfoMenu.contains(event.target)) {
                        changeInfoMenu.classList.remove('show');
                        changeInfoButton.classList.remove('active');
                    }
                });
            }
        }());
    </script>
</body>
</html>
