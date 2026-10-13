<?php
session_start();
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
</head>

<body>
  <?php include_once(__DIR__ . '/header_loginlogout/jobseekerregister.php'); ?>
  <div class="main-content">
    <div class="register-form-container">
      <h2><?php echo $lang['jobseekerregister_title']; ?></h2>
      <p><?php echo $lang['jobseekerregister_subtitle']; ?></p>
      <form action="jobseekerregister.php" method="post">
        <div class="form-group">
          <label><?php echo $lang['username']; ?></label>
          <input type="text" placeholder="<?php echo $lang['placeholder_username']; ?>" name="username" required>
        </div>
        <div class="form-group">
          <label><?php echo $lang['email']; ?></label>
          <input type="email" placeholder="<?php echo $lang['placeholder_email']; ?>" name="email" required>
        </div>
        <div class="form-group">
          <label><?php echo $lang['phone_number_register']; ?></label>
          <input type="tel" placeholder="<?php echo $lang['placeholder_phone_number_register']; ?>" name="phonenumber">
        </div>
        <div class="form-group">
          <label><?php echo $lang['password']; ?></label>
          <div class="password-toggle-wrapper">
            <input type="password" placeholder="<?php echo $lang['placeholder_password']; ?>" name="password" required>
            <button type="button" class="toggle-password" data-target="password"
              aria-label="Toggle password visibility" aria-pressed="false">👁</button>
          </div>
        </div>
        <div class="form-group">
          <label><?php echo $lang['reenter_password']; ?></label>
          <div class="password-toggle-wrapper">
            <input type="password" placeholder="<?php echo $lang['placeholder_reenter_password']; ?>" name="retypepass"
              required>
            <button type="button" class="toggle-password" data-target="retypepass"
              aria-label="Toggle password visibility" aria-pressed="false">👁</button>
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
          href="jobseekerlogin.php"><?php echo $lang['login_link_text']; ?></a>
      </div>
      <div class="redirect-employer">
        <p><?php echo $lang['employer_redirect_text_prefix']; ?> <a
            href="employerpage.php"><?php echo $lang['employer_redirect_link_text']; ?></a></p>
      </div>
    </div>
  </div>
</body>

</html>

<?php
require_once('config.php');
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $email = $_POST['email'];
  $password = $_POST['password'];
  $retypepass = $_POST['retypepass'];

  if (empty($username) || empty($email) || empty($password) || empty($retypepass)) {
    echo "<script>alert('All fields are required.'); window.history.back();</script>";
    exit;
  }

  if ($password !== $retypepass) {
    echo "<script>
      const passwordField = document.querySelector('input[name=\"password\"]');
      const errorTooltip = document.createElement('div');
      errorTooltip.textContent = 'Passwords do not match';
      errorTooltip.style.position = 'absolute';
      errorTooltip.style.backgroundColor = '#f8d7da';
      errorTooltip.style.color = '#721c24';
      errorTooltip.style.padding = '5px';
      errorTooltip.style.border = '1px solid #f5c6cb';
      errorTooltip.style.borderRadius = '5px';
      errorTooltip.style.marginTop = '5px';
      errorTooltip.style.fontSize = '12px';
      passwordField.parentNode.appendChild(errorTooltip);
      passwordField.focus();
      setTimeout(() => { errorTooltip.remove(); }, 3000);
    </script>";
    exit;
  }

  $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    echo "<script>
      const emailField = document.querySelector('input[name=\"email\"]');
      const errorTooltip = document.createElement('div');
      errorTooltip.textContent = " . json_encode($lang['email_exists']) . ";
      errorTooltip.style.position = 'absolute';
      errorTooltip.style.backgroundColor = '#f8d7da';
      errorTooltip.style.color = '#721c24';
      errorTooltip.style.padding = '5px';
      errorTooltip.style.border = '1px solid #f5c6cb';
      errorTooltip.style.borderRadius = '5px';
      errorTooltip.style.marginTop = '5px';
      errorTooltip.style.fontSize = '12px';
      emailField.parentNode.appendChild(errorTooltip);
      emailField.focus();
      setTimeout(() => { errorTooltip.remove(); }, 10000);
    </script>";
    exit;
  }

  $phone_number = !empty($_POST['phonenumber']) ? trim($_POST['phonenumber']) : NULL;

  $stmt = $conn->prepare("INSERT INTO user (username, user_type, email, password, phone_number) VALUES (?, 'jobseeker', ?, ?, ?)");
  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $stmt->bind_param("ssss", $username, $email, $hashed_password, $phone_number);

  if ($stmt->execute()) {
    sendRegistrationSuccessEmail($email, 'jobseeker', 'jobseekerlogin.php');
    echo "<script>alert('" . addslashes($lang['registration_successful']) . "'); window.location.href = 'jobseekerlogin.php';</script>";
    exit;
  } else {
    echo "<script>alert('Error: " . $stmt->error . "');</script>";
    exit;
  }
}

$conn->close();
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
        btn.textContent = isHidden ? '🙈' : '👁';
        btn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
      });
    });
  });
</script>