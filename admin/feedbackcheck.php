<?php
session_start();
require_once '../config.php'; // Adjust path as admindashboard.php is in admin folder

// Check if admin is logged in, otherwise redirect
if (!isset($_SESSION['usernameadmin'])) {
    header('Location: ../index.php'); // Redirect to login page or home page
    exit();
}

$feedbacks = []; // Initialize an empty array to store feedbacks

// Fetch feedbacks from the database
if (isset($conn) && $conn->ping()) {
    // Select all columns from the feedback table, and join with user table to get username
    $sql = "SELECT f.id, f.user_id, f.user_type, f.content, f.star_rating, f.created_at, u.username
            FROM feedback f
            JOIN user u ON f.user_id = u.id
            ORDER BY f.created_at DESC"; // Order by most recent feedback

    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = $row;
        }
        $result->free(); // Free result set
    } else {
        // Handle query error
        echo "Lỗi truy vấn phản hồi: " . $conn->error;
    }
    // Đếm số mục chờ duyệt cho badge trên sidebar (job + company)
    $pending_jobs_badge = 0;
    $pending_companies_badge = 0;
    $res_pending_jobs = $conn->query("SELECT COUNT(*) AS total FROM hoa_giai_vien WHERE status = 'pending_review'");
    if ($res_pending_jobs) {
        $pending_jobs_badge = (int) ($res_pending_jobs->fetch_assoc()['total'] ?? 0);
        $res_pending_jobs->close();
    }
    $res_pending_companies = $conn->query("SELECT COUNT(*) AS total FROM company WHERE status = 'pending_review'");
    if ($res_pending_companies) {
        $pending_companies_badge = (int) ($res_pending_companies->fetch_assoc()['total'] ?? 0);
        $res_pending_companies->close();
    }
    $conn->close();
} else {
    echo "Lỗi kết nối cơ sở dữ liệu.";
    $pending_jobs_badge = 0;
    $pending_companies_badge = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phản hồi - Quản trị</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f0f2;
            margin: 0;
            padding: 0;
            display: flex;
        }

        .sidebar {
            width: 200px;
            background-color: #0b1e45;
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 10px;
        }

        .sidebar h2 {
            margin-top: 0;
        }

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
            display: block;
        }

        .sidebar ul li a:hover {
            color: white;
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #4caf50;
            border-radius: 50%;
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
            flex-grow: 1;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .feedback-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .feedback-card {
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }

        .feedback-user-info {
            font-weight: bold;
            color: #333;
        }

        .feedback-date {
            font-size: 0.85em;
            color: #777;
        }

        .feedback-content {
            margin-bottom: 10px;
            line-height: 1.6;
            color: #555;
        }

        .feedback-rating {
            font-size: 1.1em;
            color: #f4b400; /* Google's yellow for stars */
            margin-top: 5px;
        }

        .feedback-rating .star {
            display: inline-block;
            margin-right: 2px;
        }

        .feedback-rating .star.filled {
            color: #f4b400;
        }
        .feedback-rating .star.empty {
            color: #ccc;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
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

    <div class="main-content">
        <div class="top-bar">
            <h1>Phản hồi từ người dùng</h1>
            <form method="post" action="adminlogout.php" style="display: inline;">
                <button type="submit" name="admin_logout"
                    style="padding: 8px 16px; background: #e74c3c; color: #fff; border-radius: 5px; border: none; font-weight: bold; cursor: pointer;">
                    Đăng xuất
                </button>
            </form>
        </div>

        <div class="feedback-container">
            <?php if (empty($feedbacks)): ?>
                <p style="text-align: center; color: #888;">Chưa có phản hồi nào.</p>
            <?php else: ?>
                <?php foreach ($feedbacks as $feedback): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div class="feedback-user-info">
                                Từ: <?php echo htmlspecialchars($feedback['username']); ?>
                                (<?php echo htmlspecialchars($feedback['user_type']); ?>)
                            </div>
                            <div class="feedback-date">
                                <?php echo date('d/m/Y, H:i', strtotime($feedback['created_at'])); ?>
                            </div>
                        </div>
                        <div class="feedback-content">
                            <?php echo nl2br(htmlspecialchars($feedback['content'])); ?>
                        </div>
                        <div class="feedback-rating">
                            Đánh giá:
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $feedback['star_rating']) {
                                    echo '<span class="star filled">&#9733;</span>'; // Filled star
                                } else {
                                    echo '<span class="star empty">&#9733;</span>'; // Empty star
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>