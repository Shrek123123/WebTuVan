<?php
// admin/process_job_review.php
session_start();
require_once '../config.php'; // Điều chỉnh đường dẫn đến config.php
require_once __DIR__ . '/../helpers.php';

// --- Kiểm tra quyền Admin (quan trọng) ---
if (!isset($_SESSION['usernameadmin'])) {
    // Nếu không phải admin, chuyển hướng hoặc hiển thị lỗi
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $jobId = intval($_POST['job_id']);
    $action = $_POST['action'];
    $adminNote = trim((string) ($_POST['admin_note'] ?? ''));

    $newStatus = '';
    if ($action === 'approve') {
        $newStatus = 'approved';
    } elseif ($action === 'reject') {
        // Bắt buộc phải có lý do từ chối
        if ($adminNote === '') {
            echo "<script>alert('Vui lòng nhập lý do từ chối công việc này!'); window.history.back();</script>";
            exit();
        }
        $newStatus = 'rejected';
    } else {
        // Hành động không hợp lệ
        echo "<script>alert('Hành động không hợp lệ!'); window.history.back();</script>";
        exit();
    }

    $detailsStmt = $conn->prepare("SELECT j.job_title, c.company_name, u.email AS employer_email
        FROM job j
        LEFT JOIN company c ON c.id = j.company_id
        JOIN user u ON u.id = j.posted_by_employer_id
        WHERE j.id = ?
        LIMIT 1");
    $jobDetails = null;
    if ($detailsStmt) {
        $detailsStmt->bind_param('i', $jobId);
        $detailsStmt->execute();
        $detailsResult = $detailsStmt->get_result();
        $jobDetails = $detailsResult ? $detailsResult->fetch_assoc() : null;
        $detailsStmt->close();
    }

    if (!$jobDetails) {
        echo "<script>alert('Không tìm thấy tin tuyển dụng.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("UPDATE job SET status = ?, admin_notes = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $newStatus, $adminNote, $jobId);
        if ($stmt->execute()) {
            sendJobReviewEmail(
                $jobDetails['employer_email'],
                $jobDetails['job_title'],
                $jobDetails['company_name'],
                $newStatus,
                $adminNote
            );
            echo "<script>alert('Đã " . ($newStatus === 'approved' ? 'phê duyệt' : 'từ chối') . " tin tuyển dụng thành công!'); window.location.href = 'jobpending.php';</script>";
        } else {
            echo "<script>alert('Lỗi khi cập nhật trạng thái tin tuyển dụng: " . $stmt->error . "'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Lỗi khi chuẩn bị truy vấn: " . $conn->error . "'); window.history.back();</script>";
    }
    $conn->close();
    exit();
} else {
    // Yêu cầu không hợp lệ (không phải POST hoặc thiếu dữ liệu)
    echo "<script>alert('Yêu cầu không hợp lệ!'); window.history.back();</script>";
    exit();
}
?>
