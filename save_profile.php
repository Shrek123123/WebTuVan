<?php
session_start();
include_once 'lang/' . $_SESSION['lang'] . '.php'; // Nạp ngôn ngữ từ session
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$jobseeker_id = $_SESSION['jobseeker_id'] ?? null;
if (!$jobseeker_id) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập']);
    exit;
}

// Lấy dữ liệu
$full_name = trim($_POST['full_name'] ?? '');
$phone_number = trim($_POST['phone_number'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone_pattern = '/^(?:0(?:3|5|7|8|9)\d{8}|\+84(?:3|5|7|8|9)\d{8})$/';
if ($phone_number === '') {
    echo json_encode(['success' => false, 'message' => $lang['phone_number_required'] ?? 'Please enter your phone number.']);
    exit;
}
if (!preg_match($phone_pattern, $phone_number)) {
    echo json_encode(['success' => false, 'message' => $lang['invalid_phone_number'] ?? 'Please enter a valid Vietnamese phone number.']);
    exit;
}
$date_of_birth = isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
$gender = $_POST['gender'] ?? '';
$marital_status = $_POST['marital_status'] ?? '';
$profile_description = trim($_POST['profile_description'] ?? '');


$validGenders = ['Male', 'Female', 'Other', 'Prefer not to say'];
$validStatus = ['Single', 'Married', 'Other'];


require_once __DIR__ . '/config.php';
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => "Lỗi kết nối DB: " . $mysqli->connect_error]);
    exit;
}

// Cập nhật username và số điện thoại trong bảng user
$stmtUser = $mysqli->prepare("UPDATE user SET username = ?, phone_number = ? WHERE id = ?");
$stmtUser->bind_param("ssi", $full_name, $phone_number, $jobseeker_id);
if (!$stmtUser->execute()) {
    $user_update_error = $stmtUser->errno;
    $user_update_message = $stmtUser->error;
    $stmtUser->close();
    $message = $user_update_error === 1062
        ? ($lang['phone_number_in_use'] ?? 'This phone number is already in use.')
        : "Lỗi cập nhật tên người dùng: " . $user_update_message;
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}
$stmtUser->close();

// Kiểm tra bản ghi ho_so_khach_hang đã tồn tại
$stmtCheck = $mysqli->prepare("SELECT COUNT(*) FROM ho_so_khach_hang WHERE jobseeker_id = ?");
$stmtCheck->bind_param("i", $jobseeker_id);
$stmtCheck->execute();
$stmtCheck->bind_result($count);
$stmtCheck->fetch();
$stmtCheck->close();

if ($count > 0) {
    $stmt = $mysqli->prepare("UPDATE ho_so_khach_hang SET
        profile_description = ?, gender = ?, address = ?, date_of_birth = ?, marital_status = ?
        WHERE jobseeker_id = ?");
    $stmt->bind_param("sssssi", $profile_description, $gender, $address, $date_of_birth, $marital_status, $jobseeker_id);
} else {
    $stmt = $mysqli->prepare("INSERT INTO ho_so_khach_hang
        (jobseeker_id, profile_description, gender, address, date_of_birth, marital_status)
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $jobseeker_id, $profile_description, $gender, $address, $date_of_birth, $marital_status);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $lang['luu_ho_so_thanh_cong']]);
} else {
    echo json_encode(['success' => false, 'message' => $lang['loi_luu_ho_so'] . $stmt->error]);
}

$stmt->close();
$mysqli->close();
