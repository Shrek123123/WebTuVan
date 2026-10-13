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
include '../helpers.php';
require_once '../config.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$employername = $_SESSION['usernameemployer'] ?? 'Employer';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['employerprofile_postajob'] ?></title>
    <style>
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

        .application-status-actions {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px;
            width: 100%;
        }

        .application-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            max-width: 100%;
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

        .cover-letter-trigger {
            display: inline-flex;
            align-items: center;
            margin: 0 0 10px;
            padding: 0;
            border: 0;
            background: transparent;
            color: #b23b3b;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: underline;
            cursor: pointer;
        }

        .cover-letter-trigger:focus-visible,
        .cover-letter-modal__close:focus-visible {
            outline: 3px solid rgba(178, 59, 59, 0.3);
            outline-offset: 2px;
        }

        .cover-letter-modal[hidden] {
            display: none;
        }

        .cover-letter-modal {
            position: fixed;
            inset: 0;
            z-index: 10003;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(0, 0, 0, 0.48);
        }

        .cover-letter-modal__dialog {
            width: min(560px, 100%);
            max-height: min(70vh, 560px);
            overflow: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.24);
        }

        .cover-letter-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
        }

        .cover-letter-modal__title {
            margin: 0;
            color: #b23b3b;
            font-size: 18px;
        }

        .cover-letter-modal__close {
            border: 0;
            background: transparent;
            color: #777;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .cover-letter-modal__content {
            margin: 0;
            padding: 20px;
            color: #444;
            font-size: 14px;
            line-height: 1.6;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
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

        /* ===== APPLICATION CARD ===== */
        .application-card {
            display: flex;
            flex-direction: column;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        /* ===== APPLICATION GRID ===== */
        .application-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        /* ===== JOB HEADER ===== */
        .job-header {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #b23b3b;
            margin-bottom: 20px;
        }

        .job-header h3 {
            color: #b23b3b;
            margin: 0;
            font-size: 16px;
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

            .application-grid {
                grid-template-columns: 1fr !important;
            }

            .application-card {
                padding: 15px !important;
                min-width: 0;
                box-sizing: border-box;
            }

            /* Target the flex containers inside the card */
            .application-card > div:nth-child(1),
            .application-card > div:nth-child(5) {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 10px !important;
            }
            
            .application-actions {
                width: auto;
                max-width: 100%;
            }

            .application-actions button,
            .application-actions a {
                width: auto;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                text-align: center;
                margin-right: 0 !important;
            }

            .cover-letter-trigger {
                max-width: 100%;
                box-sizing: border-box;
                white-space: normal;
                overflow-wrap: anywhere;
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

            .application-grid {
                grid-template-columns: 1fr !important;
            }

            .posting-options {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            #manageApplicationModal {
                align-items: center !important;
                justify-content: center !important;
                padding: 16px;
                box-sizing: border-box;
                z-index: 3000 !important;
            }

            #manageApplicationModal[style*="display: block"] {
                display: flex !important;
            }

            #manageApplicationModal .modal-content {
                width: 100% !important;
                max-width: 500px;
                max-height: calc(100vh - 32px);
                margin: 0 !important;
                overflow-y: auto;
                box-sizing: border-box;
            }

            #hideApplicationModal {
                align-items: center !important;
                justify-content: center !important;
                padding: 16px;
                box-sizing: border-box;
                z-index: 3000 !important;
            }

            #hideApplicationModal[style*="display: block"] {
                display: flex !important;
            }

            #hideApplicationModal .modal-content {
                width: 100% !important;
                max-width: 400px;
                max-height: calc(100vh - 32px);
                margin: 0 !important;
                overflow-y: auto;
                box-sizing: border-box;
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
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
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
                <!-- Content load sau -->
                <div style="padding: 20px;">
                    <h2 style="color: #b23b3b; margin-bottom: 20px;"><?= $lang['all_applications'] ?? 'All Applications' ?></h2>
                    
                    <?php
                    $employerid = $_SESSION['employerid'] ?? 0;
                    if ($employerid > 0 && isset($conn) && $conn->ping()) {
                        // Lấy tất cả job của employer
                        $sql_jobs = "SELECT id, job_title, job_post_number_by_employer FROM hoa_giai_vien WHERE posted_by_employer_id = ? ORDER BY created_at ASC";
                        $stmt_jobs = $conn->prepare($sql_jobs);
                        $stmt_jobs->bind_param("i", $employerid);
                        $stmt_jobs->execute();
                        $result_jobs = $stmt_jobs->get_result();
                        
                        if ($result_jobs->num_rows > 0) {
                            while ($job = $result_jobs->fetch_assoc()) {
                                $job_id = $job['id'];
                                $job_title = htmlspecialchars($job['job_title']);
                                $job_number = $job['job_post_number_by_employer'] ?? 0;
                                
                                // Header cho job
                                echo '<div class="job-header">';
                                echo '<h3>' . $lang['bai_dang_thu_tu'] . $job_number . ' - ' . $job_title . '</h3>';
                                echo '</div>';
                                
                                // Lấy applications cho job này
                                $sql_apps = "SELECT id, jobseeker_id, fullname, phonenumber, email, status, created_at, cv_file, cover_letter
            FROM application
            WHERE job_id = ? AND (hidden_from_employer = 0 OR hidden_from_employer IS NULL) ORDER BY id DESC";
                                $stmt_apps = $conn->prepare($sql_apps);
                                $stmt_apps->bind_param("i", $job_id);
                                $stmt_apps->execute();
                                $result_apps = $stmt_apps->get_result();
                                
                                if ($result_apps->num_rows > 0) {
                                    echo '<div class="application-grid">';
                                    
                                    while ($row = $result_apps->fetch_assoc()):
                                        $application_id = htmlspecialchars($row['id']);
                                        $fullname = htmlspecialchars($row['fullname']);
                                        $phonenumber = htmlspecialchars($row['phonenumber']);
                                        $email = htmlspecialchars($row['email']);
                                        $cover_letter = trim((string) ($row['cover_letter'] ?? ''));
                                        $job_status_raw = htmlspecialchars($row['status']);
                                        $created_at = new DateTime($row['created_at']);
                                        $formatted_created_at = formatDateByLocale($row['created_at']);
                                        $final_formatted_date = trans('cv_application_date', [':date' => $formatted_created_at]);

                                        // Chuẩn hóa đường dẫn CV
                                        $cv_file = '';
                                        if (!empty($row['cv_file'])) {
                                            $cv_file_raw = trim($row['cv_file']);
                                            $baseFolder = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                            if ($baseFolder === '') {
                                                $baseFolder = '/';
                                            }

                                            if (preg_match('#^(https?://)#i', $cv_file_raw)) {
                                                $cv_file = $cv_file_raw;
                                            } else {
                                                if (strpos($cv_file_raw, '/') === 0) {
                                                    $cv_file = $baseFolder . rtrim($cv_file_raw, '/');
                                                } else {
                                                    $cv_file = rtrim($baseFolder, '/') . '/' . ltrim($cv_file_raw, '/');
                                                }
                                            }
                                        }

                                        $status_lang_keys = [
                                            'hired' => 'hired',
                                            'pending' => 'pending',
                                            'rejected' => 'rejected_application',
                                            'interviewed' => 'interviewed',
                                        ];

                                        if ($job_status_raw === 'viewed') {
                                            $job_status_raw = 'pending';
                                            $display_status = $lang[$status_lang_keys[$job_status_raw]];
                                        } else {
                                            $display_status = isset($status_lang_keys[$job_status_raw]) ? $lang[$status_lang_keys[$job_status_raw]] : $job_status_raw;
                                        }

                                        $status_color = '';
                                        switch ($job_status_raw) {
                                            case 'pending':
                                                $status_color = '#007bff';
                                                break;
                                            case 'interviewed':
                                                $status_color = '#ffc107';
                                                break;
                                            case 'hired':
                                                $status_color = '#28a745';
                                                break;
                                            case 'rejected':
                                                $status_color = '#dc3545';
                                                break;
                                            default:
                                                $status_color = '#6c757d';
                                                break;
                                        }
                                        ?>
                                        <div class="application-card" data-application-id="<?php echo $application_id; ?>">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                <div style="font-weight: bold; font-size: 18px; color: #333;"><?php echo $fullname; ?></div>
                                                <div style="font-size: 13px; color: #777;"><?php echo $final_formatted_date; ?></div>
                                            </div>
                                            <div style="font-size: 14px; color: #555; margin-bottom: 5px;">
                                                Email: <strong><?php echo $email; ?></strong>
                                            </div>
                                            <div style="font-size: 14px; color: #555; margin-bottom: 10px;">
                                                <?= $lang['phone'] ?> <strong><?php echo $phonenumber; ?></strong>
                                            </div>
                                            <?php if ($cover_letter !== ''): ?>
                                                <div>
                                                    <button
                                                        type="button"
                                                        class="cover-letter-trigger"
                                                        data-cover-letter="<?= htmlspecialchars($cover_letter, ENT_QUOTES, 'UTF-8') ?>"
                                                        aria-haspopup="dialog"
                                                        aria-controls="coverLetterModal">
                                                        <?= htmlspecialchars($lang['thu_tu_gioi_thieu'] ?? 'Cover letter', ENT_QUOTES, 'UTF-8') ?>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                            <div class="application-status-actions">
                                                <div>
                                                    <?= $lang['status2'] ?> <span style="font-weight: bold; color: <?php echo $status_color; ?>;"><?php echo ucfirst($display_status); ?></span>
                                                </div>
                                                <div class="application-actions">
                                                    <?php if (!empty($cv_file)): ?>
                                                        <a href="<?php echo htmlspecialchars($cv_file); ?>" class="view-cv-button" data-application-id="<?php echo $application_id; ?>" style="background: #b23b3b; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; text-decoration: none; margin-right: 8px;" target="_blank" rel="noopener noreferrer">
                                                            <?= $lang['view_cv'] ?>
                                                        </a>
                                                        <button type="button" class="btn-manage-application" data-application-id="<?php echo $application_id; ?>" data-application-name="<?php echo $fullname; ?>" data-current-status="<?php echo $job_status_raw; ?>" style="background: #007bff; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer; margin-right: 8px;"><?= $lang['manage'] ?></button>
                                                    <?php endif; ?>
                                                    <button type="button" data-application-id="<?php echo $application_id; ?>" class="hide-application-button" style="background: #dc3545; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer;"><?= $lang['delete_submitted_applications'] ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    endwhile;
                                    
                                    echo '</div>';
                                } else {
                                    echo '<div style="text-align: center; color: #888; padding: 20px; background: #f9f9f9; border-radius: 8px; margin-bottom: 30px;">' . ($lang['no_applications'] ?? 'No applications found for this job.') . '</div>';
                                }
                                
                                $stmt_apps->close();
                            }
                        } else {
                            echo '<div style="text-align: center; color: #888; padding: 40px;">' . ($lang['no_jobs_found'] ?? 'No jobs found.') . '</div>';
                        }
                        
                        $stmt_jobs->close();
                    } else {
                        echo '<div style="text-align: center; color: #888; padding: 40px;">Vui lòng đăng nhập để xem ứng tuyển.</div>';
                    }
                    ?>
                </div>

        </div>
    </div>


    <!-- Modal for managing application -->
    <div id="manageApplicationModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px;">
            <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h3 id="modalTitle" style="margin-top: 0; color: #b23b3b;"><?= $lang['manage_application'] ?></h3>
            <p id="modalMessage"></p>
            <form id="manageApplicationForm">
                <input type="hidden" id="applicationId" name="application_id">
                <div style="margin-bottom: 15px;">
                    <label for="statusSelect" style="display: block; margin-bottom: 5px; font-weight: bold;"><?= $lang['status2'] ?></label>
                    <select id="statusSelect" name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="pending"><?= $lang['application_pending'] ?></option>
                        <option value="interviewed"><?= $lang['application_interviewed'] ?></option>
                        <option value="hired"><?= $lang['application_hired'] ?></option>
                        <option value="rejected"><?= $lang['application_rejected'] ?></option>
                    </select>
                </div>
                <div style="text-align: right;">
                    <button type="button" id="cancelBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;"><?= $lang['cancel'] ?></button>
                    <button type="submit" style="background: #b23b3b; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;"><?= $lang['update'] ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for hiding application -->
    <div id="hideApplicationModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 400px; border-radius: 8px;">
            <span class="close" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h3 style="margin-top: 0; color: #dc3545;"><?= $lang['hide_application_title'] ?></h3>
            <p id="hideModalMessage"></p>
            <div style="text-align: right;">
                <button type="button" id="cancelHideBtn" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin-right: 10px;"><?= $lang['cancel'] ?></button>
                <button type="button" id="confirmHideBtn" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;"><?= $lang['confirm'] ?></button>
            </div>
        </div>
    </div>

    <div id="coverLetterModal" class="cover-letter-modal" role="dialog" aria-modal="true" aria-labelledby="coverLetterModalTitle" hidden>
        <div class="cover-letter-modal__dialog" role="document">
            <div class="cover-letter-modal__header">
                <h2 id="coverLetterModalTitle" class="cover-letter-modal__title">
                    <?= htmlspecialchars($lang['thu_tu_gioi_thieu'] ?? 'Cover letter', ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <button type="button" class="cover-letter-modal__close" aria-label="<?= htmlspecialchars($lang['close'] ?? 'Close', ENT_QUOTES, 'UTF-8') ?>">&times;</button>
            </div>
            <p id="coverLetterModalContent" class="cover-letter-modal__content"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('coverLetterModal');
            const content = document.getElementById('coverLetterModalContent');
            const closeButton = modal?.querySelector('.cover-letter-modal__close');
            let lastTrigger = null;

            if (!modal || !content || !closeButton) return;

            const closeModal = function () {
                modal.hidden = true;
                document.body.style.overflow = '';
                if (lastTrigger) lastTrigger.focus();
            };

            document.querySelectorAll('.cover-letter-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    lastTrigger = trigger;
                    content.textContent = trigger.dataset.coverLetter || '—';
                    modal.hidden = false;
                    document.body.style.overflow = 'hidden';
                    closeButton.focus();
                });
            });

            closeButton.addEventListener('click', closeModal);
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeModal();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) closeModal();
            });
        });
    </script>

    <script>
        // Modal management
        const manageModal = document.getElementById('manageApplicationModal');
        const hideModal = document.getElementById('hideApplicationModal');
        const manageForm = document.getElementById('manageApplicationForm');
        let currentApplicationId = null;

        // Handle manage application button clicks
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-manage-application')) {
                e.preventDefault();
                const applicationId = e.target.getAttribute('data-application-id');
                const applicationName = e.target.getAttribute('data-application-name');
                const currentStatus = e.target.getAttribute('data-current-status');
                
                currentApplicationId = applicationId;
                document.getElementById('applicationId').value = applicationId;
                document.getElementById('modalTitle').textContent = '<?php echo $lang['manage_application_title']; ?>'.replace(':name', applicationName);
                document.getElementById('modalMessage').textContent = '<?php echo $lang['manage_application_update_message']; ?>'.replace(':name', applicationName);
                document.getElementById('statusSelect').value = currentStatus;
                
                manageModal.style.display = 'block';
            }
            
            // Handle hide application button clicks
            if (e.target.classList.contains('hide-application-button')) {
                e.preventDefault();
                const applicationId = e.target.getAttribute('data-application-id');
                const applicationName = e.target.closest('.application-card').querySelector('div[style*="font-weight: bold"]').textContent;
                
                currentApplicationId = applicationId;
                document.getElementById('hideModalMessage').textContent = '<?php echo $lang['hide_application_confirm_message']; ?>'.replace(':name', applicationName);
                
                hideModal.style.display = 'block';
            }
        });

        // Close modals when clicking close button or outside
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.onclick = function() {
                manageModal.style.display = 'none';
                hideModal.style.display = 'none';
            }
        });

        window.onclick = function(event) {
            if (event.target == manageModal) {
                manageModal.style.display = 'none';
            }
            if (event.target == hideModal) {
                hideModal.style.display = 'none';
            }
        }

        // Handle cancel buttons
        document.getElementById('cancelBtn').onclick = function() {
            manageModal.style.display = 'none';
        }
        
        document.getElementById('cancelHideBtn').onclick = function() {
            hideModal.style.display = 'none';
        }

        // Handle manage form submission
        manageForm.onsubmit = function(e) {
            e.preventDefault();
            
            const formData = new FormData(manageForm);
            
            fetch('manage_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật trạng thái thành công!');
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi cập nhật trạng thái');
            });
        }

        // Handle hide application confirmation
        document.getElementById('confirmHideBtn').onclick = function() {
            if (!currentApplicationId) return;
            
            const formData = new FormData();
            formData.append('application_id', currentApplicationId);
            formData.append('action', 'hide');
            
            fetch('manage_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ẩn đơn ứng tuyển thành công!');
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi ẩn đơn ứng tuyển');
            });
        }
    </script>

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