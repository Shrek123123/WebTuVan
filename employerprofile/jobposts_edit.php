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

            .form-grid {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
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
                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                include '../lang/' . $_SESSION['lang'] . '.php';

                // 1. CẬP NHẬT JOB_ID VÀO SESSION (Nếu vừa nhấn nút Sửa từ trang danh sách gửi POST qua)
                if (isset($_POST['id_to_edit'])) {
                    $_SESSION['edit_job_id'] = intval($_POST['id_to_edit']);
                }

                // 2. LẤY JOB_ID TỪ SESSION ĐỂ SỬ DỤNG
                $job_id = isset($_SESSION['edit_job_id']) ? intval($_SESSION['edit_job_id']) : 0;

                $message = "";
                $message_type = ""; // 'success' hoặc 'error'

                // --- 3. XỬ LÝ UPDATE KHI NGƯỜI DÙNG NHẤN NÚT LƯU ---
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
                    // Lấy ID từ session thay vì tin tưởng vào $_POST['job_id'] để bảo mật hơn
                    $posted_by_employer_id = intval($_POST['posted_by_employer_id']);

                    $job_title = $_POST['job_title'] ?? '';
                    $job_description = $_POST['job_description'] ?? '';
                    $job_benefit = $_POST['job_benefit'] ?? '';
                    $job_requirement = $_POST['job_requirement'] ?? '';
                    $job_location = $_POST['job_location'] ?? '';
                    $job_location_district = $_POST['jobLocationDistrict'] ?? '';
                    $no_employee_needed = intval($_POST['no_employee_needed'] ?? 0);
                    $salary = $_POST['salary'] ?? '';
                    $contact_email = $_POST['contact_email'] ?? '';
                    $contact_phone = $_POST['contact_phone'] ?? '';
                    $job_type = $_POST['job_type'] ?? '';
                    $job_position = $_POST['jobPosition'] ?? '';
                    $required_certification = $_POST['required_certification'] ?? '';
                    $job_experience = $_POST['job_experience'] ?? '';

                    // Validate
                    if ($job_id <= 0 || $posted_by_employer_id <= 0 || empty($job_title) || empty($job_description) || empty($job_location) || $no_employee_needed <= 0 || empty($salary) || empty($contact_email)) {
                        $message = $lang['validation_error'] ?? "Vui lòng điền đầy đủ các thông tin bắt buộc.";
                        $message_type = "error";
                    } else {
                        if (isset($conn) && $conn->ping()) {
                            $beforeJob = null;
                            $beforeStmt = $conn->prepare('SELECT j.*, c.company_name FROM hoa_giai_vien j LEFT JOIN company c ON c.id = j.company_id WHERE j.id = ? AND j.posted_by_employer_id = ? LIMIT 1');
                            if ($beforeStmt) {
                                $beforeStmt->bind_param('ii', $job_id, $posted_by_employer_id);
                                if ($beforeStmt->execute()) {
                                    $beforeJob = $beforeStmt->get_result()->fetch_assoc();
                                }
                                $beforeStmt->close();
                            }

                            $newJobFields = [
                                'job_title' => $job_title,
                                'job_description' => $job_description,
                                'job_benefit' => $job_benefit,
                                'job_requirement' => $job_requirement,
                                'job_location' => $job_location,
                                'job_location_district' => $job_location_district,
                                'no_employee_needed' => $no_employee_needed,
                                'salary' => $salary,
                                'contact_email' => $contact_email,
                                'contact_phone' => $contact_phone,
                                'job_type' => $job_type,
                                'job_position' => $job_position,
                                'required_certification' => $required_certification,
                                'job_experience' => $job_experience,
                            ];
                            $jobFieldLabels = [
                                'job_title' => 'Vị trí tuyển dụng',
                                'job_description' => 'Mô tả công việc',
                                'job_benefit' => 'Quyền lợi',
                                'job_requirement' => 'Yêu cầu công việc',
                                'job_location' => 'Địa điểm làm việc',
                                'job_location_district' => 'Quận/huyện',
                                'no_employee_needed' => 'Số lượng tuyển dụng',
                                'salary' => 'Mức lương',
                                'contact_email' => 'Email liên hệ',
                                'contact_phone' => 'Số điện thoại liên hệ',
                                'job_type' => 'Hình thức làm việc',
                                'job_position' => 'Cấp bậc',
                                'required_certification' => 'Chứng chỉ yêu cầu',
                                'job_experience' => 'Kinh nghiệm yêu cầu',
                            ];

                            $sql_update = "UPDATE hoa_giai_vien SET
                                job_title = ?, job_description = ?,
                                job_benefit = ?, job_requirement = ?, job_location = ?, job_location_district = ?, no_employee_needed = ?,
                                salary = ?, contact_email = ?, contact_phone = ?,
                                job_type = ?, job_position = ?, required_certification = ?, job_experience = ?,
                                status = 'pending_review', last_updated_at = CURRENT_TIMESTAMP
                            WHERE id = ? AND posted_by_employer_id = ?";

                            $stmt = $conn->prepare($sql_update);
                            $stmt->bind_param(
                                "ssssssisssssssii",
                                $job_title,
                                $job_description,
                                $job_benefit,
                                $job_requirement,
                                $job_location,
                                $job_location_district,
                                $no_employee_needed,
                                $salary,
                                $contact_email,
                                $contact_phone,
                                $job_type,
                                $job_position,
                                $required_certification,
                                $job_experience,
                                $job_id,
                                $posted_by_employer_id
                            );

                            if ($stmt->execute()) {
                                if ($stmt->affected_rows > 0 && $beforeJob) {
                                    $changedFieldLabels = getChangedJobFieldLabels($beforeJob, $newJobFields, $jobFieldLabels);
                                    $companyName = (string) ($beforeJob['company_name'] ?? '');
                                    sendJobUpdateNotificationEmails($conn, $job_id, $job_title, $companyName, $changedFieldLabels);
                                }
                                echo "<script>alert('" . addslashes($lang['job_post_is_saved_for_review']) . "'); window.location.href = window.location.href;</script>";
                                exit;
                            } else {
                                $message = "Lỗi database: " . $stmt->error;
                                $message_type = "error";
                            }
                            $stmt->close();
                        }
                    }
                }

                // --- 4. LẤY DỮ LIỆU ĐỂ HIỂN THỊ LÊN FORM ---
                $job_data = null;
                if ($job_id > 0 && isset($conn) && $conn->ping()) {
                    $sql_select = "SELECT * FROM hoa_giai_vien WHERE id = ?";
                    $stmt_sel = $conn->prepare($sql_select);
                    $stmt_sel->bind_param("i", $job_id);
                    $stmt_sel->execute();
                    $job_data = $stmt_sel->get_result()->fetch_assoc();
                    $stmt_sel->close();
                }
                ?>

                <style>
                    .edit-form-container {
                        padding: 25px;
                        background: #fff;
                        border-radius: 10px;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                        max-width: 900px;
                        margin: auto;
                    }

                    .edit-form-container h2 {
                        color: #b23b3b;
                        margin-bottom: 20px;
                        border-bottom: 2px solid #eee;
                        padding-bottom: 10px;
                    }

                    .form-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 20px;
                    }

                    .full-row {
                        grid-column: span 2;
                    }

                    label {
                        display: block;
                        font-weight: bold;
                        margin-bottom: 5px;
                        color: #444;
                    }

                    input,
                    textarea,
                    select {
                        width: 100%;
                        padding: 10px;
                        border: 1px solid #ccc;
                        border-radius: 6px;
                        box-sizing: border-box;
                    }

                    textarea {
                        height: 120px;
                    }

                    .btn-submit {
                        background: #b23b3b;
                        color: #fff;
                        border: none;
                        padding: 15px;
                        border-radius: 6px;
                        cursor: pointer;
                        font-size: 16px;
                        font-weight: bold;
                        width: 100%;
                        margin-top: 20px;
                    }

                    .btn-submit:hover {
                        background: #d90000;
                    }

                    .msg {
                        padding: 15px;
                        border-radius: 6px;
                        margin-bottom: 20px;
                        text-align: center;
                        font-weight: bold;
                    }

                    .msg-success {
                        background: #d4edda;
                        color: #155724;
                        border: 1px solid #c3e6cb;
                    }

                    .msg-error {
                        background: #f8d7da;
                        color: #721c24;
                        border: 1px solid #f5c6cb;
                    }

                    .back-link {
                        text-decoration: none;
                        color: #b23b3b;
                        font-weight: bold;
                        display: inline-block;
                        margin-bottom: 15px;
                    }
                </style>

                <div class="edit-form-container">
                    <a href="my_job_posts.php" class="back-link">←
                        <?= $lang['back_to_list'] ?? 'Quay lại danh sách' ?></a>

                    <h2><?php echo $lang['edit_job_post_modal_title']; ?></h2>

                    <?php if ($message): ?>
                        <div class="msg msg-<?= $message_type ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <?php if ($job_data): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="posted_by_employer_id"
                                value="<?= htmlspecialchars($job_data['posted_by_employer_id']) ?>">

                            <div class="form-grid">
                                <div class="full-row">
                                    <label><?= $lang['edit_job_post_modal_job_title'] ?>*</label>
                                    <input type="text" name="job_title"
                                        value="<?= htmlspecialchars($job_data['job_title']) ?>" required>
                                </div>

                                <div class="full-row">
                                    <label><?= $lang['edit_job_post_modal_job_description'] ?>*</label>
                                    <textarea name="job_description"
                                        required><?= htmlspecialchars($job_data['job_description']) ?></textarea>
                                </div>

                                <div class="full-row">
                                    <label><?= $lang['edit_job_post_modal_job_requirement'] ?></label>
                                    <textarea
                                        name="job_requirement"><?= htmlspecialchars($job_data['job_requirement']) ?></textarea>
                                </div>

                                <div class="full-row">
                                    <label><?= $lang['edit_job_post_modal_job_benefit'] ?></label>
                                    <textarea
                                        name="job_benefit"><?= htmlspecialchars($job_data['job_benefit']) ?></textarea>
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_job_location'] ?>*</label>
                                    <input type="text" name="job_location"
                                        value="<?= htmlspecialchars($job_data['job_location']) ?>" required>
                                </div>

                                <div>
                                    <label for="jobLocationDistrict"><?= $lang['post_job_form_job_location_district']; ?>:</label>
                                    <input type="text" id="jobLocationDistrict" name="jobLocationDistrict"
                                        value="<?= htmlspecialchars($job_data['job_location_district'] ?? '') ?>"
                                        placeholder="<?= $lang['optional']; ?>">
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_salary'] ?>*</label>
                                    <input type="text" name="salary" value="<?= htmlspecialchars($job_data['salary']) ?>"
                                        required>
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_no_employee_needed'] ?>*</label>
                                    <input type="number" name="no_employee_needed"
                                        value="<?= $job_data['no_employee_needed'] ?>" required min="1">
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_contact_email'] ?>*</label>
                                    <input type="email" name="contact_email"
                                        value="<?= htmlspecialchars($job_data['contact_email']) ?>" required>
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_contact_phone'] ?></label>
                                    <input type="text" name="contact_phone"
                                        value="<?= htmlspecialchars($job_data['contact_phone']) ?>">
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_job_type'] ?>*</label>
                                    <select name="job_type" required>
                                        <?php
                                        $types = ['Full-time', 'Part-time', 'Freelance', 'Internship', 'Remote'];
                                        foreach ($types as $t):
                                            ?>
                                            <option value="<?= $t ?>" <?= ($job_data['job_type'] == $t) ? 'selected' : '' ?>>
                                                <?= $t ?>
                                            </option>
                                            <?php
                                        endforeach;
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="jobPosition"><?= $lang['post_job_form_job_position']; ?></label>
                                    <select id="jobPosition" name="jobPosition" required>
                                        <option value=""><?= $lang['post_job_form_job_position']; ?></option>
                                        <?php foreach (['senior', 'junior', 'cong_tac_vien', 'manager', 'director', 'lead', 'staff', 'specialist', 'assistant', 'executive', 'other'] as $positionValue): ?>
                                            <option value="<?= htmlspecialchars($positionValue) ?>" <?= (($job_data['job_position'] ?? '') === $positionValue) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(translateJobPosition($positionValue, $lang)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_required_certification'] ?></label>
                                    <input type="text" name="required_certification"
                                        value="<?= htmlspecialchars($job_data['required_certification']) ?>">
                                </div>

                                <div>
                                    <label><?= $lang['edit_job_post_modal_job_experience'] ?></label>
                                    <?php
                                    $job_experience_value = trim((string) ($job_data['job_experience'] ?? ''));
                                    $job_experience_key = normalizeJobExperienceKey($job_experience_value);
                                    ?>
                                    <select name="job_experience" required>
                                        <option value="" <?= $job_experience_key === '' ? 'selected' : '' ?>>
                                            <?= $lang['post_job_form_experience_placeholder']; ?>
                                        </option>
                                        <?php foreach ($lang['post_job_form_experience_options'] as $value => $label): ?>
                                            <option value="<?= htmlspecialchars($value) ?>" <?= $job_experience_key === $value ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($label) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <button type="submit" name="submit_update"
                                class="btn-submit"><?php echo $lang['edit_job_post_modal_submit']; ?></button>
                        </form>
                    <?php else: ?>
                        <p class="msg msg-error">Không tìm thấy thông tin bài đăng hoặc phiên làm việc đã hết hạn.</p>
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