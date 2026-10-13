<?php
session_start();
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}
include __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['employerregister_title']; ?></title>
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

        h3 {
            color: #333;
            text-align: left;
            margin-top: 25px;
            margin-bottom: 15px;
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

        input,
        select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            background: #fff;
        }

        input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
            padding: 0;
        }

        .password-toggle-wrapper {
            position: relative;
            display: block;
            width: 100%;
        }

        .password-toggle-wrapper input {
            width: 100%;
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            z-index: 1;
            width: 28px;
            height: 28px;
            padding: 0;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #666;
            cursor: pointer;
            font-size: 16px;
            line-height: 1;
            transform: translateY(-50%);
        }

        .toggle-password:hover {
            background: #f5f5f5;
            color: #d90000;
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
        .redirect-jobseeker {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .login-link a,
        .redirect-jobseeker a {
            color: #d90000;
            text-decoration: none;
            font-weight: bold;
        }

        .terms-container {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        #terms-error {
            color: red;
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }

        .error-tooltip {
            position: absolute;
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-top: 5px;
            font-size: 12px;
            z-index: 10;
            left: 0;
            right: 0;
            text-align: left;
        }

        .image-preview-box {
            display: none;
            margin-top: 12px;
            padding: 10px;
            border: 1px solid #e6d2d2;
            border-radius: 8px;
            background: #fff8f8;
        }

        .image-preview-box img {
            max-width: 180px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <?php
    include_once(__DIR__ . '/header_loginlogout/employerregister.php');
    ?>

    <div class="main-content">
        <div class="register-form-container">
            <h2><?php echo $lang['employerregister_title']; ?></h2>
            <p><?php echo $lang['employerregister_subtitle']; ?></p>
            <form action="employerregister.php" method="post" enctype="multipart/form-data">
                <h3><?php echo $lang['account_information']; ?></h3>
                <div class="form-group">
                    <label><?php echo $lang['username']; ?></label>
                    <input type="text" placeholder="<?php echo $lang['placeholder_username']; ?>" name="username"
                        required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['email']; ?></label>
                    <input type="email" placeholder="<?php echo $lang['placeholder_email']; ?>" name="email" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['phone_number_register']; ?></label>
                    <input type="tel" placeholder="<?php echo $lang['placeholder_phone_number_register']; ?>"
                        name="phonenumber">
                </div>
                <div class="form-group">
                    <label><?php echo $lang['password']; ?></label>
                    <div class="password-toggle-wrapper">
                        <input type="password" placeholder="<?php echo $lang['placeholder_password']; ?>"
                            name="password" required>
                        <button type="button" class="toggle-password" data-target="password"
                            aria-label="Toggle password visibility" aria-pressed="false">👁</button>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['reenter_password']; ?></label>
                    <div class="password-toggle-wrapper">
                        <input type="password" placeholder="<?php echo $lang['placeholder_reenter_password']; ?>"
                            name="retypepass" required>
                        <button type="button" class="toggle-password" data-target="retypepass"
                            aria-label="Toggle password visibility" aria-pressed="false">👁</button>
                    </div>
                </div>


                <h3><?php echo $lang['company_information']; ?></h3>
                <div class="form-group">
                    <label><?php echo $lang['company_name']; ?></label>
                    <input type="text" placeholder="<?php echo $lang['placeholder_company_name']; ?>" name="companyname"
                        required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['company_address']; ?></label>
                    <input type="text" placeholder="<?php echo $lang['placeholder_company_address']; ?>"
                        name="companyaddress" required>
                </div>
                <div class="form-group">
                    <label><?php echo $lang['field_of_work']; ?></label>
                    <select id="jobCategory" name="industry" required>
                        <option value=""><?php echo $lang['chon_nganh_nghe']; ?></option>
                        <option value="kinh_doanh_ban_hang"><?php echo $lang['kinh_doanh_ban_hang']; ?></option>
                        <option value="marketing_truyen_thong"><?php echo $lang['marketing_truyen_thong']; ?></option>
                        <option value="tai_chinh_ke_toan_ngan_hang"><?php echo $lang['tai_chinh_ke_toan_ngan_hang']; ?></option>
                        <option value="cong_nghe_thong_tin"><?php echo $lang['cong_nghe_thong_tin']; ?></option>
                        <option value="nhan_su"><?php echo $lang['nhan_su']; ?></option>
                        <option value="hanh_chinh_van_phong"><?php echo $lang['hanh_chinh_van_phong']; ?></option>
                        <option value="ky_thuat"><?php echo $lang['ky_thuat']; ?></option>
                        <option value="san_xuat_van_hanh_day_chuyen"><?php echo $lang['san_xuat_van_hanh_day_chuyen']; ?></option>
                        <option value="van_tai_logistics"><?php echo $lang['van_tai_logistics']; ?></option>
                        <option value="bat_dong_san"><?php echo $lang['bat_dong_san']; ?></option>
                        <option value="bao_hiem"><?php echo $lang['bao_hiem']; ?></option>
                        <option value="xay_dung_kien_truc"><?php echo $lang['xay_dung_kien_truc']; ?></option>
                        <option value="y_te_duoc_pham"><?php echo $lang['y_te_duoc_pham']; ?></option>
                        <option value="giao_duc_dao_tao"><?php echo $lang['giao_duc_dao_tao']; ?></option>
                        <option value="du_lich_khach_san_nha_hang"><?php echo $lang['du_lich_khach_san_nha_hang']; ?></option>
                        <option value="luat"><?php echo $lang['luat']; ?></option>
                        <option value="thiet_ke_nghe_thuat"><?php echo $lang['thiet_ke_nghe_thuat']; ?></option>
                        <option value="lao_dong_pho_thong"><?php echo $lang['lao_dong_pho_thong']; ?></option>
                        <option value="nong_nghiep"><?php echo $lang['nong_nghiep']; ?></option>
                        <option value="food_beverage"><?php echo $lang['food_beverage']; ?></option>
                        <option value="nha_nuoc_cong_vu"><?php echo $lang['nha_nuoc_cong_vu']; ?></option>
                        <option value="khac"><?php echo $lang['khac']; ?></option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="company_size_select"><?php echo $lang['post_job_form_company_size']; ?>:</label>
                    <select id="company_size_select" onchange="toggleCustomInput()" required>
                        <option value=""><?php echo $lang['post_job_form_company_size_select']; ?></option>
                        <option value="1-10"><?php echo $lang['post_job_form_company_size_options']['1-10']; ?></option>
                        <option value="11-50"><?php echo $lang['post_job_form_company_size_options']['11-50']; ?></option>
                        <option value="51-200"><?php echo $lang['post_job_form_company_size_options']['51-200']; ?></option>
                        <option value="201-500"><?php echo $lang['post_job_form_company_size_options']['201-500']; ?></option>
                        <option value="501-1000"><?php echo $lang['post_job_form_company_size_options']['501-1000']; ?></option>
                        <option value="1001-5000"><?php echo $lang['post_job_form_company_size_options']['1001-5000']; ?></option>
                        <option value="5000+"><?php echo $lang['post_job_form_company_size_options']['5000+']; ?></option>
                        <option value="unknown"><?php echo $lang['post_job_form_company_size_options']['unknown']; ?></option>
                        <option value="other"><?php echo $lang['post_job_form_company_size_options']['other']; ?></option>
                    </select>
                    <input type="hidden" name="company_size" id="company_size">
                    <div id="custom_input_wrapper" style="display: none; margin-top: 8px;">
                        <input type="text" id="custom_company_size" name="custom_company_size"
                            placeholder="<?php echo $lang['custom_companysize_placeholder']; ?>" title="Enter like 10-150 or 5000+">
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['company_logo_label']; ?></label>
                    <input type="file" id="companyLogoInput" name="company_logo" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <div id="companyLogoPreview" class="image-preview-box"></div>
                </div>

                <div class="form-group">
                    <label><?php echo $lang['banner_logo_label']; ?></label>
                    <input type="file" id="bannerLogoInput" name="banner_logo" accept=".jpg,.jpeg,.png,.gif,.webp">
                    <div id="bannerLogoPreview" class="image-preview-box"></div>
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
                <button type="submit" class="btn-register"><?php echo $lang['button_register']; ?></button>
            </form>

            <div class="login-link">
                <?php echo $lang['login_link_text_prefix']; ?> <a
                    href="employerlogin.php"><?php echo $lang['login_link_text']; ?></a>
            </div>
            <div class="redirect-jobseeker">
                <p><?php echo $lang['jobseeker_redirect_text_prefix']; ?> <a
                        href="jobseekerregister.php"><?php echo $lang['jobseeker_redirect_link_text']; ?></a></p>
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
    $companyname = $_POST['companyname'];
    $companyaddress = $_POST['companyaddress'];
    $industry = $_POST['industry'];
    $company_size = trim((string) ($_POST['company_size'] ?? ''));
    if (!empty($_POST['custom_company_size'])) {
        $company_size = trim((string) $_POST['custom_company_size']);
    }

    $company_logo_path = '';
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $company_logo_file = $_FILES['company_logo'];
        $company_logo_ext = strtolower(pathinfo($company_logo_file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($company_logo_ext, $allowed_ext)) {
            echo "<script>alert('" . addslashes($lang['invalid_file_format']) . "'); window.history.back();</script>";
            exit;
        }

        if ($company_logo_file['size'] > $max_size) {
            echo "<script>alert('" . addslashes($lang['file_too_large']) . "'); window.history.back();</script>";
            exit;
        }

        $company_logo_dir = __DIR__ . '/uploads/companylogo/';
        if (!is_dir($company_logo_dir)) {
            mkdir($company_logo_dir, 0777, true);
        }

        $company_logo_name = 'company_logo_' . time() . '_' . uniqid() . '.' . $company_logo_ext;
        $company_logo_target = $company_logo_dir . $company_logo_name;
        if (move_uploaded_file($company_logo_file['tmp_name'], $company_logo_target)) {
            $company_logo_path = '../uploads/companylogo/' . $company_logo_name;
        }
    }

    $banner_logo_path = '';
    if (isset($_FILES['banner_logo']) && $_FILES['banner_logo']['error'] === UPLOAD_ERR_OK) {
        $banner_logo_file = $_FILES['banner_logo'];
        $banner_logo_ext = strtolower(pathinfo($banner_logo_file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($banner_logo_ext, $allowed_ext)) {
            echo "<script>alert('" . addslashes($lang['invalid_file_format']) . "'); window.history.back();</script>";
            exit;
        }

        if ($banner_logo_file['size'] > $max_size) {
            echo "<script>alert('" . addslashes($lang['file_too_large']) . "'); window.history.back();</script>";
            exit;
        }

        $banner_logo_dir = __DIR__ . '/uploads/companylogo/';
        if (!is_dir($banner_logo_dir)) {
            mkdir($banner_logo_dir, 0777, true);
        }

        $banner_logo_name = 'company_banner_' . time() . '_' . uniqid() . '.' . $banner_logo_ext;
        $banner_logo_target = $banner_logo_dir . $banner_logo_name;
        if (move_uploaded_file($banner_logo_file['tmp_name'], $banner_logo_target)) {
            $banner_logo_path = '../uploads/companylogo/' . $banner_logo_name;
        }
    }

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($retypepass) || empty($companyname) || empty($companyaddress) || empty($industry) || empty($company_size)) {
        echo "<script>
            alert('" . addslashes($lang['all_fields_required']) . "');
            window.history.back();
            </script>";
        exit;
    }

    if ($password !== $retypepass) {
        echo "<script>
            const passwordField = document.querySelector('input[name=\"password\"]');
            const errorTooltip = document.createElement('div');
            errorTooltip.textContent = '" . addslashes($lang['passwords_do_not_match']) . "';
            errorTooltip.className = 'error-tooltip';
            passwordField.parentNode.appendChild(errorTooltip);
            passwordField.focus();

            setTimeout(() => {
                errorTooltip.remove();
            }, 3000);
            </script>";
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
            const emailField = document.querySelector('input[name=\"email\"]');
            const errorTooltip = document.createElement('div');
            errorTooltip.textContent = '" . addslashes($lang['email_already_exists']) . "';
            errorTooltip.className = 'error-tooltip';
            emailField.parentNode.appendChild(errorTooltip);
            emailField.focus();

            setTimeout(() => {
                errorTooltip.remove();
            }, 10000);
            </script>";
        exit;
    }

    $phone_number = !empty($_POST['phonenumber']) ? trim($_POST['phonenumber']) : NULL;

    // Insert new user into the database
    // Insert user first
    $stmt = $conn->prepare("INSERT INTO user (username, user_type, email, password, phone_number) VALUES (?, 'employer', ?, ?, ?)");
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $phone_number);
    $stmt->execute();

    // Get the ID of the newly created user
    $user_id = $conn->insert_id;

    // Only insert into the company table if user_type is 'employer'
    if ($user_id) {
        $stmt_company = $conn->prepare("INSERT INTO company (employer_id, company_name, company_address, industry, company_size, company_logo, banner_logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_company->bind_param("issssss", $user_id, $companyname, $companyaddress, $industry, $company_size, $company_logo_path, $banner_logo_path);
        $stmt_company->execute();
        $stmt_company->close();
    }

    if ($stmt->affected_rows > 0) {
        sendRegistrationSuccessEmail($email, 'employer', 'employerlogin.php');
        $stmt->close();
        echo "<script>
                alert('" . addslashes($lang['registration_successful']) . "');
                window.location.href = 'employerlogin.php';
            </script>";
        exit;
    } else {
        $stmt->close();
        echo "<script>
                alert('" . addslashes($lang['error_prefix'] . $stmt->error) . "');
                window.history.back();
            </script>";
        exit;
    }
}
$conn->close();
?>
<script>
    function toggleCustomInput() {
        const select = document.getElementById('company_size_select');
        const hiddenInput = document.getElementById('company_size');
        const customWrapper = document.getElementById('custom_input_wrapper');
        const customInput = document.getElementById('custom_company_size');

        if (!select || !hiddenInput || !customWrapper || !customInput) {
            return;
        }

        const selectedValue = select.value;

        if (selectedValue === 'other') {
            customWrapper.style.display = 'block';
            customInput.required = true;
            customInput.focus();
            hiddenInput.value = customInput.value.trim();
        } else {
            customWrapper.style.display = 'none';
            customInput.required = false;
            customInput.value = '';
            hiddenInput.value = selectedValue;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.querySelector('form');
        const termsCheckbox = document.querySelector('input[name="terms"]');
        const termsError = document.getElementById('terms-error');
        const companySizeSelect = document.getElementById('company_size_select');
        const companySizeHidden = document.getElementById('company_size');
        const customCompanySize = document.getElementById('custom_company_size');

        if (companySizeSelect) {
            companySizeSelect.addEventListener('change', function () {
                toggleCustomInput();
            });
        }

        if (customCompanySize && companySizeHidden) {
            customCompanySize.addEventListener('input', function () {
                if (companySizeSelect && companySizeSelect.value === 'other') {
                    companySizeHidden.value = customCompanySize.value.trim();
                }
            });
        }

        form.addEventListener('submit', function (event) {
            if (!termsCheckbox.checked) {
                event.preventDefault();
                termsError.textContent = '<?php echo addslashes($lang['terms_not_checked']); ?>';
                termsError.style.display = 'block';

                setTimeout(function () {
                    termsError.style.display = 'none';
                    termsError.textContent = '';
                }, 3000);
            } else {
                termsError.style.display = 'none';
            }

            if (companySizeSelect && companySizeSelect.value === 'other') {
                const customValue = customCompanySize ? customCompanySize.value.trim() : '';
                if (!customValue) {
                    event.preventDefault();
                    customCompanySize.focus();
                    alert('<?php echo addslashes($lang['all_fields_required']); ?>');
                    return;
                }
                if (companySizeHidden) {
                    companySizeHidden.value = customValue;
                }
            }
        });
    });
</script>
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
