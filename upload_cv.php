<?php
session_start();
include 'config.php';
header('Content-Type: application/json');

$respond = static function (array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
};

if(!isset($_SESSION['jobseeker_id'])){
    $respond(['success'=>false,'message'=>'Chưa đăng nhập'], 401);
}

$jobseeker_id = (int)$_SESSION['jobseeker_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(['success'=>false,'message'=>'Phương thức gửi không hợp lệ'], 405);
}

if (!isset($_FILES['cv_file'])) {
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $message = $contentLength > 0
        ? 'Không nhận được file. File có thể vượt quá giới hạn upload của máy chủ.'
        : 'Vui lòng chọn file CV';
    $respond(['success'=>false,'message'=>$message], 400);
}

$file = $_FILES['cv_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'File vượt quá giới hạn upload của máy chủ.',
        UPLOAD_ERR_FORM_SIZE => 'File quá lớn theo giới hạn của biểu mẫu.',
        UPLOAD_ERR_PARTIAL => 'File chỉ được tải lên một phần, vui lòng thử lại.',
        UPLOAD_ERR_NO_FILE => 'Vui lòng chọn file CV.',
        UPLOAD_ERR_NO_TMP_DIR => 'Máy chủ thiếu thư mục tạm để nhận file.',
        UPLOAD_ERR_CANT_WRITE => 'Máy chủ không thể ghi file lên ổ đĩa.',
        UPLOAD_ERR_EXTENSION => 'Một tiện ích trên máy chủ đã chặn việc tải file.'
    ];
    $respond(['success'=>false,'message'=>$uploadErrors[$file['error']] ?? 'Lỗi upload không xác định.'], 400);
}

$allowedExt = ['doc','docx','pdf'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    $respond(['success'=>false,'message'=>'Định dạng file không hợp lệ'], 400);
}

if ($file['size'] > 5 * 1024 * 1024) {
    $respond(['success'=>false,'message'=>'File quá lớn (max 5MB)'], 400);
}

$uploadDir = 'uploads/cv_file/';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $respond(['success'=>false,'message'=>'Không thể tạo thư mục lưu CV trên máy chủ.'], 500);
}

$newFileName = $jobseeker_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$targetPath = $uploadDir . $newFileName;
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    $respond(['success'=>false,'message'=>'Máy chủ không thể lưu file CV. Vui lòng thử lại.'], 500);
}

$checkStmt = $conn->prepare("SELECT id FROM ho_so_khach_hang WHERE jobseeker_id=?");
if (!$checkStmt) {
    @unlink($targetPath);
    $respond(['success'=>false,'message'=>'Không thể kiểm tra hồ sơ ứng viên trên máy chủ.'], 500);
}
$checkStmt->bind_param("i", $jobseeker_id);
$checkStmt->execute();
$checkStmt->store_result();
$profileExists = $checkStmt->num_rows > 0;
$checkStmt->close();

if ($profileExists) {
    $stmt = $conn->prepare("UPDATE ho_so_khach_hang SET cv_file=?, updated_at=NOW() WHERE jobseeker_id=?");
    if ($stmt) {
        $stmt->bind_param("si", $targetPath, $jobseeker_id);
    }
} else {
    $stmt = $conn->prepare("INSERT INTO ho_so_khach_hang(jobseeker_id,cv_file) VALUES(?,?)");
    if ($stmt) {
        $stmt->bind_param("is", $jobseeker_id, $targetPath);
    }
}

if (!$stmt || !$stmt->execute()) {
    @unlink($targetPath);
    $respond(['success'=>false,'message'=>'Không thể cập nhật CV vào hồ sơ. Vui lòng thử lại.'], 500);
}

$stmt->close();
$respond(['success'=>true,'file_name'=>$file['name']]);
