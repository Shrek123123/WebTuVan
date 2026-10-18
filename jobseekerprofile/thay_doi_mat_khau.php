<?php require_once '../config.php';
session_start();
if (!isset($_SESSION['username'])) {
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit();
}

if (!isset($_SESSION['jobseeker_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

include '../lang/' . $_SESSION['lang'] . '.php';

$jobseeker_id = $_SESSION['jobseeker_id'] ?? '';
$form_message = '';
$form_error = '';
$user_display_name = $_SESSION['username'] ?? 'User';

if (!empty($jobseeker_id)) {
    $user_sql = "SELECT username, password FROM user WHERE id = ? LIMIT 1";
    $user_stmt = $conn->prepare($user_sql);
    if ($user_stmt) {
        $user_stmt->bind_param('i', $jobseeker_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result && $user_result->num_rows > 0) {
            $user_row = $user_result->fetch_assoc();
            $user_display_name = $user_row['username'] ?? $user_display_name;
        }
        $user_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $password_sql = "SELECT password FROM user WHERE id = ? LIMIT 1";
    $password_stmt = $conn->prepare($password_sql);
    $stored_hash = '';
    if ($password_stmt) {
        $password_stmt->bind_param('i', $jobseeker_id);
        $password_stmt->execute();
        $password_result = $password_stmt->get_result();
        if ($password_result && $password_result->num_rows > 0) {
            $password_row = $password_result->fetch_assoc();
            $stored_hash = $password_row['password'] ?? '';
        }
        $password_stmt->close();
    }

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $form_error = $lang['password_required'] ?? '';
    } elseif (!password_verify($current_password, $stored_hash)) {
        $form_error = $lang['current_password_incorrect'] ?? '';
    } elseif (strlen($new_password) < 6) {
        $form_error = $lang['password_min_length'] ?? '';
    } elseif ($new_password !== $confirm_password) {
        $form_error = $lang['password_mismatch'] ?? '';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE user SET password = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param('si', $new_hash, $jobseeker_id);
            if ($update_stmt->execute()) {
                $form_message = $lang['password_update_success'] ?? '';
            } else {
                $form_error = $lang['password_update_failed'] ?? '';
            }
            $update_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <title><?= $lang['change_password_page_title'] ?></title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .top-bar {
            background-color: #943737;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            padding: 10px 20px;
        }

        .top-bar .title {
            font-weight: bold;
            font-size: 15px;
        }

        .top-bar .company {
            display: flex;
            align-items: center;
        }

        .top-bar .company img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .top-bar .company-info {
            text-align: right;
            font-size: 12px;
        }

        .top-bar .status {
            font-size: 11px;
            color: #ffdddd;
        }

        .main {
            display: flex;
        }

        .sidebar {
            width: 160px;
            border-right: 1px solid #ccc;
            padding: 10px;
            background: #fff;
        }

        .sidebar h3 {
            margin-top: 20px;
            font-size: 14px;
        }

        .sidebar button {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            border: 1px solid black;
            background-color: white;
            cursor: pointer;
        }

        .sidebar button:hover {
            background-color: #f2f2f2;
        }

        .applicant-card {
            background-color: #b76d6d;
            color: white;
            border-radius: 6px;
            padding: 6px;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .applicant-card img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .applicant-info {
            font-size: 13px;
        }

        .status-viewed {
            font-size: 11px;
            color: #b2ffb2;
        }

        .status-new {
            font-size: 11px;
            color: #cce0ff;
        }

        .view-more {
            font-size: 12px;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
            border-radius: 4px;
        }

        .content-area {
            flex-grow: 1;
            background-color: #f3f3f3;
        }

        .change-info-wrap {
            position: relative;
            width: auto;
            display: block;
        }

        .change-info-menu {
            position: absolute;
            top: 0;
            left: calc(100% + 10px);
            display: none;
            flex-direction: column;
            gap: 8px;
            min-width: 190px;
            padding: 10px;
            background: #fff;
            border: 1px solid #e8dede;
            border-radius: 0;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
            z-index: 50;
        }

        .change-info-menu.show {
            display: flex;
        }

        .change-info-option {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #f0d8d8;
            background: linear-gradient(180deg, #ffffff 0%, #fff8f8 100%);
            color: #733333;
            border-radius: 0;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .change-info-option:hover {
            background: #b23b3b;
            color: #fff;
            border-color: #b23b3b;
        }

        #btn-changeinfo {
            border-radius: 0;
            transition: all 0.2s ease;
        }

        #btn-changeinfo.active {
            background: #b23b3b;
            color: #fff;
            border-color: #b23b3b;
            font-weight: bold;
            box-shadow: 0 6px 16px rgba(178, 59, 59, 0.2);
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .main {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                box-sizing: border-box;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }

            .change-info-menu {
                position: static;
                width: 100%;
                margin-top: 5px;
                box-shadow: none;
                border: 1px solid #eee;
            }
        }

        .password-form-card {
            background: #fff;
            border: 1px solid #e9e9e9;
            padding: 28px;
            margin: 24px auto;
            max-width: 680px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .password-form-card h2 {
            margin: 0 0 20px;
            color: #b23b3b;
            font-size: 28px;
        }

        .password-form-group {
            margin-bottom: 18px;
        }

        .password-form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }

        .password-form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d6d6d6;
            background: #fff;
            box-sizing: border-box;
            font-size: 14px;
        }

        .password-toggle-wrapper {
            position: relative;
            width: 100%;
        }

        .password-toggle-wrapper input {
            width: 100%;
            padding-right: 42px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 18px;
            color: #666;
            padding: 0;
        }

        .password-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        .password-form-actions button,
        .password-form-actions a {
            padding: 10px 18px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .password-btn-save {
            background: #b23b3b;
            color: #fff;
        }

        .password-btn-cancel {
            background: #f3f3f3;
            color: #333;
            border: 1px solid #d9d9d9;
        }

        .password-alert {
            padding: 10px 12px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .password-alert.success {
            background: #eaf8ee;
            color: #1e7b4b;
            border-color: #c7ead4;
        }

        .password-alert.error {
            background: #fdecec;
            color: #aa2d2d;
            border-color: #f4c7c7;
        }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="top-bar">
        <div class="title"><?= $lang['jobseekerprofile'] ?></div>
        <button class="profile-sidebar-toggle" type="button" aria-label="Toggle profile menu">
            <span></span><span></span><span></span>
        </button>
        
    </div>

    <div class="main">
        <div class="sidebar">
            <button id="btn-myprofile" onclick="window.location.href='ho_so_ung_vien.php'"><?= $lang['btn_myprofile'] ?></button>
            <button id="btn-myapplications" onclick="window.location.href='don_ung_tuyen.php'"><?= $lang['btn_myapplications'] ?></button>
            <button id="btn-savedjobs" onclick="window.location.href='cong_viec_da_luu.php'"><?= $lang['btn_savedjobs'] ?></button>
            <button id="btn-followed-companies" onclick="window.location.href='followed_company.php'"><?= $lang['btn_interested_jobs'] ?></button>
            <div class="change-info-wrap">
                <button id="btn-changeinfo" class="active" type="button"><?= $lang['btn_changeinfo'] ?></button>
                <div id="changeInfoMenu" class="change-info-menu" aria-label="Change info actions">
                    <button type="button" class="change-info-option" data-href="thay_doi_email.php"><?= $lang['change_email_action'] ?></button>
                    <button type="button" class="change-info-option" data-href="thay_doi_mat_khau.php"><?= $lang['change_password_action'] ?></button>
                </div>
            </div>
        </div>

        <div class="content-area">
            <div class="password-form-card">
                <h2><?= htmlspecialchars($lang['change_password_page_title']) ?></h2>

                <?php if (!empty($form_message)): ?>
                    <div class="password-alert success"><?= htmlspecialchars($form_message) ?></div>
                <?php endif; ?>

                <?php if (!empty($form_error)): ?>
                    <div class="password-alert error"><?= htmlspecialchars($form_error) ?></div>
                <?php endif; ?>

                <form method="POST" action="thay_doi_mat_khau.php">
                    <div class="password-form-group">
                        <label for="current_password"><?= htmlspecialchars($lang['current_password_label']) ?></label>
                        <div class="password-toggle-wrapper">
                            <input id="current_password" name="current_password" type="password" required>
                            <button type="button" class="toggle-password" data-target="current_password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
                        </div>
                    </div>

                    <div class="password-form-group">
                        <label for="new_password"><?= htmlspecialchars($lang['new_password_label']) ?></label>
                        <div class="password-toggle-wrapper">
                            <input id="new_password" name="new_password" type="password" required>
                            <button type="button" class="toggle-password" data-target="new_password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
                        </div>
                    </div>

                    <div class="password-form-group">
                        <label for="confirm_password"><?= htmlspecialchars($lang['confirm_password_label']) ?></label>
                        <div class="password-toggle-wrapper">
                            <input id="confirm_password" name="confirm_password" type="password" required>
                            <button type="button" class="toggle-password" data-target="confirm_password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button>
                        </div>
                    </div>

                    <div class="password-form-actions">
                        <a class="password-btn-cancel" href="ho_so_ung_vien.php"><?= htmlspecialchars($lang['back_to_profile']) ?></a>
                        <button type="submit" class="password-btn-save"><?= htmlspecialchars($lang['save_changes']) ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnChangeInfo = document.getElementById('btn-changeinfo');
            const menu = document.getElementById('changeInfoMenu');
            const keepActive = window.location.pathname.toLowerCase().includes('thay_doi_email.php') || window.location.pathname.toLowerCase().includes('thay_doi_mat_khau.php');
            if (btnChangeInfo && keepActive) {
                btnChangeInfo.classList.add('active');
            }
            if (!btnChangeInfo || !menu) return;

            btnChangeInfo.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = menu.classList.contains('show');
                document.querySelectorAll('.change-info-menu').forEach(item => item.classList.remove('show'));
                document.querySelectorAll('#btn-changeinfo').forEach(item => item.classList.remove('active'));
                if (!isOpen) {
                    menu.classList.add('show');
                    btnChangeInfo.classList.add('active');
                }
            });

            menu.querySelectorAll('.change-info-option').forEach((option) => {
                option.addEventListener('click', function () {
                    window.location.href = this.dataset.href;
                });
            });

            document.addEventListener('click', function (event) {
                if (!keepActive && !btnChangeInfo.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.remove('show');
                    btnChangeInfo.classList.remove('active');
                }
            });
        });

        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(this.dataset.target);
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>

<footer>
    <?php require_once '../jobseekerprofile/footer.php'; ?>
</footer>

<?php include 'mobile_sidebar.php'; ?>
</html>
