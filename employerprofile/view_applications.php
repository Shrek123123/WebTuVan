<?php
session_start();

if (isset($_POST['lang'])) {
    $_SESSION['lang'] = $_POST['lang'];
} elseif (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

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

require_once '../config.php';
include '../helpers.php';
include '../lang/' . $_SESSION['lang'] . '.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$employername = $_SESSION['usernameemployer'] ?? 'Employer';

$job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : (isset($_GET['job_id']) ? intval($_GET['job_id']) : 0);
$flash_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_SESSION['employerid']) || empty($_SESSION['employerid'])) {
        header('Location: employerpage.php');
        exit;
    }

    $action = $_POST['action'];
    $employerid = $_SESSION['employerid'];

    if ($action === 'hideFromEmployer') {
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        if ($application_id <= 0) {
            $_SESSION['flash_message'] = $lang['invalid_application_id'] ?? 'Invalid application ID.';
        } else {
            // Kiểm tra quyền
            $checkSql = "SELECT j.posted_by_employer_id FROM application a JOIN hoa_giai_vien j ON a.job_id = j.id WHERE a.id = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param('i', $application_id);
                $checkStmt->execute();
                $res = $checkStmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (intval($row['posted_by_employer_id']) === intval($employerid)) {
                        $updSql = "UPDATE application SET hidden_from_employer = 1 WHERE id = ?";
                        $updStmt = $conn->prepare($updSql);
                        if ($updStmt) {
                            $updStmt->bind_param('i', $application_id);
                            if ($updStmt->execute()) {
                                $_SESSION['flash_message'] = $lang['delete_application_success_message'] ?? 'Application hidden successfully.';
                            } else {
                                $_SESSION['flash_message'] = $lang['database_error'] ?? 'Database error.';
                            }
                            $updStmt->close();
                        }
                    } else {
                        $_SESSION['flash_message'] = $lang['unauthorized_action'] ?? 'Unauthorized action.';
                    }
                } else {
                    $_SESSION['flash_message'] = $lang['application_not_found'] ?? 'Application not found.';
                }
                $checkStmt->close();
            }
        }

        header('Location: view_applications.php?job_id=' . $job_id);
        exit;
    }

    if ($action === 'updateApplicationStatus') {
        $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
        $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';
        $allowed_statuses = ['applied', 'viewed', 'interviewed', 'hired', 'rejected'];

        if ($application_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
            $_SESSION['flash_message'] = $lang['invalid_request'] ?? 'Invalid request.';
        } else {
            $checkSql = "SELECT j.posted_by_employer_id FROM application a JOIN hoa_giai_vien j ON a.job_id = j.id WHERE a.id = ? LIMIT 1";
            $checkStmt = $conn->prepare($checkSql);
            if ($checkStmt) {
                $checkStmt->bind_param('i', $application_id);
                $checkStmt->execute();
                $res = $checkStmt->get_result();
                if (($row = $res->fetch_assoc()) && intval($row['posted_by_employer_id']) === intval($employerid)) {
                    $sql = "UPDATE application SET status = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param('si', $new_status, $application_id);
                        if ($stmt->execute()) {
                            $_SESSION['flash_message'] = $lang['status_update_success'] ?? 'Status updated successfully.';
                        } else {
                            $_SESSION['flash_message'] = $lang['database_error'] ?? 'Database error.';
                        }
                        $stmt->close();
                    }
                } else {
                    $_SESSION['flash_message'] = $lang['unauthorized_action'] ?? 'Unauthorized action.';
                }
                $checkStmt->close();
            }
        }

        header('Location: view_applications.php?job_id=' . $job_id);
        exit;
    }
}

if (isset($_SESSION['flash_message'])) {
    $flash_message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}

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
        /* .posting-options {
            display: grid;
            grid-template-columns: repeat(2, 260px);
            /* 2 cột cố định */
        justify-content: center;
        /* căn giữa toàn bộ khối */
        gap: 20px;
        }

        */ .post-btn {
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
            .application-status-actions {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }

            .application-status,
            .application-actions {
                width: 100%;
                box-sizing: border-box;
            }

            .application-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
            }

            .application-actions a,
            .application-actions button {
                margin-right: 0 !important;
                max-width: 100%;
                box-sizing: border-box;
            }

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

    <form id="lang-switch-form" method="POST" action="view_applications.php" style="display:none;">
        <input type="hidden" name="lang" id="lang-switch-input" value="">
        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id); ?>">
    </form>

    <script>
        document.getElementById('lang-switch-btn').onclick = function () {
            var currentLang = '<?php echo $_SESSION['lang']; ?>';
            document.getElementById('lang-switch-input').value = (currentLang === 'vi') ? 'en' : 'vi';
            document.getElementById('lang-switch-form').submit();
        };
    </script>
    </div>
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
                <?php
                $employerid = $_SESSION['employerid'] ?? 0;
                $stmt = $conn->prepare("
    SELECT *
    FROM hoa_giai_vien
    WHERE id = ? AND posted_by_employer_id = ?
    LIMIT 1
");
                $stmt->bind_param("ii", $job_id, $employerid);
                $stmt->execute();
                $result = $stmt->get_result();
                $job_row = $result->fetch_assoc();
                $stmt->close();

                if (!$job_row) {
                    die("Job không tồn tại hoặc không thuộc employer.");
                }

                // Lấy job_post_number
                $job_post_number = $job_row['job_post_number_by_employer'] ?? 0;

                // Tạo message
                $lang_key = 'your_job_post#';
                $message = sprintf($lang[$lang_key], $job_post_number);

                // Lấy tổng số application cho cùng một job
                $sql = "SELECT COUNT(id) AS total_applications
    FROM application
    WHERE job_id = ? AND (hidden_from_employer = 0 OR hidden_from_employer IS NULL)";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $job_id);
                $stmt->execute();
                $result = $stmt->get_result();

                $total_applications = 0;
                if ($row = $result->fetch_assoc()) {
                    $total_applications = $row['total_applications'];
                }
                ?>


                <div style="color: #b23b3b; font-weight: bold; font-size: 20px; text-align: center;">
                    <?php echo $message ?><span></span>
                </div>
                <?php if (!empty($flash_message)): ?>
                    <div style="margin: 10px auto; width: fit-content; padding: 10px 16px; border-radius: 6px; background: #eaf8ed; color: #2a662d; border: 1px solid #b9e6c0; font-weight: bold;">
                        <?php echo htmlspecialchars($flash_message); ?>
                    </div>
                <?php endif; ?>
                <div style="padding: 10px;">
                    <?php if ($job_id > 0): ?>
                        <h4 style="margin-bottom: 15px; color: #b23b3b;">
                            <?= $lang['application_detail_to_job'] ?>
                            <strong><?php echo htmlspecialchars($total_applications); ?></strong>
                        </h4>

                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <?php
                            // Kiểm tra kết nối database
                            if (isset($conn) && $conn->ping()) {
                                // Truy vấn để lấy các ứng dụng cho job_id cụ thể
                                // Lấy thông tin từ bảng 'application'
                                $sql = "SELECT id, jobseeker_id, fullname, phonenumber, email, status, created_at, cv_file, cover_letter
            FROM application
            WHERE job_id = ? AND (hidden_from_employer = 0 OR hidden_from_employer IS NULL)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $job_id);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows === 0) {
                                    echo '<div style="color: #888; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">' . $lang['no_application_found_for_this_job_post'] . '</div>';
                                } else {
                                    while ($row = $result->fetch_assoc()):
                                        $application_id = htmlspecialchars($row['id']);
                                        $fullname = htmlspecialchars($row['fullname']);
                                        $phonenumber = htmlspecialchars($row['phonenumber']);
                                        $email = htmlspecialchars($row['email']);
                                        $cover_letter = trim((string) ($row['cover_letter'] ?? ''));
                                        $job_status_raw = htmlspecialchars($row['status']);
                                        $created_at = new DateTime($row['created_at']);
                                        $formatted_created_at = formatDateByLocale($row['created_at']);
                                        $final_formatted_date = trans('cv_application_date', [':date' => $formatted_created_at]);

                                        // Chuẩn hóa đường dẫn CV để luôn trỏ vào root folder ViecNhe
                                        $cv_file = '';
                                        if (!empty($row['cv_file'])) {
                                            $cv_file_raw = trim($row['cv_file']);
                                            $baseFolder = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
                                            if ($baseFolder === '') {
                                                $baseFolder = '/';
                                            }

                                            // Nếu là URL đầy đủ thì giữ nguyên
                                            if (preg_match('#^(https?://)#i', $cv_file_raw)) {
                                                $cv_file = $cv_file_raw;
                                            } else {
                                                // Nếu là đường dẫn bắt đầu / thì cho vào base folder ViecNhe
                                                if (strpos($cv_file_raw, '/') === 0) {
                                                    $cv_file = $baseFolder . rtrim($cv_file_raw, '/');
                                                } else {
                                                    // path tương đối như uploads/cv_file/abc.pdf
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

                                        // For employer view: hide 'viewed' status (show blank). Only jobseeker sees 'viewed'.
                                        if ($job_status_raw === 'viewed') {
                                            // change nothing to employer
                                            $job_status_raw = 'pending';
                                            $display_status = $lang[$status_lang_keys[$job_status_raw]];
                                        } else {
                                            $display_status = isset($status_lang_keys[$job_status_raw]) ? $lang[$status_lang_keys[$job_status_raw]] : $job_status_raw;
                                        }

                                        // Định nghĩa màu cho trạng thái
                                        $status_color = '';
                                        switch ($job_status_raw) {
                                            case 'pending':
                                                $status_color = '#007bff'; // Blue
                                                break;
                                            case 'interviewed':
                                                $status_color = '#ffc107'; // Yellow
                                                break;
                                            case 'hired':
                                                $status_color = '#28a745'; // Green
                                                break;
                                            case 'rejected':
                                                $status_color = '#dc3545'; // Red
                                                break;
                                            default:
                                                $status_color = '#6c757d'; // Grey
                                                break;
                                        }

                                        ?>
                                        <div class="application-card" data-application-id="<?php echo $application_id; ?>"
                                            style="display: flex; flex-direction: column; background: #f9f9f9; padding: 15px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                            <div
                                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                <div style="font-weight: bold; font-size: 18px; color: #333;"><?php echo $fullname; ?>
                                                </div>
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
                                            <div class="application-status-actions"
                                                style="display: flex; justify-content: space-between; align-items: center;">
                                                <div class="application-status">
                                                    <?= $lang['status2'] ?> <span
                                                        style="font-weight: bold; color: <?php echo $status_color; ?>;"><?php echo ucfirst($display_status); ?></span>
                                                </div>
                                                <div class="application-actions">
                                                    <?php if (!empty($cv_file)): ?>
                                                        <a href="<?php echo htmlspecialchars($cv_file); ?>" class="view-cv-button"
                                                            data-application-id="<?php echo $application_id; ?>"
                                                            style="background: #b23b3b; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; text-decoration: none; margin-right: 8px;"
                                                            target="_blank" rel="noopener noreferrer">
                                                            <?= $lang['view_cv'] ?>
                                                        </a>
                                                        <button type="button"
                                                            class="btn-manage-application"
                                                            data-application-id="<?php echo $application_id; ?>"
                                                            data-application-name="<?php echo $fullname; ?>"
                                                            data-current-status="<?php echo htmlspecialchars($display_status, ENT_QUOTES, 'UTF-8'); ?>"
                                                            style="background: #007bff; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer; margin-right: 8px;"><?= $lang['manage'] ?></button>
                                                    <?php endif; ?>

                                                    <!-- Hide (archive from employer) button -->
                                                    <button type="button" data-application-id="<?php echo $application_id; ?>"
                                                        class="hide-application-button"
                                                        style="background: #dc3545; color: #fff; border: none; border-radius: 5px; padding: 6px 12px; font-size: 13px; cursor: pointer;"><?= $lang['delete_submitted_applications'] ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    endwhile;
                                }
                            } else {
                                echo '<div style="color: red; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">Database connection error.</div>';
                            }
                            ?>
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
                            // Tạo modal cho Manage và Xóa
                            document.body.insertAdjacentHTML('beforeend', `
                                <div id="appModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9998;"></div>
                                <div id="manageApplicationModal" style="display:none; position:fixed; inset:0; z-index:10000; align-items:center; justify-content:center;">
                                    <div style="background:#fff; border-radius:8px; width:95%; max-width:420px; padding:20px; box-shadow:0 10px 24px rgba(0,0,0,0.2);">
                                        <h3 style="margin-top:0;"><?= addslashes($lang['manage'] ?? 'Manage') ?></h3>
                                        <div id="manageAppInfo" style="margin-bottom:12px; font-size:14px; color:#333;"></div>
                                        <form id="updateStatusForm" method="POST" action="view_applications.php">
                                            <input type="hidden" name="action" value="updateApplicationStatus">
                                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                            <input type="hidden" name="application_id" id="modalApplicationId" value="">
                                            <input type="hidden" name="new_status" id="modalNewStatus" value="">
                                            <div style="display:flex; gap:8px; margin-bottom:12px;">
                                                <button type="button" class="status-action" data-status="interviewed" style="flex:1; background:#ffc107; border:none; color:#000; border-radius:6px; padding:8px; cursor:pointer;"><?= addslashes($lang['interviewed'] ?? 'Interviewed') ?></button>
                                                <button type="button" class="status-action" data-status="hired" style="flex:1; background:#28a745; border:none; color:#fff; border-radius:6px; padding:8px; cursor:pointer;"><?= addslashes($lang['hired'] ?? 'Hired') ?></button>
                                                <button type="button" class="status-action" data-status="rejected" style="flex:1; background:#dc3545; border:none; color:#fff; border-radius:6px; padding:8px; cursor:pointer;"><?= addslashes($lang['reject'] ?? 'Rejected') ?></button>
                                            </div>
                                        </form>
                                        <div style="text-align:right;"><button id="closeManageModal" style="background:#ddd; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;"><?= addslashes($lang['cancel'] ?? 'Cancel') ?></button></div>
                                    </div>
                                </div>
                                <div id="confirmHideModal" style="display:none; position:fixed; inset:0; z-index:10001; align-items:center; justify-content:center;">
                                    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.45);"></div>
                                    <div style="position:relative; background:#fff; border-radius:8px; max-width:420px; width:90%; padding:18px; box-shadow:0 8px 24px rgba(0,0,0,0.2); z-index:10002;">
                                        <p id="confirmHideText" style="margin:0 0 14px;"><?= addslashes($lang['confirm_delete_submitted_applications'] ?? 'Delete submitted application?') ?></p>
                                        <form method="POST" action="view_applications.php">
                                            <input type="hidden" name="action" value="hideFromEmployer">
                                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                                            <input type="hidden" name="application_id" id="confirmHideApplicationId" value="">
                                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                                <button type="button" id="cancelHideBtn" style="background:#ccc; border:none; padding:8px 12px; border-radius:6px; cursor:pointer;"><?= addslashes($lang['cancel'] ?? 'Cancel') ?></button>
                                                <button type="submit" style="background:#dc3545; border:none; color:#fff; padding:8px 12px; border-radius:6px; cursor:pointer;"><?= addslashes($lang['delete_submitted_applications'] ?? 'Delete') ?></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            `);

                            var overlay = document.getElementById('appModalOverlay');
                            var manageModal = document.getElementById('manageApplicationModal');
                            var confirmHideModal = document.getElementById('confirmHideModal');

                            function showModal(modal) {
                                modal.style.display = 'flex';
                                overlay.style.display = 'block';
                            }
                            function hideModal(modal) {
                                modal.style.display = 'none';
                                overlay.style.display = 'none';
                            }

                            document.body.addEventListener('click', function (e) {
                                var manageBtn = e.target.closest('.btn-manage-application');
                                if (manageBtn) {
                                    e.preventDefault();
                                    var applicationId = manageBtn.getAttribute('data-application-id');
                                    var appName = manageBtn.getAttribute('data-application-name');
                                    var currentStatus = manageBtn.getAttribute('data-current-status');
                                    document.getElementById('modalApplicationId').value = applicationId;
                                    document.getElementById('manageAppInfo').textContent = '<?= addslashes($lang['application'] ?? 'Application') ?> ' + appName + ' (<?= addslashes($lang['current'] ?? 'current') ?>' + currentStatus + ')';
                                    showModal(manageModal);
                                    return;
                                }

                                var hideBtn = e.target.closest('.hide-application-button');
                                if (hideBtn) {
                                    e.preventDefault();
                                    var applicationId = hideBtn.getAttribute('data-application-id');
                                    document.getElementById('confirmHideApplicationId').value = applicationId;
                                    document.getElementById('confirmHideText').textContent = '<?= addslashes($lang['confirm_delete_submitted_applications'] ?? 'Delete submitted application?') ?>';
                                    showModal(confirmHideModal);
                                    return;
                                }
                            });

                            overlay.addEventListener('click', function () {
                                hideModal(manageModal);
                                hideModal(confirmHideModal);
                            });
                            document.getElementById('closeManageModal').addEventListener('click', function () {
                                hideModal(manageModal);
                            });
                            document.getElementById('cancelHideBtn').addEventListener('click', function () {
                                hideModal(confirmHideModal);
                            });

                            document.querySelectorAll('.status-action').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    document.getElementById('modalNewStatus').value = this.getAttribute('data-status');
                                    document.getElementById('updateStatusForm').submit();
                                });
                            });
                        </script>
                    <?php else: ?>
                        <div
                            style="color: #888; text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                            <?= $lang['no_job_id_provided'] ?>
                        </div>
                    <?php endif; ?>
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