<?php
session_start();
$conn = null;
require_once __DIR__ . '/config.php';
$language = in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true) ? $_SESSION['lang'] : 'vi';
$_SESSION['lang'] = $language;
include __DIR__ . '/lang/' . $language . '.php';
$registrationError = '';
$requestedRedirect = trim((string) ($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
$redirectTarget = null;
$parsedRedirect = $requestedRedirect !== '' && strlen($requestedRedirect) <= 2048 ? parse_url($requestedRedirect) : false;
if ($parsedRedirect !== false && empty($parsedRedirect['host']) && empty($parsedRedirect['scheme'])
  && strpos($requestedRedirect, '//') !== 0 && !preg_match('/[\r\n\x00-\x1F\x7F]/', $requestedRedirect)) {
  $redirectTarget = $requestedRedirect;
}
if (empty($_SESSION['customer_register_csrf'])) {
  $_SESSION['customer_register_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $fullName = trim((string) ($_POST['full_name'] ?? ''));
  $email = trim((string) ($_POST['email'] ?? ''));
  $phone = trim((string) ($_POST['phonenumber'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  $retypePassword = (string) ($_POST['retypepass'] ?? '');
  $csrfToken = (string) ($_POST['csrf_token'] ?? '');

  if (!hash_equals((string) $_SESSION['customer_register_csrf'], $csrfToken)) {
    $registrationError = 'Phiên đăng ký không hợp lệ. Vui lòng tải lại trang và thử lại.';
  } elseif ($fullName === '' || strlen($fullName) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || $phone === '' || strlen($phone) > 20) {
    $registrationError = 'Vui lòng nhập họ tên, email và số điện thoại hợp lệ.';
  } elseif (strlen($password) < 8) {
    $registrationError = 'Mật khẩu cần có ít nhất 8 ký tự.';
  } elseif ($password !== $retypePassword) {
    $registrationError = 'Mật khẩu nhập lại không khớp.';
  } elseif (($_POST['terms'] ?? '') !== 'accepted') {
    $registrationError = 'Vui lòng đồng ý với điều khoản sử dụng.';
  } else {
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
    if (!$stmt) {
      throw new RuntimeException('Không thể chuẩn bị yêu cầu đăng ký: ' . $conn->error);
    }
    $stmt->bind_param('ssss', $fullName, $email, $phone, $passwordHash);
    try {
      $stmt->execute();
      $customerId = (int) $conn->insert_id;
      session_regenerate_id(true);
      $_SESSION['customer_id'] = $customerId;
      $_SESSION['jobseeker_id'] = $customerId;
      $_SESSION['username'] = $fullName;
      $_SESSION['role'] = 'jobseeker';
      unset($_SESSION['customer_register_csrf']);
      header('Location: ' . ($redirectTarget ?? 'bookings.php'));
      exit();
    } catch (mysqli_sql_exception $exception) {
      if ((int) $exception->getCode() === 1062) {
        $registrationError = 'Email này đã được đăng ký. Vui lòng đăng nhập hoặc sử dụng email khác.';
      } else {
        throw $exception;
      }
    } finally {
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>

<head>
  <meta charset="UTF-8">
  <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jobseeker Register - ViecNhe</title>
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

    .main-content {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
      box-sizing: border-box;
    }

    .register-form-container {
      background: #ffffff;
      color: #8B0000;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
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

    input[type="checkbox"] {
      width: auto;
      margin-right: 5px;
    }

    .btn-register {
      width: 100%;
      padding: 12px;
      background-color: #d90000;
      color: #ffffff;
      font-size: 18px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      margin-top: 10px;
    }

    .login-link,
    .redirect-employer {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
    }

    .login-link a,
    .redirect-employer a {
      color: #d90000;
      text-decoration: none;
      font-weight: bold;
    }

    .terms-container {
      margin-top: 20px;
      display: flex;
      flex-direction: column;
      align-items: start;
    }

    #terms-error {
      color: red;
      font-size: 0.9em;
      margin-top: 5px;
      display: none;
    }
  </style>
    <link rel="stylesheet" href="mediato-theme.css?v=7">
</head>

<body>
  <?php include_once(__DIR__ . '/header_loginlogout/jobseekerregister.php'); ?>
  <div class="main-content">
    <div class="register-form-container">
      <h2><?php echo $lang['jobseekerregister_title']; ?></h2>
      <p><?php echo $lang['jobseekerregister_subtitle']; ?></p>
      <?php if ($registrationError !== ''): ?>
        <p role="alert" style="color: #9f2727;"><?= htmlspecialchars($registrationError, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <?php $registerAction = 'jobseekerregister.php' . ($redirectTarget !== null ? '?redirect=' . rawurlencode($redirectTarget) : ''); ?>
      <form action="<?= htmlspecialchars($registerAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['customer_register_csrf'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($redirectTarget !== null): ?><input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
        <div class="form-group">
          <label>Họ và tên</label>
          <input type="text" placeholder="Họ tên của bạn" name="full_name" maxlength="100" required>
        </div>
        <div class="form-group">
          <label><?php echo $lang['email']; ?></label>
          <input type="email" placeholder="<?php echo $lang['placeholder_email']; ?>" name="email" required>
        </div>
        <div class="form-group">
          <label><?php echo $lang['phone_number_register']; ?></label>
          <input type="tel" placeholder="<?php echo $lang['placeholder_phone_number_register']; ?>" name="phonenumber" maxlength="20" required>
        </div>
        <div class="form-group">
          <label><?php echo $lang['password']; ?></label>
          <div class="password-toggle-wrapper">
            <input type="password" placeholder="<?php echo $lang['placeholder_password']; ?>" name="password" minlength="8" required>
            <button type="button" class="toggle-password" data-target="password"
              aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
          </div>
        </div>
        <div class="form-group">
          <label><?php echo $lang['reenter_password']; ?></label>
          <div class="password-toggle-wrapper">
            <input type="password" placeholder="<?php echo $lang['placeholder_reenter_password']; ?>" name="retypepass"
              minlength="8" required>
            <button type="button" class="toggle-password" data-target="retypepass"
              aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
          </div>
        </div>
        <div class="terms-container" style="align-items: center;">
          <label style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="terms" value="accepted" required style="margin: 0;">
            <small><?php echo $lang['terms_agreement_text']; ?> <a
          href="term.php"><?php echo $lang['terms_of_use_link_text']; ?></a>
              <?php echo $lang['terms_of_use_after_link_text']; ?></small>
          </label>
          <span id="terms-error"></span>
        </div>
        <button class="btn-register"><?php echo $lang['button_register']; ?></button>
      </form>
      <div class="login-link">
        <?php echo $lang['login_link_text_prefix']; ?> <a
          href="jobseekerlogin.php<?= $redirectTarget !== null ? '?redirect=' . rawurlencode($redirectTarget) : '' ?>"><?php echo $lang['login_link_text']; ?></a>
      </div>
    </div>
  </div>
</body>

</html>

<?php
?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form');
    const termsCheckbox = document.querySelector('input[name="terms"]');
    const termsError = document.getElementById('terms-error');

    form.addEventListener('submit', function (event) {
      if (!termsCheckbox.checked) {
        event.preventDefault();
        termsError.textContent = 'Please read the terms and check the box';
        termsError.style.display = 'block';
        setTimeout(function () {
          termsError.style.display = 'none';
          termsError.textContent = '';
        }, 3000);
      } else {
        termsError.style.display = 'none';
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const input = document.getElementById(btn.dataset.target) || document.querySelector('input[name="' + btn.dataset.target + '"]');
        if (!input) return;
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      });
    });
  });
</script>