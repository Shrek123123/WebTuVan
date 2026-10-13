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
$employername = $_SESSION['usernameemployer'] ?? 'Employer';
$employer_id = isset($_SESSION['employerid']) ? (int) $_SESSION['employerid'] : 0;
$form_message = '';
$form_error = '';
$current_profile_pic = '../image/defaultavatar.jpg';
$upload_max_size = 2 * 1024 * 1024;
$normalize_profile_pic = function ($path) {
    if (empty($path)) {
        return '../image/defaultavatar.jpg';
    }

    $normalized = trim($path);
    if (preg_match('/^(https?:|data:)/i', $normalized)) {
        return $normalized;
    }

    $normalized = ltrim($normalized, '/');
    if (strpos($normalized, '../') === 0) {
        return $normalized;
    }

    if (strpos($normalized, 'uploads/') === 0) {
        return '../' . $normalized;
    }

    return '../uploads/' . basename($normalized);
};

if ($employer_id > 0) {
    $profile_stmt = $conn->prepare("SELECT profile_pic FROM employer_profile WHERE employer_id = ? LIMIT 1");
    if ($profile_stmt) {
        $profile_stmt->bind_param('i', $employer_id);
        $profile_stmt->execute();
        $profile_result = $profile_stmt->get_result();
        if ($profile_result && $profile_result->num_rows > 0) {
            $profile_row = $profile_result->fetch_assoc();
            if (!empty($profile_row['profile_pic'])) {
                $current_profile_pic = $normalize_profile_pic($profile_row['profile_pic']);
            }
        }
        $profile_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic']) && !empty($_FILES['profile_pic']['name'])) {
    if ($employer_id <= 0) {
        $form_error = $lang['profile_upload_missing_employer'] ?? 'Employer information is missing.';
    } else {
        $upload_dir = '../uploads/employerprofilepic';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file = $_FILES['profile_pic'];
        $tmp_name = $file['tmp_name'];
        $original_name = $file['name'];
        $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        $mime_type = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $form_error = $lang['profile_upload_failed'] ?? 'Upload failed. Please try again.';
        } elseif (!in_array($file_ext, $allowed_ext, true)) {
            $form_error = $lang['profile_upload_invalid_type'] ?? 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.';
        } elseif (!empty($mime_type) && !in_array($mime_type, $allowed_types, true)) {
            $form_error = $lang['profile_upload_invalid_type'] ?? 'Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.';
        } elseif ($file['size'] > $upload_max_size) {
            $max_size_mb = round($upload_max_size / 1024 / 1024, 1);
            $form_error = sprintf($lang['profile_upload_too_large'] ?? 'Image size must be smaller than %sMB.', $max_size_mb);
        } elseif (!is_uploaded_file($tmp_name)) {
            $form_error = $lang['profile_upload_invalid_file'] ?? 'Invalid uploaded file.';
        } else {
            $new_name = 'employer_' . $employer_id . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . '/' . $new_name;

            if (move_uploaded_file($tmp_name, $target_path)) {
                $db_path = 'uploads/employerprofilepic/' . $new_name;
                $find_stmt = $conn->prepare("SELECT id FROM employer_profile WHERE employer_id = ? LIMIT 1");
                $exists = false;

                if ($find_stmt) {
                    $find_stmt->bind_param('i', $employer_id);
                    $find_stmt->execute();
                    $find_result = $find_stmt->get_result();
                    $exists = ($find_result && $find_result->num_rows > 0);
                    $find_stmt->close();
                }

                if ($exists) {
                    $update_stmt = $conn->prepare("UPDATE employer_profile SET profile_pic = ? WHERE employer_id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param('si', $db_path, $employer_id);
                        if ($update_stmt->execute()) {
                            $form_message = $lang['profile_upload_success'] ?? 'Profile picture updated successfully.';
                            $current_profile_pic = '../' . $db_path;
                        } else {
                            $form_error = $lang['profile_upload_update_failed'] ?? 'Unable to update profile picture.';
                        }
                        $update_stmt->close();
                    }
                } else {
                    $insert_stmt = $conn->prepare("INSERT INTO employer_profile (employer_id, profile_pic) VALUES (?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param('is', $employer_id, $db_path);
                        if ($insert_stmt->execute()) {
                            $form_message = $lang['profile_upload_success'] ?? 'Profile picture saved successfully.';
                            $current_profile_pic = '../' . $db_path;
                        } else {
                            $form_error = $lang['profile_upload_insert_failed'] ?? 'Unable to save profile picture.';
                        }
                        $insert_stmt->close();
                    }
                }
            } else {
                $form_error = $lang['profile_upload_move_failed'] ?? 'Could not move uploaded file to the server.';
            }
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

        .avatar-card {
            max-width: 700px;
            margin: 30px auto;
            background: #fff;
            border: 1px solid #f0d8d8;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(178, 59, 59, 0.08);
            padding: 28px;
        }

        .avatar-header {
            font-size: 28px;
            color: #b23b3b;
            margin: 0 0 20px;
        }

        .avatar-layout {
            display: flex;
            gap: 28px;
            align-items: center;
            flex-wrap: wrap;
        }

        .avatar-preview {
            width: 170px;
            height: 170px;
            border-radius: 50%;
            border: 3px solid #f1d7d7;
            object-fit: cover;
            background: #f7f7f7;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
        }

        .avatar-upload-box {
            flex: 1;
            min-width: 260px;
        }

        .avatar-upload-box label {
            display: block;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .avatar-upload-box input[type="file"] {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #d9d9d9;
            border-radius: 10px;
            background: #fff;
        }

        .avatar-upload-box input[type="file"]:focus {
            border-color: #b23b3b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(178, 59, 59, 0.08);
        }

        .avatar-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        .avatar-btn {
            border: none;
            border-radius: 10px;
            padding: 11px 18px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .avatar-btn.cancel {
            background: #f3f3f3;
            color: #333;
        }

        .avatar-btn.save {
            background: linear-gradient(135deg, #b23b3b, #8a2f2f);
            color: #fff;
        }

        .avatar-alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin: 0 0 18px;
            font-size: 14px;
        }

        .avatar-alert.success {
            background: #edf9f0;
            border: 1px solid #bfe7c9;
            color: #1e7d45;
        }

        .avatar-alert.error {
            background: #fff1f1;
            border: 1px solid #f5c6c6;
            color: #a02b2b;
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

            .avatar-card {
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
                <div class="avatar-card">
                    <h2 class="avatar-header"><?= htmlspecialchars($lang['change_employer_avatar_action'] ?? 'Change profile picture') ?></h2>

                    <?php if (!empty($form_message)): ?>
                        <div class="avatar-alert success"><?= htmlspecialchars($form_message) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($form_error)): ?>
                        <div class="avatar-alert error"><?= htmlspecialchars($form_error) ?></div>
                    <?php endif; ?>

                    <div class="avatar-layout">
                        <img src="<?= htmlspecialchars($current_profile_pic) ?>" alt="Current profile picture" class="avatar-preview">

                        <div class="avatar-upload-box">
                            <form method="POST" action="change_profile_pic.php" enctype="multipart/form-data">
                                <label for="profile_pic"><?= htmlspecialchars($lang['choose_image_label'] ?? 'Choose image') ?></label>
                                <input id="profile_pic" type="file" name="profile_pic" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <div style="margin-top: 8px; color: #666; font-size: 12px;">
                                    <?= htmlspecialchars($lang['image_validation_note'] ?? 'Allowed: JPG, JPEG, PNG, GIF, WEBP. Maximum size: 2MB.') ?>
                                </div>

                                <div class="avatar-actions">
                                    <a href="company_profile.php" class="avatar-btn cancel"><?= htmlspecialchars($lang['back_to_profile'] ?? 'Back') ?></a>
                                    <button type="submit" class="avatar-btn save"><?= htmlspecialchars($lang['save_changes'] ?? 'Save') ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alerts = document.querySelectorAll('.avatar-alert');
            alerts.forEach((alert) => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.4s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 400);
                }, 5000);
            });

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

        const fileInput = document.getElementById('profile_pic');
        const previewImage = document.querySelector('.avatar-preview');
        if (fileInput && previewImage) {
            fileInput.addEventListener('change', function () {
                const file = this.files && this.files[0];
                if (!file) return;

                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImage.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>

<?php include 'footer.php' ?>

</html>