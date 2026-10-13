<?php
session_start();
// Điều chỉnh đường dẫn tới config.php vì admindashboard.php nằm trong thư mục admin
require_once '../config.php'; // Giả định config.php nằm ở thư mục gốc của project

if (empty($_SESSION['usernameadmin']) || empty($_SESSION['admin_id'])) {
    header('Location: adminlogin.php');
    exit();
}
$adminId = (int) $_SESSION['admin_id'];
$adminStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin' AND status = 'active' LIMIT 1");
if (!$adminStmt) {
    throw new RuntimeException('Could not verify administrator: ' . $conn->error);
}
$adminStmt->bind_param('i', $adminId);
$adminStmt->execute();
$isAdmin = (bool) $adminStmt->get_result()->fetch_assoc();
$adminStmt->close();
if (!$isAdmin) {
    session_destroy();
    header('Location: adminlogin.php');
    exit();
}

// --- Lấy dữ liệu động từ database ---
$total_customer_accounts = 0;
$total_mediators = 0;
$online_mediators = 0;
$user_feedbacks = 0;

$customerResult = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
if ($customerResult && $customerResult->num_rows > 0) {
    $total_customer_accounts = (int) $customerResult->fetch_assoc()['total'];
}

$result_mediators = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'online'), 0) AS online_total FROM mediators");
if ($result_mediators && $result_mediators->num_rows > 0) {
    $row = $result_mediators->fetch_assoc();
    $total_mediators = (int) $row['total'];
    $online_mediators = (int) $row['online_total'];
}

// Lấy tổng số User Feedbacks
$sql_feedbacks = "SELECT COUNT(*) AS total FROM reviews";
$result_feedbacks = $conn->query($sql_feedbacks);
if ($result_feedbacks && $result_feedbacks->num_rows > 0) {
    $row = $result_feedbacks->fetch_assoc();
    $user_feedbacks = $row['total'];
}

// Đóng kết nối database sau khi đã lấy xong dữ liệu
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <link rel="icon" type="image/svg+xml" href="../image/favicon.svg">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bảng điều khiển quản trị</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f3f0f2;
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

        /* Thêm style cho thẻ a trong li để không bị gạch chân và màu mặc định */
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
            /* Để toàn bộ li là khu vực click */
        }

        .sidebar ul li a:hover {
            color: white;
            /* Giữ nguyên màu khi hover */
        }

        .main {
            margin-left: 220px;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cards {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }

        .card {
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            color: white;
            text-align: center;
        }

        .yellow {
            background-color: #f4b400;
        }

        .green {
            background-color: #0f9d58;
        }

        .blue {
            background-color: #4285f4;
        }

        .purple {
            background-color: #9b59b6;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .box {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
        }

        .user-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-dot {
            height: 10px;
            width: 10px;
            background-color: #4caf50;
            border-radius: 50%;
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
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
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
            <li><a href="mediators.php">Quản lý hòa giải viên</a></li>
            <li><a href="bookings.php">Quản lý lịch tư vấn</a></li>
            <li><a href="user_table.php">Duyệt người dùng</a></li>
        </ul>
    </div>
    <div class="main">
        <div class="top-bar">
            <h1>Bảng điều khiển</h1>
            <div>
                <img src="../image/logo.png" alt="Profile" style="width: 200px; height: 48px; border-radius: 10px; object-fit: cover;">
            </div>
            <form method="post" action="adminlogout.php" style="display: inline;">
                <button type="submit" name="admin_logout"
                    style="padding: 8px 16px; background: #e74c3c; color: #fff; border-radius: 5px; border: none; font-weight: bold; cursor: pointer;">
                    Đăng xuất
                </button>
            </form>
        </div>
        <div class="cards">
            <div class="card yellow">
                <h2><?php echo $total_customer_accounts; ?></h2>
                <p>Tổng tài khoản khách hàng</p>
            </div>
            <div class="card green">
                <h2><?php echo $total_mediators; ?></h2>
                <p>Tổng hòa giải viên</p>
            </div>
            <div class="card blue">
                <h2><?php echo $online_mediators; ?></h2>
                <p>Đang trực tuyến</p>
            </div>
            <div class="card purple">
                <h2><?php echo $user_feedbacks; ?></h2>
                <p>Đánh giá tư vấn</p>
            </div>
        </div>
        <div class="content-grid">
            <div class="box">
                <img src="https://www.presentationgo.com/wp-content/uploads/2018/12/Free-Growth-Arrow-PowerPoint-Template-Chart.png"
                    width="100%" alt="Graph" />
            </div>
            <div class="box">
                <h3>Hoạt động truy cập</h3>
                <ul>
                    <li>Quản lý tài khoản người dùng</li>
                    <li>Tra cứu hồ sơ hòa giải viên</li>
                    <li>Xem phản hồi người dùng</li>
                </ul>
            </div>
        </div>
        <div class="content-grid" style="margin-top: 20px;">
            <div class="box">
                <h3>Thông báo</h3>
            </div>
            <div class="box">
                <h3>Lượt xem trang</h3>
            </div>
        </div>
    </div>
</body>

</html>