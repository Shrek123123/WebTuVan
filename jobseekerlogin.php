<?php
session_start();
require_once 'config.php';

if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$language = in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true) ? $_SESSION['lang'] : 'vi';
$_SESSION['lang'] = $language;
include __DIR__ . '/lang/' . $language . '.php';
$loginError = '';
if (empty($_SESSION['customer_login_csrf'])) {
    $_SESSION['customer_login_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals((string) $_SESSION['customer_login_csrf'], $csrfToken)) {
        $loginError = 'Phiên đăng nhập không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = $conn->prepare("SELECT id, full_name, password_hash, status FROM users WHERE email = ? AND role = 'customer' LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException('Không thể chuẩn bị yêu cầu đăng nhập: ' . $conn->error);
        }
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            $loginError = $lang['login_invalid_email'] ?? 'Email hoặc mật khẩu không đúng.';
        } elseif (($row['status'] ?? 'active') !== 'active') {
            $loginError = $lang['account_locked'] ?? 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            session_regenerate_id(true);
            $_SESSION['username'] = $row['full_name'];
            $_SESSION['role'] = 'jobseeker';
            $_SESSION['customer_id'] = (int) $row['id'];
            $_SESSION['jobseeker_id'] = (int) $row['id'];
            $redirect = trim((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
            $parsed = $redirect !== '' && strlen($redirect) <= 2048 ? parse_url($redirect) : false;
            if ($parsed !== false && empty($parsed['host']) && empty($parsed['scheme'])
                && strpos($redirect, '//') !== 0 && !preg_match('/[\r\n\x00-\x1F\x7F]/', $redirect)) {
                header('Location: ' . $redirect);
            } else {
                header('Location: bookings.php');
            }
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['jobseeker_login_title']; ?> - ViecNhe</title>

    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #f3f3f3;
        }
        header {
            width: 100%;

        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .login-form-container {
            background: #ffffff;
            color: #8B0000;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-sizing: border-box;
        }

        h2 {
            color: #d90000;
            margin-bottom: 10px;
        }

        p {
            margin-top: 0;
            margin-bottom: 25px;
            color: #555;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .password-toggle-wrapper {
            position: relative;
            width: 100%;
        }

        .password-toggle-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #666;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            line-height: 1;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #d90000;
            color: #ffffff;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color 0.3s ease;
        }

        .btn-login:hover {
            background-color: #a60000;
        }

        .forgot {
            text-align: right;
            margin-top: 10px;
            font-size: 14px;
        }

        .forgot a,
        .signup a,
        .redirect-employer a {
            color: #d90000;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .forgot a:hover,
        .signup a:hover,
        .redirect-employer a:hover {
            color: #a60000;
        }

        .signup {
            text-align: center;
            margin-top: 30px;
            font-size: 15px;
            color: #555;
        }

        @media (max-width: 600px) {
            .login-form-container {
                padding: 25px;
                margin: 0 15px;
            }
        }
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>

<body>
    <?php include_once(__DIR__ . '/header_loginlogout/jobseekerlogin.php'); ?>

    <div class="main-content">
        <div class="login-form-container">
            <h2><?= $lang['jobseeker_login_subtitle_h2']; ?></h2>
            <p><?= $lang['jobseeker_login_subtitle_p']; ?></p>

            <?php
            // Preserve redirect query parameter in the form action if present so POST keeps it
            $form_action = 'jobseekerlogin.php';
            if (!empty($_GET['redirect'])) {
                $form_action .= '?redirect=' . urlencode($_GET['redirect']);
            }
            $registration_url = 'jobseekerregister.php';
            if (!empty($_GET['redirect'])) {
                $registration_url .= '?redirect=' . urlencode($_GET['redirect']);
            }
            ?>
            <form action="<?= htmlspecialchars($form_action) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['customer_login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($loginError !== ''): ?>
                    <p role="alert" style="color: #9f2727;"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <div class="form-group">
                    <label for="emailInput"><?= $lang['email']; ?></label>
                    <input type="email" id="emailInput" name="email"
                        placeholder="<?= $lang['jobseeker_login_email_placeholder']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="passwordInput"><?= $lang['password']; ?></label>
                    <div class="password-toggle-wrapper">
                        <input type="password" id="passwordInput" name="password"
                            placeholder="<?= $lang['jobseeker_login_password_placeholder']; ?>" required>
                        <button type="button" class="toggle-password" data-target="passwordInput"
                            aria-label="Toggle password visibility" aria-pressed="false">👁</button>
                    </div>
                    <div class="forgot">
                        <a href="jobseekerforgetpassword.php"><?= $lang['jobseeker_login_forgot_password']; ?></a>
                    </div>
                </div>

                <button type="submit" class="btn-login"><?= $lang['jobseeker_login_button']; ?></button>
            </form>

            <div class="signup">
                <?= htmlspecialchars($lang['jobseeker_login_signup_prefix'] ?? 'Chưa có tài khoản?', ENT_QUOTES, 'UTF-8') ?>
                <a href="<?= htmlspecialchars($registration_url, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($lang['jobseeker_login_signup_link'] ?? 'Đăng ký tại đây', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>

        </div>
    </div>

</body>

</html>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const input = document.getElementById(btn.dataset.target) || document.querySelector('input[name="' + btn.dataset.target + '"]');
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.textContent = isHidden ? '🙈' : '👁';
        btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      });
    });
  });
</script>
