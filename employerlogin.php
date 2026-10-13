<?php
session_start();
require_once 'config.php';
$redirectAfterLogin = (string) ($_GET['redirect'] ?? $_POST['redirect'] ?? 'employerpage.php');
if ($redirectAfterLogin !== 'employerpage.php') {
    $redirectAfterLogin = 'employerpage.php';
}
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
include 'lang/' . $_SESSION['lang'] . '.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $invalidPasswordMessage = $lang['login_invalid_password'] ?? 'Invalid password. Please try again.';
    $invalidEmailMessage = $lang['login_invalid_email'] ?? 'Invalid email. Please try again.';
    // Check if the email and password are correct
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ? AND user_type = 'employer'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            if (($row['status'] ?? 'unlocked') === 'locked') {
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var errorDiv = document.createElement('div');
                    errorDiv.style.color = 'red';
                    errorDiv.style.textAlign = 'center';
                    errorDiv.style.marginTop = '10px';
                    errorDiv.innerText = " . json_encode($lang['account_locked'] ?? 'Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.', JSON_UNESCAPED_UNICODE) . ";
                    document.querySelector('input[name=\"password\"]').closest('.form-group').appendChild(errorDiv);
                });
                </script>";
            } else {
                // Password is correct
                $_SESSION['usernameemployer'] = $row['username'];
                $_SESSION['role'] = 'employer';
                $_SESSION['employerid'] = $row['id'];
                header("Location: " . $redirectAfterLogin);
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
                errorDiv.innerText = " . json_encode($invalidPasswordMessage) . ";
                document.querySelector('input[name=\"password\"]').closest('.form-group').appendChild(errorDiv);
            });
            </script>";
        }
    } else {
        // Email not found
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var errorDiv = document.createElement('div');
                errorDiv.style.color = 'red';
                errorDiv.style.textAlign = 'center';
                errorDiv.style.marginTop = '10px';
                errorDiv.innerText = " . json_encode($invalidEmailMessage) . ";
                document.querySelector('input[name=\"password\"]').closest('.form-group').appendChild(errorDiv);
            });
            </script>";
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['employer_login_title'] ?></title>
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
            /* Darker red on hover */
        }

        .forgot {
            text-align: right;
            margin-top: 10px;
            font-size: 14px;
        }

        .forgot a,
        .signup a,
        .redirect-jobseeker a {
            color: #d90000;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s ease;
        }

        .forgot a:hover,
        .signup a:hover,
        .redirect-jobseeker a:hover {
            color: #a60000;
        }

        .signup {
            text-align: center;
            margin-top: 30px;
            font-size: 15px;
            color: #555;
        }

        /* --- Responsive adjustments (optional but recommended) --- */
        @media (max-width: 600px) {
            .login-form-container {
                padding: 25px;
                margin: 0 15px;
                /* Add some margin on smaller screens */
            }
        }
    </style>
</head>

<body>
    <?php include_once(__DIR__ . '/header_loginlogout/employerlogin.php'); ?>

    <div class="main-content">
        <div class="login-form-container">
            <h2><?= $lang['employer_login_subtitle_h2']; ?></h2>
            <p><?= $lang['employer_login_subtitle_p']; ?></p>

            <form action="employerlogin.php" method="POST">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectAfterLogin, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="emailInput"><?= $lang['email']; ?></label>
                    <input type="email" id="emailInput" name="email"
                        placeholder="<?= $lang['employer_login_email_placeholder']; ?>" required>
                </div>

                <div class="form-group">
                    <label for="passwordInput"><?= $lang['password']; ?></label>
                    <div class="password-toggle-wrapper">
                        <input type="password" id="passwordInput" name="password"
                            placeholder="<?= $lang['employer_login_password_placeholder']; ?>" required>
                        <button type="button" class="toggle-password" data-target="passwordInput"
                            aria-label="Toggle password visibility" aria-pressed="false">👁</button>
                    </div>
                    <div class="forgot">
                        <a href="employerforgetpassword.php"><?= $lang['employer_login_forgot_password']; ?></a>
                    </div>
                </div>

                <button type="submit" class="btn-login"><?= $lang['employer_login_button']; ?></button>
            </form>

            <div class="signup">
                <?= $lang['employer_login_signup']; ?>
            </div>

            <div class="redirect-jobseeker" style="margin-top: 20px; text-align: center;">
                <p><?= $lang['employer_login_redirect_jobseeker']; ?></p>
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
