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
include '../helpers.php';

$currentPage = basename($_SERVER['PHP_SELF']);

$employername = $_SESSION['usernameemployer'] ?? 'Employer';
$employer_id = $_SESSION['employerid'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
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

        :root {
            --jobhive-brand-red: #b23b3b;
            --jobhive-danger-red: #dc3545;
            --jobhive-surface: #fff;
            --jobhive-overlay: rgba(0, 0, 0, 0.48);
        }

        .application-action-form {
            display: inline;
        }

        .application-action-button {
            position: relative;
            background: var(--jobhive-brand-red);
            color: var(--jobhive-surface);
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .application-action-button:focus-visible,
        .rejected-reason-trigger:focus-visible,
        .rejection-modal__close:focus-visible {
            outline: 3px solid rgba(178, 59, 59, 0.35);
            outline-offset: 2px;
        }

        .application-count-badge {
            position: absolute;
            top: -9px;
            right: -9px;
            min-width: 22px;
            height: 22px;
            padding: 0 5px;
            box-sizing: border-box;
            border-radius: 50%;
            background: var(--jobhive-danger-red);
            color: var(--jobhive-surface);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            border: 2px solid var(--jobhive-surface);
        }

        .rejected-reason-trigger {
            margin-left: 8px;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--jobhive-danger-red);
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
        }

        .rejection-modal[hidden] {
            display: none;
        }

        .rejection-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: var(--jobhive-overlay);
        }

        .rejection-modal__dialog {
            width: min(520px, 100%);
            max-height: min(70vh, 520px);
            overflow: auto;
            background: var(--jobhive-surface);
            border-radius: 10px;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.24);
        }

        .rejection-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
        }

        .rejection-modal__title {
            margin: 0;
            color: #333;
            font-size: 18px;
        }

        .rejection-modal__close {
            border: 0;
            background: transparent;
            color: #666;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .rejection-modal__content {
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
        }

        /* button post job */
        #btn-postjob.active {
            background: #b23b3b;
            color: #fff;
            font-weight: bold;
        }

        #btn-myjobposts.active {
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

            .dashboard-company {
                display: none;
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
                padding: 10px !important;
            }

            .dashboard-content > div {
                padding: 15px !important;
            }

            /* Target job card flex container */
            .dashboard-content .dashboard-job-card {
                flex-direction: column !important;
                align-items: stretch !important;
            }

            .dashboard-content .dashboard-job-card img {
                width: 100% !important;
                max-width: 100% !important;
                margin-right: 0 !important;
                margin-bottom: 15px !important;
            }

            .dashboard-content .dashboard-job-card div:last-child {
                margin-left: 0 !important;
                margin-top: 15px !important;
                align-items: flex-start !important;
                flex-direction: row !important;
                flex-wrap: wrap;
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
                <button id="btn-myjobposts" onclick="window.location.href='my_job_posts.php'"
                    class="<?= ($currentPage == 'my_job_posts.php') ? 'active' : '' ?>"><?= $lang['employerprofile_myjobposts'] ?></button>
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
                    $sql_total = "SELECT COUNT(a.id) AS total FROM application a JOIN job j ON a.job_id = j.id WHERE j.posted_by_employer_id = ? AND (a.hidden_from_employer = 0 OR a.hidden_from_employer IS NULL)";
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
                    $sql_app = "SELECT a.fullname, a.status, j.job_title FROM application a JOIN job j ON a.job_id = j.id WHERE j.posted_by_employer_id = ? AND (a.hidden_from_employer = 0 OR a.hidden_from_employer IS NULL) ORDER BY a.id DESC LIMIT 2";
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

                <div style="padding: 30px; border-radius: 8px;">
                    <h2 style="color: #b23b3b; margin-bottom: 20px;"><?= $lang['my_job_post'] ?></h2>
                    <div style="display: flex; flex-direction: column; gap: 18px;">
                        <?php
                        if ($employer_id > 0 && isset($conn) && $conn->ping()) {
                            $sql = "SELECT job.*, (SELECT COUNT(a.id) FROM application a WHERE a.job_id = job.id) AS application_count FROM job WHERE posted_by_employer_id = ? ORDER BY created_at DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $employer_id);
                            $stmt->execute();
                            $result = $stmt->get_result();


                            if ($result->num_rows === 0) {
                                echo '<div style="color: #888; text-align: center; padding: 20px;">' . $lang['no_jobs_found'] . '</div>';
                            }

                            while ($row = $result->fetch_assoc()):
                                // Xử lý dữ liệu hiển thị
                                $job_id = htmlspecialchars($row['id']);
                                $company_name = '';
                                $logo = '../uploads/companylogo/no-logo.png';

                                if (!empty($row['company_id'])) {
                                    $company_sql = "SELECT company_name, company_logo FROM company WHERE id = ? LIMIT 1";
                                    $company_stmt = $conn->prepare($company_sql);
                                    if ($company_stmt) {
                                        $company_stmt->bind_param('i', $row['company_id']);
                                        $company_stmt->execute();
                                        $company_result = $company_stmt->get_result();
                                        if ($company_result && $company_result->num_rows > 0) {
                                            $company_row = $company_result->fetch_assoc();
                                            $company_name = $company_row['company_name'] ?? '';
                                            $db_logo = $company_row['company_logo'] ?? '';
                                            if (!empty($db_logo)) {
                                                $logo = trim((string) $db_logo);

                                                if (!preg_match('#^https?://#i', $logo)) {
                                                    $logo = preg_replace('#^(?:\./)+#', '', $logo);
                                                    $logo = preg_replace('#^(?:\.\./)+#', '', $logo);
                                                    $logo = preg_replace('#^/+#', '', $logo);

                                                    if (stripos($logo, 'uploads/') === 0) {
                                                        $logo = '../' . $logo;
                                                    } elseif (stripos($logo, 'companylogo/') === 0) {
                                                        $logo = '../uploads/' . $logo;
                                                    } elseif (stripos($logo, 'uploads/companylogo/') === 0) {
                                                        $logo = '../' . $logo;
                                                    } else {
                                                        $logo = '../uploads/companylogo/' . basename($logo);
                                                    }
                                                }
                                            }
                                        }
                                        $company_stmt->close();
                                    }
                                }
                                // Tính toán thời gian
                                $created_at = new DateTime($row['created_at']);
                                $expire_at = (clone $created_at)->modify("+" . intval($row['post_duration']) . " days");
                                $now_dt = new DateTime();
                                $days_left = $now_dt < $expire_at ? $now_dt->diff($expire_at)->days : 0;

                                // Tính x phút/giờ trước
                                $last_updated = strtotime($row['last_updated_at'] ?? $row['created_at']);
                                $diff = time() - $last_updated;
                                if ($diff < 60)
                                    $updated_str = sprintf($lang['updated_now'], $diff);
                                elseif ($diff < 3600)
                                    $updated_str = sprintf($lang['updated_minutes'], floor($diff / 60));
                                elseif ($diff < 86400)
                                    $updated_str = sprintf($lang['updated_hours'], floor($diff / 3600));
                                else
                                    $updated_str = sprintf($lang['updated_days'], floor($diff / 86400));

                                // Xử lý trạng thái và số lượng hồ sơ theo từng bài đăng
                                $job_status_raw = (string) ($row['status'] ?? '');
                                $status_colors = ['pending_review' => '#ffc107', 'approved' => '#28a745', 'rejected' => '#dc3545'];
                                $status_color = $status_colors[$job_status_raw] ?? '#6c757d';
                                $job_status_text = $lang[$job_status_raw] ?? $job_status_raw;
                                $application_count = max(0, (int) ($row['application_count'] ?? 0));
                                $admin_notes = trim((string) ($row['admin_notes'] ?? ''));

                                ?>
                                <div class="dashboard-job-card"
                                    style="background: #fff; border: 1px solid #ddd; border-radius: 10px; padding: 18px; display: flex; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                    <img src="<?= $logo ?>" alt="logo"
                                        style="width: 120px; height: auto; max-height: 140px; border-radius: 8px; object-fit: contain; margin-right: 18px; border: 1px solid #eee; background: #fff;">

                                    <div style="flex: 1;">
                                        <div style="font-weight: bold; font-size: 18px; margin-bottom: 4px;">
                                            <a href="../jobdetail.php?id=<?= $job_id ?>"
                                                style="color: #333; text-decoration: none;"><?= htmlspecialchars($row['job_title']) ?></a>
                                        </div>
                                        <div style="color: #888; font-size: 13px; margin-bottom: 8px;">
                                            <?= htmlspecialchars($company_name !== '' ? $company_name : ($row['company_id'] ?? '')) ?>
                                        </div>

                                        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                            <span
                                                style="background: #f8f8f8; color: #b23b3b; border-radius: 6px; padding: 2px 10px; font-size: 12px;"><?= htmlspecialchars($row['job_location']) ?></span>
                                            <span
                                                style="background: #f8f8f8; color: #b23b3b; border-radius: 6px; padding: 2px 10px; font-size: 12px;">
                                                <?= $days_left > 0 ? sprintf($lang['job_days_left'], $days_left) : $lang['job_closed'] ?>
                                            </span>
                                            <span
                                                style="background: #f8f8f8; color: #888; border-radius: 6px; padding: 2px 10px; font-size: 12px;"><?= $updated_str ?></span>
                                        </div>

                                        <div style="color: #b23b3b; font-weight: bold; font-size: 16px; margin-bottom: 5px;">
                                            <?= htmlspecialchars(translateJobSalary($row['salary'] ?? '', $lang), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div style="font-size: 14px;">
                                            <?= $lang['status'] ?>: <span
                                                style="font-weight: bold; color: <?= $status_color ?>;"><?= htmlspecialchars($job_status_text, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($job_status_raw === 'rejected'): ?>
                                                <button
                                                    type="button"
                                                    class="rejected-reason-trigger"
                                                    data-rejection-reason="<?= htmlspecialchars($admin_notes, ENT_QUOTES, 'UTF-8') ?>"
                                                    aria-haspopup="dialog"
                                                    aria-controls="rejectionReasonModal">
                                                    <?= htmlspecialchars($lang['rejected_reason'] ?? '- Reasons for rejecting:', ENT_QUOTES, 'UTF-8') ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div
                                        style="display: flex; flex-direction: column; gap: 8px; margin-left: 18px; align-items: flex-end;">
                                        <form action="jobposts_edit.php" method="POST" style="display: inline;">
                                            <input type="hidden" name="id_to_edit" value="<?php echo $job_id; ?>">
                                            <button type="submit"
                                                style="background: #d90000; color: #fff; border: none; border-radius: 6px; padding: 6px 18px; font-size: 13px; cursor: pointer;">
                                                <?= $lang['edit'] ?>
                                            </button>
                                        </form>
                                        <form style="display: inline;" onsubmit="deleteJobPost(event, <?= $job_id ?>)">
                                            <button type="submit"
                                                style="background: #fff; color: #d90000; border: 1px solid #d90000; border-radius: 6px; padding: 6px 18px; font-size: 13px; cursor: pointer;">
                                                <?= $lang['delete'] ?>
                                            </button>
                                        </form>
                                        <form action="view_applications.php" method="POST" class="application-action-form">
                                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                            <button
                                                type="submit"
                                                class="application-action-button"
                                                aria-label="<?= htmlspecialchars($lang['check_submitted_applications'], ENT_QUOTES, 'UTF-8') ?> (<?= $application_count ?>)">
                                                <?= htmlspecialchars($lang['check_submitted_applications'], ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($application_count > 0): ?>
                                                    <span class="application-count-badge" aria-hidden="true"><?= $application_count ?></span>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <?php
                            endwhile;
                        } else {
                            echo '<div style="color: #888; text-align: center; padding: 20px;">Vui lòng đăng nhập để xem bài đăng.</div>';
                        }
                        ?>
                    </div>
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

    <div
        id="rejectionReasonModal"
        class="rejection-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rejectionReasonModalTitle"
        hidden>
        <div class="rejection-modal__dialog" role="document">
            <div class="rejection-modal__header">
                <h2 id="rejectionReasonModalTitle" class="rejection-modal__title">
                    <?= htmlspecialchars($lang['title'] ?? 'Reasons:', ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <button
                    type="button"
                    class="rejection-modal__close"
                    aria-label="<?= htmlspecialchars($lang['close'] ?? 'Close', ENT_QUOTES, 'UTF-8') ?>">&times;</button>
            </div>
            <p id="rejectionReasonModalContent" class="rejection-modal__content"></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('rejectionReasonModal');
            const modalContent = document.getElementById('rejectionReasonModalContent');
            const closeButton = modal?.querySelector('.rejection-modal__close');
            let lastTrigger = null;

            if (!modal || !modalContent || !closeButton) return;

            const closeModal = function () {
                modal.hidden = true;
                document.body.style.overflow = '';
                if (lastTrigger) lastTrigger.focus();
            };

            document.querySelectorAll('.rejected-reason-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    lastTrigger = trigger;
                    modalContent.textContent = trigger.dataset.rejectionReason || '—';
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
</body>

<?php include 'footer.php' ?>


    <script>
        function deleteJobPost(event, jobId) {
            event.preventDefault();

            if (!confirm("<?php echo $lang['confirm-deletejobpost']; ?>")) return;

            fetch('delete_job_post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'job_id=' + jobId
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast("<?php echo $lang['delete_job_post_success']; ?>", "success");

                        // 👉 reload sau 2 giây
                        setTimeout(() => {
                            location.reload();
                        }, 2000);

                    } else {
                        showToast(data.message, "error");
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast("Lỗi server!", "error");
                });
        }


        // 👉 fix lỗi showToast undefined
        function showToast(message, type) {
            // overlay (nền mờ)
            let overlay = document.createElement("div");
            overlay.style.position = "fixed";
            overlay.style.top = "0";
            overlay.style.left = "0";
            overlay.style.width = "100%";
            overlay.style.height = "100%";
            overlay.style.background = "rgba(0,0,0,0.4)";
            overlay.style.display = "flex";
            overlay.style.alignItems = "center";
            overlay.style.justifyContent = "center";
            overlay.style.zIndex = "9999";

            // box thông báo
            let box = document.createElement("div");
            box.innerText = message;

            box.style.background = "#fff";
            box.style.padding = "20px 40px";
            box.style.borderRadius = "10px";
            box.style.fontSize = "16px";
            box.style.fontWeight = "500";
            box.style.boxShadow = "0 4px 20px rgba(0,0,0,0.2)";
            box.style.textAlign = "center";

            if (type === "success") {
                box.style.color = "#28a745";
            } else {
                box.style.color = "#dc3545";
            }

            overlay.appendChild(box);
            document.body.appendChild(overlay);

            // tự biến mất sau 2s
            setTimeout(() => {
                overlay.remove();
            }, 2000);
        }
    </script>

</html>