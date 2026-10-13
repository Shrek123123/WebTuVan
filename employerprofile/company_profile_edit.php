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

$currentPage = basename($_SERVER['PHP_SELF']);
$employerid = $_SESSION['employerid'] ?? 0;
$employername = $_SESSION['usernameemployer'] ?? 'Employer';
$company_id = isset($_GET['company_id']) ? (int) $_GET['company_id'] : 0;
$company_data = null;

if ($employerid > 0 && isset($conn) && $conn->ping()) {
    if ($company_id > 0) {
        $sql = "SELECT * FROM company WHERE id = ? AND employer_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $company_id, $employerid);
            $stmt->execute();
            $result = $stmt->get_result();
            $company_data = $result->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$company_data) {
        $sql = "SELECT * FROM company WHERE employer_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $employerid);
            $stmt->execute();
            $result = $stmt->get_result();
            $company_data = $result->fetch_assoc();
            $stmt->close();
        }
    }
}

if (!$company_data && $company_id > 0) {
    header('Location: company_profile.php');
    exit();
}

$company_logo_preview = trim((string) ($company_data['company_logo'] ?? ''));
$banner_logo_preview = trim((string) ($company_data['banner_logo'] ?? ''));
$company_size_value = trim((string) ($company_data['company_size'] ?? ''));
$company_size_is_custom = $company_size_value !== '' && !in_array($company_size_value, ['1-10', '11-50', '51-200', '201-500', '501-1000', '1001-5000', '5000+', 'unknown'], true);

foreach (['company_logo_preview' => $company_logo_preview, 'banner_logo_preview' => $banner_logo_preview] as $key => $value) {
    if ($value === '' || preg_match('#^https?://#i', $value)) {
        continue;
    }

    $normalized = preg_replace('#^(?:\./)+#', '', $value);
    $normalized = preg_replace('#^(?:\.\./)+#', '', $normalized);
    $normalized = preg_replace('#^/+#', '', $normalized);

    if (stripos($normalized, 'uploads/') === 0) {
        $normalized = '../' . $normalized;
    } elseif (stripos($normalized, 'companylogo/') === 0) {
        $normalized = '../uploads/' . $normalized;
    } elseif (stripos($normalized, 'uploads/companylogo/') === 0) {
        $normalized = '../' . $normalized;
    } else {
        $normalized = '../uploads/companylogo/' . basename($normalized);
    }

    if ($key === 'company_logo_preview') {
        $company_logo_preview = $normalized;
    } else {
        $banner_logo_preview = $normalized;
    }
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

        .company-form-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #e9e3e3;
            box-shadow: 0 10px 26px rgba(116, 35, 35, 0.08);
            padding: 28px;
            max-width: 760px;
            width: 100%;
            margin: 0 auto;
        }

        .company-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f0e4e4;
        }

        .company-form-header h2 {
            margin: 0;
            color: #b23b3b;
            font-size: 28px;
        }

        .company-subnav {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .back-link {
            color: #b23b3b;
            text-decoration: none;
            font-weight: 600;
        }

        .company-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .form-input,
        .form-textarea,
        .form-file {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            background: #fff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-file:focus {
            outline: none;
            border-color: #b23b3b;
            box-shadow: 0 0 0 4px rgba(178, 59, 59, 0.08);
        }

        .form-textarea {
            min-height: 140px;
            resize: vertical;
        }

        .preview-box {
            margin-top: 10px;
            background: #faf7f7;
            border: 1px solid #f0e5e5;
            border-radius: 10px;
            padding: 12px;
        }

        .preview-box img {
            max-width: 220px;
            max-height: 160px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        .helper-text {
            color: #666;
            font-size: 12px;
            margin-top: 4px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #f0e4e4;
        }

        .secondary-btn,
        .primary-btn {
            border: none;
            border-radius: 10px;
            padding: 11px 20px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .secondary-btn {
            background: #f4f4f4;
            color: #333;
        }

        .primary-btn {
            background: linear-gradient(135deg, #b23b3b, #8a2f2f);
            color: #fff;
        }

        .secondary-btn:hover,
        .primary-btn:hover {
            opacity: 0.97;
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .dashboard-content,
            .dashboard-content > div,
            .company-form-card,
            .company-form-grid,
            .form-group {
                min-width: 0;
                max-width: 100%;
                box-sizing: border-box;
            }

            .dashboard-content > div {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .company-form-card {
                padding: 20px 16px;
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

            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .form-actions button,
            .form-actions a {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                box-sizing: border-box;
                text-align: center;
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

        /* ===== CONTENT ===== */
        .dashboard-content {
            flex: 1;
            background: #fff;
            padding: 20px;
        }

        /* ===== POSTING OPTIONS BUTTONS===== */

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

            .company-form-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
        }

        :root {
            --company-toast-success: #15803d;
            --company-toast-error: #b91c1c;
            --company-toast-text: #1f2937;
            --company-toast-surface: #ffffff;
            --company-toast-shadow: 0 18px 45px rgba(15, 23, 42, 0.18);
        }

        .company-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 12000;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: min(390px, calc(100vw - 32px));
            padding: 16px 18px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-left: 4px solid var(--company-toast-accent);
            border-radius: 14px;
            background: var(--company-toast-surface);
            color: var(--company-toast-text);
            box-shadow: var(--company-toast-shadow);
            opacity: 0;
            transform: translate3d(0, -14px, 0);
            transition: opacity 220ms ease, transform 220ms ease;
            pointer-events: none;
        }

        .company-toast.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0);
            pointer-events: auto;
        }

        .company-toast--success {
            --company-toast-accent: var(--company-toast-success);
        }

        .company-toast--error {
            --company-toast-accent: var(--company-toast-error);
        }

        .company-toast__icon {
            flex: 0 0 auto;
            margin-top: 1px;
            color: var(--company-toast-accent);
            font-size: 20px;
        }

        .company-toast__message {
            font-size: 15px;
            line-height: 1.5;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .company-toast {
                top: 16px;
                right: 16px;
                width: calc(100vw - 32px);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .company-toast {
                transition: none;
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
                <div style="padding: 20px; display: flex; justify-content: center; align-items: flex-start; min-height: 400px;">
                    <div style="max-width: 600px; width: 100%;">
                    
                    <?php
                    if ($company_data):
                    ?>
                    <div class="company-form-card">
                        <div class="company-form-header">
                            <h2><?= $lang['company_edit_title'] ?? 'Edit Company' ?></h2>
                            <div class="company-subnav">
                                <a href="../companyprofile.php?company_id=<?= (int) ($company_data['id'] ?? 0) ?>" class="back-link"><?= $lang['detail_company_link'] ?? 'Quay lại hồ sơ công ty' ?></a>
                            </div>
                        </div>

                        <form id="companyProfileForm" enctype="multipart/form-data">
                            <input type="hidden" name="company_id" value="<?= htmlspecialchars((string) $company_data['id'], ENT_QUOTES, 'UTF-8'); ?>">

                            <div class="company-form-grid">
                                <div class="form-group">
                                    <label for="companyName" class="form-label"><?= $lang['company_name_label'] ?></label>
                                    <input type="text" id="companyName" name="company_name" class="form-input" value="<?= htmlspecialchars((string) ($company_data['company_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="companyAddress" class="form-label"><?= $lang['company_address_label'] ?></label>
                                    <input type="text" id="companyAddress" name="company_address" class="form-input" value="<?= htmlspecialchars((string) ($company_data['company_address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </div>

                                <div class="form-group full-width">
                                    <label for="industry" class="form-label"><?= $lang['industry_label'] ?></label>
                                    <select id="industry" name="industry" class="form-input">
                                        <option value=""><?= $lang['chon_nganh_nghe'] ?></option>
                                        <option value="kinh_doanh_ban_hang" <?= (($company_data['industry'] ?? '') === 'kinh_doanh_ban_hang') ? 'selected' : '' ?>><?= $lang['kinh_doanh_ban_hang'] ?></option>
                                        <option value="marketing_truyen_thong" <?= (($company_data['industry'] ?? '') === 'marketing_truyen_thong') ? 'selected' : '' ?>><?= $lang['marketing_truyen_thong'] ?></option>
                                        <option value="tai_chinh_ke_toan_ngan_hang" <?= (($company_data['industry'] ?? '') === 'tai_chinh_ke_toan_ngan_hang') ? 'selected' : '' ?>><?= $lang['tai_chinh_ke_toan_ngan_hang'] ?></option>
                                        <option value="cong_nghe_thong_tin" <?= (($company_data['industry'] ?? '') === 'cong_nghe_thong_tin') ? 'selected' : '' ?>><?= $lang['cong_nghe_thong_tin'] ?></option>
                                        <option value="nhan_su" <?= (($company_data['industry'] ?? '') === 'nhan_su') ? 'selected' : '' ?>><?= $lang['nhan_su'] ?></option>
                                        <option value="hanh_chinh_van_phong" <?= (($company_data['industry'] ?? '') === 'hanh_chinh_van_phong') ? 'selected' : '' ?>><?= $lang['hanh_chinh_van_phong'] ?></option>
                                        <option value="ky_thuat" <?= (($company_data['industry'] ?? '') === 'ky_thuat') ? 'selected' : '' ?>><?= $lang['ky_thuat'] ?></option>
                                        <option value="san_xuat_van_hanh_day_chuyen" <?= (($company_data['industry'] ?? '') === 'san_xuat_van_hanh_day_chuyen') ? 'selected' : '' ?>><?= $lang['san_xuat_van_hanh_day_chuyen'] ?></option>
                                        <option value="van_tai_logistics" <?= (($company_data['industry'] ?? '') === 'van_tai_logistics') ? 'selected' : '' ?>><?= $lang['van_tai_logistics'] ?></option>
                                        <option value="bat_dong_san" <?= (($company_data['industry'] ?? '') === 'bat_dong_san') ? 'selected' : '' ?>><?= $lang['bat_dong_san'] ?></option>
                                        <option value="bao_hiem" <?= (($company_data['industry'] ?? '') === 'bao_hiem') ? 'selected' : '' ?>><?= $lang['bao_hiem'] ?></option>
                                        <option value="xay_dung_kien_truc" <?= (($company_data['industry'] ?? '') === 'xay_dung_kien_truc') ? 'selected' : '' ?>><?= $lang['xay_dung_kien_truc'] ?></option>
                                        <option value="y_te_duoc_pham" <?= (($company_data['industry'] ?? '') === 'y_te_duoc_pham') ? 'selected' : '' ?>><?= $lang['y_te_duoc_pham'] ?></option>
                                        <option value="giao_duc_dao_tao" <?= (($company_data['industry'] ?? '') === 'giao_duc_dao_tao') ? 'selected' : '' ?>><?= $lang['giao_duc_dao_tao'] ?></option>
                                        <option value="du_lich_khach_san_nha_hang" <?= (($company_data['industry'] ?? '') === 'du_lich_khach_san_nha_hang') ? 'selected' : '' ?>><?= $lang['du_lich_khach_san_nha_hang'] ?></option>
                                        <option value="luat" <?= (($company_data['industry'] ?? '') === 'luat') ? 'selected' : '' ?>><?= $lang['luat'] ?></option>
                                        <option value="thiet_ke_nghe_thuat" <?= (($company_data['industry'] ?? '') === 'thiet_ke_nghe_thuat') ? 'selected' : '' ?>><?= $lang['thiet_ke_nghe_thuat'] ?></option>
                                        <option value="lao_dong_pho_thong" <?= (($company_data['industry'] ?? '') === 'lao_dong_pho_thong') ? 'selected' : '' ?>><?= $lang['lao_dong_pho_thong'] ?></option>
                                        <option value="nong_nghiep" <?= (($company_data['industry'] ?? '') === 'nong_nghiep') ? 'selected' : '' ?>><?= $lang['nong_nghiep'] ?></option>
                                        <option value="food_beverage" <?= (($company_data['industry'] ?? '') === 'food_beverage') ? 'selected' : '' ?>><?= $lang['food_beverage'] ?></option>
                                        <option value="nha_nuoc_cong_vu" <?= (($company_data['industry'] ?? '') === 'nha_nuoc_cong_vu') ? 'selected' : '' ?>><?= $lang['nha_nuoc_cong_vu'] ?></option>
                                        <option value="khac" <?= (($company_data['industry'] ?? '') === 'khac') ? 'selected' : '' ?>><?= $lang['khac'] ?></option>
                                    </select>
                                    <input type="hidden" name="jobCategoryHidden" id="jobCategoryHidden">
                                    <div id="custom_input_wrapper2" style="display: none; margin-top: 8px;">
                                        <input type="text" id="custom_job_category" name="custom_job_category" placeholder="<?= $lang['custom_jobcategory_placeholder']; ?>">
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="company_size_select" class="form-label"><?= $lang['post_job_form_company_size'] ?></label>
                                    <select id="company_size_select" name="company_size_select" class="form-input" onchange="toggleCustomInput()" required>
                                        <option value=""><?= $lang['post_job_form_company_size_select'] ?></option>
                                        <option value="1-10" <?= ($company_size_value === '1-10') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['1-10'] ?></option>
                                        <option value="11-50" <?= ($company_size_value === '11-50') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['11-50'] ?></option>
                                        <option value="51-200" <?= ($company_size_value === '51-200') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['51-200'] ?></option>
                                        <option value="201-500" <?= ($company_size_value === '201-500') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['201-500'] ?></option>
                                        <option value="501-1000" <?= ($company_size_value === '501-1000') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['501-1000'] ?></option>
                                        <option value="1001-5000" <?= ($company_size_value === '1001-5000') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['1001-5000'] ?></option>
                                        <option value="5000+" <?= ($company_size_value === '5000+') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['5000+'] ?></option>
                                        <option value="unknown" <?= ($company_size_value === 'unknown') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['unknown'] ?></option>
                                        <option value="other" <?= ($company_size_is_custom || $company_size_value === 'other') ? 'selected' : '' ?>><?= $lang['post_job_form_company_size_options']['other'] ?></option>
                                    </select>
                                    <input type="hidden" name="company_size" id="company_size" value="<?= htmlspecialchars((string) $company_size_value, ENT_QUOTES, 'UTF-8'); ?>">
                                    <div id="custom_input_wrapper" style="display: <?= ($company_size_is_custom || $company_size_value === 'other') ? 'block' : 'none'; ?>; margin-top: 8px;">
                                        <input type="text" id="custom_company_size" name="custom_company_size" value="<?= htmlspecialchars((string) (($company_size_is_custom || $company_size_value === 'other') ? $company_size_value : ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?= $lang['custom_companysize_placeholder']; ?>" title="Enter like 10-150 or 5000+">
                                    </div>
                                </div>

                                <div class="form-group full-width">
                                    <label for="information" class="form-label"><?= $lang['company_information_label'] ?></label>
                                    <textarea id="information" name="information" class="form-textarea"><?= htmlspecialchars((string) ($company_data['information'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                </div>

                                <div class="form-group full-width">
                                    <label for="companyLogo" class="form-label"><?= $lang['company_logo_label'] ?></label>
                                    <?php if (!empty($company_data['company_logo'])): ?>
                                        <div class="preview-box">
                                            <p style="font-size: 14px; color: #666; margin: 0 0 10px;"><?= $lang['current_file'] ?? 'Current file' ?>: <?= htmlspecialchars(basename((string) $company_logo_preview)); ?></p>
                                            <img src="<?= htmlspecialchars((string) $company_logo_preview); ?>" alt="Company Logo">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="companyLogo" name="company_logo" accept=".jpg,.jpeg,.png,.gif,.webp" class="form-file">
                                    <div id="companyLogoPreview" class="preview-box" style="display:none;"></div>
                                    <small class="helper-text"><?= $lang['supported_image_formats'] ?></small>
                                </div>

                                <div class="form-group full-width">
                                    <label for="bannerLogo" class="form-label"><?= $lang['banner_logo_label'] ?></label>
                                    <?php if (!empty($company_data['banner_logo'])): ?>
                                        <div class="preview-box">
                                            <p style="font-size: 14px; color: #666; margin: 0 0 10px;"><?= $lang['current_file'] ?? 'Current file' ?>: <?= htmlspecialchars(basename((string) $banner_logo_preview)); ?></p>
                                            <img src="<?= htmlspecialchars((string) $banner_logo_preview); ?>" alt="Banner Logo">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="bannerLogo" name="banner_logo" accept=".jpg,.jpeg,.png,.gif,.webp" class="form-file">
                                    <div id="bannerLogoPreview" class="preview-box" style="display:none;"></div>
                                    <small class="helper-text"><?= $lang['supported_image_formats'] ?></small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="company_profile.php" class="secondary-btn"><?= $lang['cancel_button'] ?? 'Cancel' ?></a>
                                <button type="submit" class="primary-btn"><?= $lang['company_update_button'] ?></button>
                            </div>
                        </form>
                    </div>

                    <script>
                        const allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        const maxImageSize = 5 * 1024 * 1024;
                        const companyEditSuccessMessage = <?= json_encode($lang['company_edit_message_success'] ?? 'Edited version of your company is saved for reivewed.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        const companyEditErrorMessage = <?= json_encode($lang['company_profile_update_error'] ?? 'An error occurred while updating the company profile.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        const unknownErrorMessage = <?= json_encode($lang['unknown_error'] ?? 'Unknown error', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        const invalidImageTypeMessage = <?= json_encode($lang['invalid_file_format'] ?? 'Only JPG, JPEG, PNG, GIF, WEBP files are allowed.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        const fileTooLargeMessage = <?= json_encode($lang['file_too_large'] ?? 'File size must not exceed 5MB.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                        const companySizeRequiredMessage = <?= json_encode($lang['post_job_form_company_size_select'] ?? 'Please select company size.', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

                        function showCompanyToast(message, type = 'success') {
                            const previousToast = document.querySelector('.company-toast');
                            if (previousToast) {
                                previousToast.remove();
                            }

                            const toastType = type === 'error' ? 'error' : 'success';
                            const toast = document.createElement('div');
                            toast.className = `company-toast company-toast--${toastType}`;
                            toast.setAttribute('role', toastType === 'error' ? 'alert' : 'status');
                            toast.setAttribute('aria-live', toastType === 'error' ? 'assertive' : 'polite');

                            const icon = document.createElement('i');
                            icon.className = toastType === 'error'
                                ? 'fas fa-circle-exclamation company-toast__icon'
                                : 'fas fa-circle-check company-toast__icon';
                            icon.setAttribute('aria-hidden', 'true');

                            const messageElement = document.createElement('span');
                            messageElement.className = 'company-toast__message';
                            messageElement.textContent = message;

                            toast.append(icon, messageElement);
                            document.body.appendChild(toast);
                            requestAnimationFrame(() => toast.classList.add('is-visible'));

                            window.setTimeout(() => {
                                toast.classList.remove('is-visible');
                                window.setTimeout(() => toast.remove(), 240);
                            }, 3000);
                        }

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

                        function attachImagePreview(inputId, previewId) {
                            const input = document.getElementById(inputId);
                            const preview = document.getElementById(previewId);
                            if (!input || !preview) return;

                            input.addEventListener('change', function () {
                                const file = this.files && this.files[0];
                                if (!file) {
                                    preview.style.display = 'none';
                                    preview.innerHTML = '';
                                    return;
                                }

                                if (!allowedImageTypes.includes(file.type)) {
                                    showCompanyToast(invalidImageTypeMessage, 'error');
                                    this.value = '';
                                    preview.style.display = 'none';
                                    preview.innerHTML = '';
                                    return;
                                }

                                if (file.size > maxImageSize) {
                                    showCompanyToast(fileTooLargeMessage, 'error');
                                    this.value = '';
                                    preview.style.display = 'none';
                                    preview.innerHTML = '';
                                    return;
                                }

                                const reader = new FileReader();
                                reader.onload = function (event) {
                                    preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview" style="max-width:220px;max-height:160px;object-fit:cover;border-radius:8px;">';
                                    preview.style.display = 'block';
                                };
                                reader.readAsDataURL(file);
                            });
                        }

                        attachImagePreview('companyLogo', 'companyLogoPreview');
                        attachImagePreview('bannerLogo', 'bannerLogoPreview');

                        const companySizeSelect = document.getElementById('company_size_select');
                        const companySizeHidden = document.getElementById('company_size');
                        const customCompanySize = document.getElementById('custom_company_size');

                        if (companySizeSelect) {
                            companySizeSelect.addEventListener('change', toggleCustomInput);
                        }

                        if (customCompanySize && companySizeHidden) {
                            customCompanySize.addEventListener('input', function () {
                                if (companySizeSelect && companySizeSelect.value === 'other') {
                                    companySizeHidden.value = customCompanySize.value.trim();
                                }
                            });
                        }

                        document.getElementById('companyProfileForm').onsubmit = function(e) {
                            e.preventDefault();

                            if (!companySizeHidden || !companySizeHidden.value.trim()) {
                                showCompanyToast(companySizeRequiredMessage, 'error');
                                return;
                            }

                            const formData = new FormData(this);
                            const filesToCheck = ['company_logo', 'banner_logo'];
                            let valid = true;

                            filesToCheck.forEach(function (fieldName) {
                                const file = formData.get(fieldName);
                                if (!file || !(file instanceof File) || file.size === 0) return;
                                if (!allowedImageTypes.includes(file.type)) {
                                    valid = false;
                                    showCompanyToast(invalidImageTypeMessage, 'error');
                                }
                                if (file.size > maxImageSize) {
                                    valid = false;
                                    showCompanyToast(fileTooLargeMessage, 'error');
                                }
                            });

                            if (!valid) return;

                            const submitButton = this.querySelector('button[type="submit"]');
                            if (submitButton) {
                                submitButton.disabled = true;
                            }

                            fetch('update_company_profile.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error(`Request failed with status ${response.status}`);
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    showCompanyToast(companyEditSuccessMessage, 'success');
                                    window.setTimeout(() => {
                                        window.location.reload();
                                    }, 1800);
                                } else {
                                    if (submitButton) {
                                        submitButton.disabled = false;
                                    }
                                    showCompanyToast(`${companyEditErrorMessage}: ${data.message || unknownErrorMessage}`, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                if (submitButton) {
                                    submitButton.disabled = false;
                                }
                                showCompanyToast(companyEditErrorMessage, 'error');
                            });
                        }
                    </script>
                    <?php
                    else:
                        echo '<div style="text-align: center; color: #888; padding: 40px;">' . ($lang['no_company_profile'] ?? 'No company profile found.') . '</div>';
                    endif;
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
</body>

<?php include 'footer.php' ?>

</html>