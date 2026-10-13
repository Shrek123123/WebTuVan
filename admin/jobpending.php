<?php
// admin/jobpending.php
session_start();
require_once '../config.php'; // Điều chỉnh đường dẫn đến config.php

// --- Kiểm tra quyền Admin (quan trọng) ---
if (!isset($_SESSION['usernameadmin'])) { // Giả định 'usernameadmin' là biến session cho admin
    header('Location: ../index.php'); // Chuyển hướng đến trang đăng nhập admin
    exit();
}
$sql = "SELECT j.id, j.job_title, c.company_name, j.posted_by_employer_id
            FROM hoa_giai_vien j
            LEFT JOIN company c ON c.id = j.company_id
            WHERE j.status = 'pending_review'
            ORDER BY j.created_at ASC";
        $result = $conn->query($sql);

 $pending_jobs_badge = $result->num_rows;
        $res_pending_companies = $conn->query("SELECT COUNT(*) AS total FROM company WHERE status = 'pending_review'");
        $pending_companies_badge = 0;
        if ($res_pending_companies) {
            $pending_companies_badge = (int) ($res_pending_companies->fetch_assoc()['total'] ?? 0);
            $res_pending_companies->close();
        }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản trị - Duyệt tin tuyển dụng chờ phê duyệt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f0f2;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0b1e45;
            text-align: center;
            margin-bottom: 25px;
        }
        .back-button {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.2s;
        }
        .back-button:hover {
            background-color: #0056b3;
        }
        .job-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: background-color 0.2s;
        }
        .job-item:hover {
            background-color: #f0f0f0;
            cursor: pointer;
        }
        .job-info {
            flex-grow: 1;
        }
        .job-info h3 {
            margin: 0 0 5px 0;
            color: #15294c;
        }
        .job-info p {
            margin: 0;
            color: #555;
            font-size: 0.9em;
        }
        .job-item a {
            text-decoration: none;
            color: inherit; /* Kế thừa màu chữ từ cha */
            display: block; /* Làm cho toàn bộ thẻ job là một liên kết */
        }
        .no-jobs {
            text-align: center;
            color: #888;
            padding: 30px;
            font-size: 1.1em;
            background-color: #fefefe;
            border-radius: 8px;
            border: 1px dashed #ccc;
        }

        /* --- Sidebar đồng bộ --- */
        .sidebar {
            width: 200px;
            background-color: #0b1e45;
            color: white;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px 10px;
            overflow-y: auto;
        }

        .sidebar h2 { margin-top: 0; }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 20px 0;
            padding: 10px;
            background-color: #15294c;
            border-radius: 5px;
        }

        .sidebar ul li:hover {
            background-color: #1e3a66;
            cursor: pointer;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .sidebar ul li a:hover { color: white; }

        .user-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #4caf50;
            border-radius: 50%;
        }

        .nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 10px;
            background: #e74c3c;
            color: #fff;
            font-size: 12px;
            font-weight: bold;
            line-height: 1;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/mobile_sidebar.php'; ?>
    <div class="sidebar">
        <h2>Quản trị</h2>
        <div class="user-status">
            <div class="status-dot"></div>
            <span>Trực tuyến</span>
        </div>
        <ul>
            <li><a href="#">Thay đổi ảnh trang chủ</a></li>
            <li><a href="#">Người dùng</a></li>
            <li><a href="jobpending.php">Duyệt tin tuyển dụng
                    <?php if ($pending_jobs_badge > 0): ?><span class="nav-badge"><?php echo $pending_jobs_badge; ?></span><?php endif; ?>
                </a></li>
            <li><a href="company_check.php">Duyệt thông tin công ty
                    <?php if ($pending_companies_badge > 0): ?><span class="nav-badge"><?php echo $pending_companies_badge; ?></span><?php endif; ?>
                </a></li>
            <li><a href="user_table.php">Duyệt người dùng</a></li>
            <li><a href="feedbackcheck.php">Phản hồi</a></li>
        </ul>
    </div>

    <div class="container" style="margin-left: 240px;">
        <a href="admindashboard.php" class="back-button">&larr; Quay lại bảng điều khiển</a>
        <h1>Tin tuyển dụng đang chờ duyệt</h1>

        <?php
        $sql = "SELECT j.id, j.job_title, c.company_name, j.posted_by_employer_id
            FROM hoa_giai_vien j
            LEFT JOIN company c ON c.id = j.company_id
            WHERE j.status = 'pending_review'
            ORDER BY j.created_at ASC";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                ?>
                <div class="job-item">
                    <a href="jobpendingdetail.php?id=<?php echo htmlspecialchars($row['id']); ?>">
                        <div class="job-info">
                            <h3><?php echo htmlspecialchars($row['job_title']); ?></h3>
                            <p><strong>Công ty:</strong> <?php echo htmlspecialchars($row['company_name']); ?></p>
                            <p><strong>Đăng bởi nhà tuyển dụng mã số:</strong> <?php echo htmlspecialchars($row['posted_by_employer_id']); ?></p>
                        </div>
                    </a>
                </div>
                <?php
            }
        } else {
            echo '<div class="no-jobs">Hiện không có tin tuyển dụng nào đang chờ duyệt.</div>';
        }

        // --- Đếm số mục chờ duyệt cho badge trên sidebar ---
       

        $conn->close();
        ?>
    </div>
</body>

</html>