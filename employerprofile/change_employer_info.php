<?php
session_start();
if (!isset($_SESSION['usernameemployer'])) {
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'employerpage.php';
    header("Location: $redirect");
    exit();
}

// Kiểm tra quyền truy cập employerprofile.php
if (!isset($_SESSION['employerid'])) {
    header("Location: employerpage.php");
    exit();
}
?>
<?php
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include '../lang/' . $_SESSION['lang'] . '.php';

require_once '../config.php';
require_once __DIR__ . '/../helpers.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$employerid = $_SESSION['employerid'] ?? 0;
$employername = $_SESSION['usernameemployer'] ?? 'Employer';

$current_profile = [
    'username' => '',
    'phone_number' => '',
    'email' => ''
];
$form_error = '';
$form_success = '';

if ($employerid > 0) {
    $stmt_profile = $conn->prepare("SELECT username, phone_number, email FROM user WHERE id = ?");
    if ($stmt_profile) {
        $stmt_profile->bind_param("i", $employerid);
        $stmt_profile->execute();
        $result_profile = $stmt_profile->get_result();
        if ($row_profile = $result_profile->fetch_assoc()) {
            $current_profile['username'] = $row_profile['username'] ?? '';
            $current_profile['phone_number'] = $row_profile['phone_number'] ?? '';
            $current_profile['email'] = $row_profile['email'] ?? '';
        }
        $stmt_profile->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_employer_profile'])) {
    $new_username = trim($_POST['username'] ?? '');
    $new_phone = trim($_POST['phone_number'] ?? '');
    $email_choice = $_POST['email_choice'] ?? 'same';
    $entered_new_email = trim($_POST['new_email'] ?? '');
    $old_email = trim($_POST['old_email'] ?? '');
    $final_email = $current_profile['email'];

    if (empty($new_username)) {
        $form_error = 'Vui lòng nhập username.';
    } elseif (empty($old_email)) {
        $form_error = 'Vui lòng nhập email cũ để xác nhận.';
    } elseif (strtolower($old_email) !== strtolower($current_profile['email'])) {
        $form_error = 'Email cũ không đúng. Vui lòng nhập đúng email hiện tại.';
    } elseif (!empty($new_phone) && !preg_match('/^(0|\+84)(3|5|7|8|9)\d{8,9}$/', $new_phone)) {
        $form_error = 'Số điện thoại không hợp lệ. Ví dụ: 0901234567 hoặc +84901234567.';
    } elseif ($email_choice === 'new') {
        if (empty($entered_new_email)) {
            $form_error = 'Vui lòng nhập email mới.';
        } elseif (!filter_var($entered_new_email, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'Email mới không hợp lệ.';
        } else {
            $final_email = $entered_new_email;
            $check_email = $conn->prepare("SELECT id FROM user WHERE email = ? AND id != ? LIMIT 1");
            if ($check_email) {
                $check_email->bind_param("si", $final_email, $employerid);
                $check_email->execute();
                $check_email->store_result();
                if ($check_email->num_rows > 0) {
                    $form_error = 'Email mới đã được sử dụng bởi tài khoản khác.';
                }
                $check_email->close();
            }
        }
    } else {
        $final_email = $current_profile['email'];
    }

    if (empty($form_error) && $email_choice === 'new' && empty($final_email)) {
        $form_error = 'Vui lòng chọn hoặc nhập email mới.';
    }

    if (empty($form_error)) {
        $update_sql = "UPDATE user SET username = ?, phone_number = ?, email = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        if ($stmt_update) {
            $stmt_update->bind_param("sssi", $new_username, $new_phone, $final_email, $employerid);
            if ($stmt_update->execute()) {
                if ($email_choice === 'new' && strcasecmp($current_profile['email'], $final_email) !== 0) {
                    sendEmailChangeNotification($final_email, 'employer');
                }
                $current_profile['username'] = $new_username;
                $current_profile['phone_number'] = $new_phone;
                $current_profile['email'] = $final_email;
                $_SESSION['usernameemployer'] = $new_username;
                $employername = $new_username;
                $form_success = 'Cập nhật thông tin thành công.';
            } else {
                $form_error = 'Lỗi khi cập nhật thông tin: ' . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            $form_error = 'Không thể chuẩn bị câu lệnh cập nhật: ' . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['employerprofile_postajob'] ?></title>
    <style>
        .email-choice-wrap {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .email-choice-wrap .hidden-email-input {
            display: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-info span {
            font-size: 16px;
            font-weight: bold;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
            cursor: pointer;
            margin-right: 50px;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            /* nằm ngay dưới .user-info */
            left: 0;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            flex-direction: column;
            min-width: 100px;
            z-index: 1000;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
            border-bottom: 1px solid #eee;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .user-dropdown:hover .dropdown-menu {
            display: flex;
        }


        body {
            margin: 0;
            padding: 0;
            font-family: Roboto, sans-serif;
        }

        .header {
            display: flex;
            align-items: center;
            background-color: white;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            width: 100%;
            box-sizing: border-box;
        }

        .header nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .header a:hover,
        .header .guest-options a:hover,
        .header .dropdown-menu a:hover {
            color: #d70018 !important;
            transition: color 0.1s;
        }

        .navbar {
            display: flex;
            align-items: center;
        }



        .guest-options {
            display: flex;
            gap: 15px;
            margin-left: auto;
            margin-right: 50px;
        }

        .btn-login,
        .btn-register {
            text-decoration: none;
        }

        .footer-new {
            background-color: #B73C3C;
            color: white;
            font-family: Arial, sans-serif;

        }

        .hotline-section {
            display: flex;
            justify-content: space-around;
            background-color: #fff;
            padding: 30px 10px;
            border: 1px solid #ccc;
            border-bottom: none;
        }

        .hotline-box {
            flex: 1;
            padding: 0 20px;
            text-align: center;
            border-right: 1px solid #ddd;
        }

        .hotline-box:last-child {
            border-right: none;
        }

        .hotline-box h3 {
            color: #D91616;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .hotline-item {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .hotline-item i {
            font-size: 24px;
            color: #333;
            margin-right: 10px;
        }

        .hotline-item div {
            text-align: left;
            font-size: 14px;
            color: #D91616;
        }

        .hotline-btn {
            background-color: #D91616;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
        }

        .hotline-btn:hover {
            background-color: #880000;
        }



        .footer-container {
            display: flex;
            justify-content: space-between;
            max-width: 100%;
            flex-wrap: wrap;
            margin: auto;
        }

        .footer-column {
            flex: 1 1 30%;
            min-width: 400px;
            margin: 10px 0px 50px 40px;

        }

        .footer-column h4 {
            margin-bottom: 30px;
            font-weight: bold;
        }

        .footer-column p,
        .footer-column ul {
            font-size: 14px;
            line-height: 1.6;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 8px;
        }

        .social-icons {
            margin-bottom: 12px;
        }

        .social-icons i {
            font-size: 20px;
            margin-right: 10px;
            border-radius: 10px;
            color: white;
        }


        .social-icons .facebook {
            background-color: #1877f2;
            padding: 10px;
        }

        .social-icons .tiktok {
            background-color: #000;
            padding: 10px;
        }

        .social-icons .telegram {
            background-color: #24A1DE;
            padding: 10px;
        }

        .social-icons .instagram {
            background: radial-gradient(circle at 30% 107%,
                    #fdf497 0%,
                    #fdf497 5%,
                    #fd5949 45%,
                    #d6249f 60%,
                    #285AEB 90%);
            display: inline-block;
            padding: 10px;
        }

        .qr-and-apps {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .qr-and-apps img {
            height: 100px;
        }

        .app-links img {
            width: 130px;
            display: block;
            height: 60px;
        }

        /**
        body CSS
         */
        /* ===== DASHBOARD BASE ===== */
        .employer-dashboard {
            font-family: Arial, sans-serif;
            background: #f9f9f9;
        }

        /* ===== TOP BAR ===== */
        .dashboard-topbar {
            background-color: #943737;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            padding: 10px 20px;
        }

        .dashboard-title {
            font-weight: bold;
            font-size: 15px;
        }

        .dashboard-company {
            display: flex;
            align-items: center;
        }

        .dashboard-company img {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .dashboard-company-info {
            text-align: right;
            font-size: 12px;
        }

        .company-status {
            font-size: 11px;
            color: #ffdddd;
        }

        /* ===== MAIN LAYOUT ===== */
        .dashboard-main {
            display: flex;
        }

        /* ===== SIDEBAR ===== */
        .dashboard-sidebar {
            width: 160px;
            border-right: 1px solid #ccc;
            padding: 10px;
            background: #fff;
        }

        .dashboard-sidebar h3 {
            margin-top: 20px;
            font-size: 14px;
        }

        .dashboard-sidebar button {
            width: 100%;
            padding: 6px;
            margin-bottom: 10px;
            font-size: 13px;
            border: 1px solid black;
            background: white;
            cursor: pointer;
        }

        .dashboard-sidebar button:hover {
            background: #f2f2f2;
        }

        .dashboard-sidebar button.active {
            background: #b23b3b;
            color: #fff;
            border-color: #b23b3b;
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
            display: none !important;
            visibility: hidden;
            opacity: 0;
            pointer-events: none;
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
            display: flex !important;
            visibility: visible;
            opacity: 1;
            pointer-events: auto;
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

        /* ===== APPLICANT CARD ===== */
        .applicant-card {
            background: #b76d6d;
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

        .status-new {
            font-size: 11px;
            color: #17a2b8;
            font-weight: bold;
        }

        .status-viewed {
            font-size: 11px;
            color: #28a745;
            font-weight: bold;
        }

        .status-hired {
            font-size: 11px;
            color: #20c997;
            font-weight: bold;
        }

        .status-interviewed {
            font-size: 11px;
            color: #6f42c1;
            font-weight: bold;
        }

        .status-rejected {
            font-size: 11px;
            color: #dc3545;
            font-weight: bold;
        }

        .status-default {
            font-size: 11px;
            color: #6c757d;
            font-weight: bold;
        }

        .view-more {
            font-size: 12px;
            text-align: center;
            background: #fff;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: underline;
        }

        /* ===== CONTENT ===== */
        .dashboard-content {
            flex: 1;
            background: #fff;
            padding: 20px;
        }

        /* button post job */
        #btn-postjob.active {
            background: #b23b3b;
            color: #fff;
            font-weight: bold;
        }

        /* ===== POSTING OPTIONS BUTTONS===== */
        .posting-options {
            display: grid;
            grid-template-columns: repeat(2, 260px);
            /* 2 cột cố định */
            justify-content: center;
            /* căn giữa toàn bộ khối */
            gap: 20px;
        }

        .post-btn {
            padding: 14px 18px;
            border: none;
            border-radius: 14px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        /* Màu */
        .post-btn.blue {
            background: linear-gradient(135deg, #2563eb, #1e40af);
        }

        .post-btn.teal {
            background: linear-gradient(135deg, #0d9488, #0f766e);
        }

        .post-btn.green {
            background: linear-gradient(135deg, #16a34a, #166534);
        }

        .post-btn.indigo {
            background: linear-gradient(135deg, #4f46e5, #3730a3);
        }

        .post-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
        }

        @media (max-width: 768px) {
            .dashboard-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .dashboard-main {
                flex-direction: column;
            }

            .dashboard-sidebar {
                width: 100%;
                box-sizing: border-box;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }

            .dashboard-sidebar h3 {
                margin-top: 15px;
            }

            .dashboard-content {
                padding: 15px !important;
            }

            .change-info-menu {
                position: static;
                width: 100%;
                margin-top: 5px;
                box-shadow: none;
                border: 1px solid #eee;
            }
            
            .footer-column {
                min-width: 100%;
                margin: 20px 0;
            }
            
            .hotline-section {
                flex-direction: column;
            }
            
            .hotline-box {
                border-right: none;
                border-bottom: 1px solid #ddd;
                padding: 20px 0;
            }

            .posting-options {
                grid-template-columns: 1fr !important;
            }
        }
        .applicant-preview-heading a {
            display: inline-flex; align-items: center; gap: 6px; padding: 10px 14px;
            border-radius: 8px; background: #fff4f4; color: #b23b3b !important;
            text-decoration: none !important; cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }
        .applicant-preview-heading a:hover,
        .applicant-preview-heading a:focus-visible {
            background: #b23b3b; color: #fff !important; transform: translateY(-1px);
        }
    </style>
</head>


<body>
    <?php include 'header.php'; ?>
    <!-- #region -->
    <div class="employer-dashboard">

        <!-- TOP BAR -->
        <div class="dashboard-topbar">
            <div class="dashboard-title">
                <?= $lang['employerprofile'] ?>
            </div>

        </div>

        <!-- MAIN -->
        <div class="dashboard-main has-applicant-preview">

            <!-- SIDEBAR -->
            <div class="dashboard-sidebar">
                <button id="btn-postjob"
                    onclick="window.location.href='add_job_post.php'" class="<?= ($currentPage == 'add_job_post.php') ? 'active' : '' ?>"><?= $lang['employerprofile_postajob'] ?></button>
                <button onclick="window.location.href='my_job_posts.php'" class="<?= ($currentPage == 'my_job_posts.php') ? 'active' : '' ?>"><?= $lang['employerprofile_myjobposts'] ?></button>
                <button onclick="window.location.href='company_profile.php'" class="<?= ($currentPage == 'company_profile.php') ? 'active' : '' ?>"><?= $lang['employerprofile_companyprofile'] ?></button>
                <div class="change-info-wrap">
                    <button id="btn-changeinfo" type="button" class="<?= in_array($currentPage, ['change_employer_info.php', 'change_password.php', 'change_profile_pic.php'], true) ? 'active' : '' ?>"><?= $lang['employerprofile_changeinfo'] ?></button>
                    <div id="changeInfoMenu" class="change-info-menu" aria-label="Change info actions">
                        <button type="button" class="change-info-option" data-href="change_employer_info.php"><?= $lang['change_employer_general_info_action'] ?></button>
                        <button type="button" class="change-info-option" data-href="change_password.php"><?= $lang['change_employer_password_action'] ?></button>
                        <button type="button" class="change-info-option" data-href="change_profile_pic.php"><?= $lang['change_employer_avatar_action'] ?></button>
                    </div>
                </div>

                <?php
                // Tính tổng số đơn ứng tuyển cho employer hiện tại
                $total_applications = 0;
                $employerid = $_SESSION['employerid'] ?? 0;
                if ($employerid > 0 && isset($conn) && $conn->ping()) {
                    $sql_total = "SELECT COUNT(a.id) AS total FROM application a JOIN hoa_giai_vien j ON a.job_id = j.id WHERE j.posted_by_employer_id = ? AND (a.hidden_from_employer = 0 OR a.hidden_from_employer IS NULL)";
                    $stmt_total = $conn->prepare($sql_total);
                    $stmt_total->bind_param("i", $employerid);
                    $stmt_total->execute();
                    $result_total = $stmt_total->get_result();
                    if ($row_total = $result_total->fetch_assoc()) {
                        $total_applications = $row_total['total'];
                    }
                    $stmt_total->close();
                }
                ?>

                <h3 class="applicant-preview-heading"><a href="view_more_list_application.php" style="color: inherit; text-decoration: none;"><?= $lang['employerprofile_applicantprofiles'] ?> <span style="color: #b23b3b; font-weight: bold;">(<?php echo $total_applications; ?>)</span></a></h3>

                <?php
                // Lấy 2 đơn ứng tuyển đầu tiên từ database
                $applications = [];
                $employerid = $_SESSION['employerid'] ?? 0;
                if ($employerid > 0 && isset($conn) && $conn->ping()) {
                    $sql_app = "SELECT a.fullname, a.status, j.job_title FROM application a JOIN hoa_giai_vien j ON a.job_id = j.id WHERE j.posted_by_employer_id = ? AND (a.hidden_from_employer = 0 OR a.hidden_from_employer IS NULL) ORDER BY a.id DESC LIMIT 2";
                    $stmt_app = $conn->prepare($sql_app);
                    $stmt_app->bind_param("i", $employerid);
                    $stmt_app->execute();
                    $result_app = $stmt_app->get_result();
                    while ($row_app = $result_app->fetch_assoc()) {
                        $applications[] = $row_app;
                    }
                    $stmt_app->close();
                }
                ?>

                <!-- Applicant preview (dynamic) -->
                <?php foreach ($applications as $app): 
                    $status_raw = strtolower($app['status']);
                    
                    // Map status với CSS class và language key
                    $status_mapping = [
                        'pending' => ['class' => 'status-new', 'lang_key' => 'application_pending'],
                        'viewed' => ['class' => 'status-viewed', 'lang_key' => 'application_viewed'],
                        'hired' => ['class' => 'status-hired', 'lang_key' => 'application_hired'],
                        'interviewed' => ['class' => 'status-interviewed', 'lang_key' => 'application_interviewed'],
                        'rejected' => ['class' => 'status-rejected', 'lang_key' => 'application_rejected'],
                    ];
                    
                    $config = $status_mapping[$status_raw] ?? ['class' => 'status-default', 'lang_key' => $status_raw];
                    $status_class = $config['class'];
                    $status_icon = isset($lang[$config['lang_key']]) ? $lang[$config['lang_key']] : ucfirst($status_raw);
                    $job_title = (string) ($app['job_title'] ?? '');
                    if (mb_strlen($job_title, 'UTF-8') > 50) {
                        $job_title = mb_substr($job_title, 0, 47, 'UTF-8') . '...';
                    }
                ?>
                    <div class="applicant-card">
                        <div class="applicant-info">
                            <?= htmlspecialchars($app['fullname'], ENT_QUOTES, 'UTF-8') ?><br>
                            <span class="applicant-job-title"><?= htmlspecialchars($lang['ung_tuyen_viec'] ?? 'Applied for', ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars($job_title, ENT_QUOTES, 'UTF-8') ?></span><br>
                            <span class="<?= $status_class ?>"><?= $status_icon ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="view-more" onclick="window.location.href='view_more_list_application.php'" style="cursor: pointer;"><?= $lang['view_more'] ?></div>
            </div>

            <!-- CONTENT -->
            <div class="dashboard-content">
                <div style="max-width: 700px; margin: 0 auto; padding: 20px; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
                    <h2 style="margin-bottom: 20px; color: #333;"><?= $lang['edit_account_title'] ?? 'Edit account information' ?></h2>

                    <?php if (!empty($form_error)): ?>
                        <div style="padding: 12px 14px; background: #fff1f0; border: 1px solid #f8c4c4; color: #b42318; border-radius: 8px; margin-bottom: 18px;">
                            <?= htmlspecialchars($form_error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($form_success)): ?>
                        <div style="padding: 12px 14px; background: #edfdf3; border: 1px solid #bfeec9; color: #117a37; border-radius: 8px; margin-bottom: 18px;">
                            <?= htmlspecialchars($form_success) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="change_employer_info.php" style="display: flex; flex-direction: column; gap: 18px;">
                        <div>
                            <label for="username" style="display: block; margin-bottom: 8px; font-weight: 600;"><?= $lang['username'] ?? 'Username' ?></label>
                            <input type="text" id="username" name="username" required value="<?= htmlspecialchars($current_profile['username']) ?>" placeholder="<?= $lang['placeholder_username'] ?? 'Enter username' ?>" style="width: 100%; padding: 11px 12px; border: 1px solid #d9d9d9; border-radius: 8px; box-sizing: border-box;">
                        </div>

                        <div>
                            <label for="old_email" style="display: block; margin-bottom: 8px; font-weight: 600;"><?= $lang['old_email'] ?? 'Old email' ?></label>
                            <input type="email" id="old_email" name="old_email" required value="" placeholder="<?= $lang['placeholder_email'] ?? 'Enter your current email' ?>" style="width: 100%; padding: 11px 12px; border: 1px solid #d9d9d9; border-radius: 8px; box-sizing: border-box;">
                        </div>

                        <div class="email-choice-wrap">
                            <div>
                                <label for="email_choice" style="display: block; margin-bottom: 8px; font-weight: 600;"><?= $lang['new_email'] ?? 'New email' ?></label>
                                <select id="email_choice" name="email_choice" style="width: 100%; padding: 11px 12px; border: 1px solid #d9d9d9; border-radius: 8px; box-sizing: border-box;">
                                    <option value="same" selected><?= $lang['same_as_old_email'] ?? 'Same as the old email address' ?></option>
                                    <option value="new"><?= $lang['change_email_address'] ?? 'Change email address' ?></option>
                                </select>
                            </div>

                            <div id="new_email_wrap" class="hidden-email-input">
                                <label for="new_email" style="display: block; margin-bottom: 8px; font-weight: 600;">
                                    <?= $lang['enter_new_email'] ?? 'Enter new email' ?>
                                </label>
                                <input type="email" id="new_email" name="new_email" value="" placeholder="<?= $lang['placeholder_email'] ?? 'Enter your new email' ?>" style="width: 100%; padding: 11px 12px; border: 1px solid #d9d9d9; border-radius: 8px; box-sizing: border-box;">
                            </div>
                        </div>

                        <div>
                            <label for="phone_number" style="display: block; margin-bottom: 8px; font-weight: 600;"><?= $lang['phone_number'] ?? 'Phone number' ?></label>
                            <input type="text" id="phone_number" name="phone_number" value="<?= htmlspecialchars($current_profile['phone_number']) ?>" placeholder="<?= $lang['enter_phone'] ?? 'Example: 0901234567' ?>" style="width: 100%; padding: 11px 12px; border: 1px solid #d9d9d9; border-radius: 8px; box-sizing: border-box;">
                        </div>

                        <div>
                            <button type="submit" name="update_employer_profile" value="1" style="background: #b23b3b; color: white; border: none; border-radius: 8px; padding: 12px 20px; font-weight: 600; cursor: pointer;"><?= $lang['update_profile'] ?? 'Update' ?></button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const emailChoice = document.getElementById('email_choice');
                            const emailWrap = document.getElementById('new_email_wrap');
                            const newEmailInput = document.getElementById('new_email');

                            function toggleNewEmailInput() {
                                const show = emailChoice && emailChoice.value === 'new';
                                if (emailWrap) {
                                    emailWrap.style.display = show ? 'block' : 'none';
                                }
                                if (newEmailInput) {
                                    newEmailInput.required = show;
                                    if (!show) {
                                        newEmailInput.value = '';
                                    }
                                }
                            }

                            if (emailChoice) {
                                emailChoice.addEventListener('change', toggleNewEmailInput);
                                toggleNewEmailInput();
                            }
                        });
                    </script>
                </div>
            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btnChangeInfo = document.getElementById('btn-changeinfo');
            const menu = document.getElementById('changeInfoMenu');
            if (!btnChangeInfo || !menu) return;

            const activePages = ['change_employer_info.php', 'change_password.php', 'change_profile_pic.php'];
            const currentPage = window.location.pathname.split('/').pop();
            const isActivePage = activePages.includes(currentPage);

            menu.classList.remove('show');
            menu.style.display = 'none';
            btnChangeInfo.classList.toggle('active', isActivePage);

            btnChangeInfo.addEventListener('click', function (event) {
                event.stopPropagation();
                const isOpen = menu.style.display !== 'none';
                menu.style.display = isOpen ? 'none' : 'flex';
                menu.classList.toggle('show', !isOpen);
                btnChangeInfo.classList.toggle('active', !isOpen || isActivePage);
            });

            menu.querySelectorAll('.change-info-option').forEach((option) => {
                option.addEventListener('click', function () {
                    window.location.href = this.dataset.href;
                });
            });

            document.addEventListener('click', function (event) {
                if (!btnChangeInfo.contains(event.target) && !menu.contains(event.target)) {
                    menu.style.display = 'none';
                    menu.classList.remove('show');
                    btnChangeInfo.classList.toggle('active', isActivePage);
                }
            });
        });
    </script>
</body>

<?php include 'footer.php' ?>

</html>