<?php
function sendAvatarUploadResponse(array $response, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

set_error_handler(static function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
header('Content-Type: application/json; charset=utf-8');

try {
session_start();
require_once __DIR__ . '/../config.php';

$language = $_SESSION['lang'] ?? 'vi';
if (!in_array($language, ['vi', 'en'], true)) {
    $language = 'vi';
}
include __DIR__ . '/../lang/' . $language . '.php';

if (!isset($_SESSION['jobseeker_id'])) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.'], 401);
}

$jobseeker_id = (int) $_SESSION['jobseeker_id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Phương thức gửi yêu cầu không hợp lệ.'], 405);
}

if (!isset($_FILES['avatar'])) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Không có file được tải lên.'], 400);
}

$file = $_FILES['avatar'];
$uploadErrors = [
    UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn upload của máy chủ.',
    UPLOAD_ERR_FORM_SIZE => 'File vượt quá giới hạn cho phép.',
    UPLOAD_ERR_PARTIAL => 'File chỉ được tải lên một phần. Vui lòng thử lại.',
    UPLOAD_ERR_NO_FILE => 'Vui lòng chọn ảnh đại diện.',
    UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm để nhận file.',
    UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi file tải lên.',
    UPLOAD_ERR_EXTENSION => 'Một tiện ích PHP đã dừng việc tải file.',
];
if ($file['error'] !== UPLOAD_ERR_OK) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => $uploadErrors[$file['error']] ?? 'File tải lên không hợp lệ.'], 400);
}

$allowed_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];
$max_size = 5 * 1024 * 1024;

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$mime_type = '';
if (function_exists('finfo_open')) {
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    if ($file_info) {
        $mime_type = finfo_file($file_info, $file['tmp_name']);
        finfo_close($file_info);
    }
}

if (!is_uploaded_file($file['tmp_name']) || !isset($allowed_types[$extension]) || (!empty($mime_type) && $mime_type !== $allowed_types[$extension])) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Ảnh đại diện chỉ hỗ trợ định dạng JPG, JPEG hoặc PNG.'], 400);
}

if ($file['size'] > $max_size) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => $lang['upload_file_too_large'] ?? 'Ảnh không được vượt quá 5MB.'], 400);
}

$upload_dir = __DIR__ . '/../uploads/jobseekerprofilepic';
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true) && !is_dir($upload_dir)) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Không thể tạo thư mục uploads/jobseekerprofilepic.'], 500);
}

$new_filename = 'jobseeker_' . $jobseeker_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$destination = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Không thể chuyển ảnh vào thư mục uploads/jobseekerprofilepic. Kiểm tra quyền ghi thư mục.'], 500);
}

$db_path = 'uploads/jobseekerprofilepic/' . $new_filename;
$find_stmt = $conn->prepare('SELECT id FROM jobseeker_profile WHERE jobseeker_id = ? LIMIT 1');
if (!$find_stmt) {
    unlink($destination);
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Không thể kiểm tra hồ sơ ứng viên: ' . $conn->error], 500);
}
$find_stmt->bind_param('i', $jobseeker_id);
$find_stmt->execute();
$find_result = $find_stmt->get_result();
$exists = $find_result && $find_result->num_rows > 0;
$find_stmt->close();

if ($exists) {
    $save_stmt = $conn->prepare('UPDATE jobseeker_profile SET profile_pic = ? WHERE jobseeker_id = ?');
    if ($save_stmt) {
        $save_stmt->bind_param('si', $db_path, $jobseeker_id);
        $saved = $save_stmt->execute();
        $save_error = $save_stmt->error;
        $save_stmt->close();
    } else {
        $saved = false;
        $save_error = $conn->error;
    }
} else {
    $save_stmt = $conn->prepare('INSERT INTO jobseeker_profile (jobseeker_id, profile_pic) VALUES (?, ?)');
    if ($save_stmt) {
        $save_stmt->bind_param('is', $jobseeker_id, $db_path);
        $saved = $save_stmt->execute();
        $save_error = $save_stmt->error;
        $save_stmt->close();
    } else {
        $saved = false;
        $save_error = $conn->error;
    }
}

if (!$saved) {
    unlink($destination);
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Không thể lưu ảnh vào hồ sơ: ' . $save_error], 500);
}

sendAvatarUploadResponse([
    'status' => 'success',
    'message' => $lang['upload_success'] ?? 'Ảnh đại diện đã được tải lên thành công!',
    'path' => $db_path
]);
} catch (Throwable $error) {
    error_log('Avatar upload failed: ' . $error->getMessage());
    sendAvatarUploadResponse(['status' => 'error', 'message' => 'Lỗi upload ảnh: ' . $error->getMessage()], 500);
}