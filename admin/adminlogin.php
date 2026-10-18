<?php
session_start();
require_once __DIR__ . '/../config.php';
if (!in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true)) {
    $_SESSION['lang'] = 'vi';
}
include_once __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $accountLockedMessage = $lang['account_locked'] ?? 'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.';

    // Check if the email and password are correct
    $stmt = $conn->prepare("SELECT id, full_name, password_hash, status FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password_hash'])) {
            if (($row['status'] ?? 'active') !== 'active') {
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var errorDiv = document.createElement('div');
                    errorDiv.style.color = 'red';
                    errorDiv.style.textAlign = 'center';
                    errorDiv.style.marginTop = '10px';
                    errorDiv.innerText = " . json_encode($accountLockedMessage, JSON_UNESCAPED_UNICODE) . ";
                    document.querySelector('input[name=\"password\"]').parentNode.appendChild(errorDiv);
                });
                </script>";
            } else {
                // Password is correct
                session_regenerate_id(true);
                $_SESSION['usernameadmin'] = $row['full_name'];
                $_SESSION['admin_id'] = (int) $row['id'];
                header("Location: admindashboard.php");
                exit();
            }
        } else {
            // Invalid password
            echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var errorDiv = document.createElement('div');
                errorDiv.style.color = 'red';
                errorDiv.style.textAlign = 'center';
                errorDiv.style.marginTop = '10px';
                errorDiv.innerText = 'Mật khẩu không đúng. Vui lòng thử lại.';
                document.querySelector('input[name=\"password\"]').parentNode.appendChild(errorDiv);
            });
            </script>";
        }
    } else {
        // Email not found
        echo "<script>alert('Email không tồn tại. Vui lòng thử lại.');</script>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES, 'UTF-8') ?>">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['admin_login'] ?? 'Admin Login', ENT_QUOTES, 'UTF-8') ?> - Mediato</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f3f3f3;
        }

        .container {
            width: 100%;
            max-width: 460px;
        }

        .login-card {
            width: 100%;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .logo {
            display: block;
            width: auto;
            max-width: 100%;
            height: 70px;
            margin: 0 auto 20px;
            object-fit: contain;
        }

        h2 {
            margin: 0 0 10px;
            color: #d90000;
        }

        .intro {
            margin: 0 0 25px;
            color: #555;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #666;
            cursor: pointer;
            transform: translateY(-50%);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #d90000;
            color: white;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
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

        .forgot a {
            color: #d90000;
            font-weight: bold;
            text-decoration: none;
        }

        .support {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #555;
            font-size: 14px;
            line-height: 1.4;
        }

        .support p {
            margin: 8px 0;
        }

        .support a {
            color: #d90000;
            font-weight: bold;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            body {
                align-items: flex-start;
                padding: 15px;
            }

            .login-card {
                padding: 28px 20px;
            }

            .logo {
                height: 58px;
            }
        }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>

<body>
    <div class="container">
        <div class="login-card">
            <a href="../index.php">
                <img class="logo" src="../image/logo.png" alt="ViecNhe Logo">
            </a>
            <h2><?= $lang['admin_login'] ?? 'Chào mừng quản trị viên' ?></h2>
            <p class="intro">Hãy cùng đưa hệ thống ViecNhe lên một tầm cao mới</p>
            <form action="adminlogin.php" method="POST">
                <div class="form-group">
                    <label for="admin-email"><?= $lang['email'] ?? 'Email' ?></label>
                    <input id="admin-email" type="email" placeholder="<?= $lang['email'] ?? 'Email' ?>" name="email" required>
                </div>
                <div class="form-group">
                    <label for="admin-password"><?= $lang['password'] ?? 'Mật khẩu' ?></label>
                    <div class="password-wrapper">
                        <input id="admin-password" type="password" placeholder="<?= $lang['password'] ?? 'Mật khẩu' ?>" name="password" required>
                        <button type="button" class="toggle-password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
                    </div>
                    <div class="forgot"><a href="jobseekerforgetpassword.php">Quên mật khẩu</a></div>
                </div>
                <button class="btn-login">Đăng nhập</button>
            </form>
            <div class="support">
                <p>Gặp khó khăn khi đăng nhập?</p>
                <p>Gọi cho chúng tôi: <strong>012 345 6789</strong></p>
                <p>Gửi email cho chúng tôi: <a href="mailto:support@jobhive.com">support@jobhive.com</a></p>
            </div>
        </div>

    </div>
</body>

</html>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('.toggle-password');
        const input = document.getElementById('admin-password');

        if (!toggle || !input) {
            return;
        }

        toggle.addEventListener('click', function () {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });
</script>