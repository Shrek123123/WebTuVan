<?php
require_once '../config.php'; // Đảm bảo đường dẫn đến config.php là chính xác

header('Content-Type: application/json'); // Thiết lập header để trình duyệt biết đây là JSON

// Kiểm tra xem request có phải là POST và chứa các tham số cần thiết không
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id']) && isset($_POST['jobseeker_id'])) {
    $job_id = intval($_POST['job_id']);
    $jobseeker_id = intval($_POST['jobseeker_id']);

    // Kiểm tra tính hợp lệ của ID
    if ($job_id <= 0 || $jobseeker_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid Job ID or Jobseeker ID.', 'error' => 'Invalid ID']);
        exit;
    }

    if (isset($conn) && $conn->ping()) {
        // Chuẩn bị câu lệnh SQL để xóa
        $sql = "DELETE FROM saved_jobs WHERE job_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $job_id, $jobseeker_id);

            if ($stmt->execute()) {
                // Kiểm tra xem có hàng nào bị ảnh hưởng không (tức là có xóa được không)
                if ($stmt->affected_rows > 0) {
                    echo json_encode(['success' => true, 'message' => 'Job removed from saved list.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Job not found in saved list or already removed.', 'error' => 'No rows affected']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error during deletion.', 'error' => $conn->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.', 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection error.', 'error' => 'DB Connect']);
    }
} else {
    // Nếu request không hợp lệ
    echo json_encode(['success' => false, 'message' => 'Invalid request method or missing parameters.', 'error' => 'Invalid Request']);
}

exit; // Đảm bảo không có gì khác được in ra sau JSON
?>