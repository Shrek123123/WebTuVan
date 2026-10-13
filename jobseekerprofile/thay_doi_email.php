<?php require_once '../config.php';
require_once __DIR__ . '/../helpers.php';
session_start();
if (!isset($_SESSION['username'])) {
    $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
    header("Location: $redirect");
    exit();
}

// Kiểm tra quyền truy cập jobseekerprofile.php?id=...
if (!isset($_SESSION['jobseeker_id'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include '../lang/' . $_SESSION['lang'] . '.php';


$jobseeker_id = $_SESSION['jobseeker_id'] ?? '';
$current_email = '';
$form_message = '';
$form_error = '';

if (!empty($jobseeker_id)) {
    $current_email_sql = "SELECT email FROM user WHERE id = ? LIMIT 1";
    $current_email_stmt = $conn->prepare($current_email_sql);
    if ($current_email_stmt) {
        $current_email_stmt->bind_param('i', $jobseeker_id);
        $current_email_stmt->execute();
        $current_email_result = $current_email_stmt->get_result();
        if ($current_email_result && $current_email_result->num_rows > 0) {
            $current_email_row = $current_email_result->fetch_assoc();
            $current_email = $current_email_row['email'] ?? '';
        }
        $current_email_stmt->close();
    }
}
$sql_user = "SELECT email, verify_email, username FROM user WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $jobseeker_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user_data = $result_user->fetch_assoc();
    $user_email = $user_data['email'];
    $is_email_verified = (bool) $user_data['verify_email'];
    $user_display_name = htmlspecialchars($user_data['username']);
}

$stmt_user->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = trim($_POST['new_email'] ?? '');

    if (empty($new_email)) {
        $form_error = $lang['email_required'] ?? 'Please enter a new email.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $form_error = $lang['invalid_email'] ?? 'Please enter a valid email.';
    } else {
        $check_sql = "SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        if ($check_stmt) {
            $check_stmt->bind_param('si', $new_email, $jobseeker_id);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $form_error = $lang['email_in_use'] ?? 'This email is already in use.';
            }
            $check_stmt->close();
        }

        if (empty($form_error)) {
            $update_sql = "UPDATE user SET email = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param('si', $new_email, $jobseeker_id);
                if ($update_stmt->execute()) {
                    if (strcasecmp($current_email, $new_email) !== 0) {
                        sendEmailChangeNotification($new_email, 'jobseeker');
                    }
                    $current_email = $new_email;
                    $form_message = $lang['email_update_success'] ?? 'Email updated successfully.';
                } else {
                    $form_error = $lang['email_update_failed'] ?? 'Unable to update email.';
                }
                $update_stmt->close();
            }
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
    <title><?= $lang['jobseekerpersonalinfo_title'] ?></title>
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

        .main_profile_pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
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

        #btn-myprofile.active {
            background: #b23b3b;
            color: #fff;
            font-weight: bold;
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

        .avatar-wrapper {
            position: relative;
            width: 100px;
            /* hoặc tuỳ bạn */
            height: 100px;
            cursor: pointer;
        }

        .avatar-circle img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
        }

        .email-form-card {
            background: #fff;
            border: 1px solid #e9e9e9;
            padding: 28px;
            margin: 24px auto;
            max-width: 680px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .email-form-card h2 {
            margin: 0 0 20px;
            color: #b23b3b;
            font-size: 28px;
        }

        .email-form-group {
            margin-bottom: 18px;
        }

        .email-form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #222;
        }

        .email-form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d6d6d6;
            background: #fff;
            box-sizing: border-box;
            font-size: 14px;
        }

        .email-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }

        .email-form-actions button,
        .email-form-actions a {
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

        .email-btn-save {
            background: #b23b3b;
            color: #fff;
        }

        .email-btn-cancel {
            background: #f3f3f3;
            color: #333;
            border: 1px solid #d9d9d9;
        }

        .email-alert {
            padding: 10px 12px;
            margin-bottom: 18px;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .email-alert.success {
            background: #eaf8ee;
            color: #1e7b4b;
            border-color: #c7ead4;
        }

        .email-alert.error {
            background: #fdecec;
            color: #aa2d2d;
            border-color: #f4c7c7;
        }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>

<body>
    <?php
    include 'header.php';
    ?>
    <!-- Top bar -->
    <div class="top-bar">
        <div class="title"><?= $lang['jobseekerprofile'] ?></div>
        <button class="profile-sidebar-toggle" type="button" aria-label="Toggle profile menu">
            <span></span><span></span><span></span>
        </button>
        
    </div>
    <!-- Main layout -->
    <div class="main">
        <!-- Sidebar -->
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


            <!-- Applicants -->
            
        </div>
        <!-- Right side content -->
        <div class="content-area">
            <div class="email-form-card">
                <h2><?= htmlspecialchars($lang['change_email_page_title'] ?? 'Change email') ?></h2>

                <?php if (!empty($form_message)): ?>
                    <div class="email-alert success"><?= htmlspecialchars($form_message) ?></div>
                <?php endif; ?>

                <?php if (!empty($form_error)): ?>
                    <div class="email-alert error"><?= htmlspecialchars($form_error) ?></div>
                <?php endif; ?>

                <form method="POST" action="thay_doi_email.php">
                    <div class="email-form-group">
                        <label for="current_email"><?= htmlspecialchars($lang['current_email_label'] ?? 'Current email') ?></label>
                        <input id="current_email" type="email" value="<?= htmlspecialchars($current_email) ?>" disabled>
                    </div>

                    <div class="email-form-group">
                        <label for="new_email"><?= htmlspecialchars($lang['new_email_label'] ?? 'New email') ?></label>
                        <input id="new_email" name="new_email" type="email" value="<?= htmlspecialchars($_POST['new_email'] ?? '') ?>" placeholder="example@email.com" required>
                    </div>

                    <div class="email-form-actions">
                        <a class="email-btn-cancel" href="ho_so_ung_vien.php"><?= htmlspecialchars($lang['back_to_profile'] ?? 'Back to profile') ?></a>
                        <button type="submit" class="email-btn-save"><?= htmlspecialchars($lang['save_changes'] ?? 'Save changes') ?></button>
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
    </script>
</body>
<footer>
    <?php
    require_once '../jobseekerprofile/footer.php';
    ?>
</footer>

<?php include 'mobile_sidebar.php'; ?>
</html>