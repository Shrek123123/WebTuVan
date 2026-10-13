<?php
session_start();
header('Content-Type: application/json');
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include '../lang/' . $_SESSION['lang'] . '.php';
// Kiểm tra đăng nhập
if (!isset($_SESSION['employerid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config.php';
require_once __DIR__ . '/../helpers.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employerid = $_SESSION['employerid'];
    $application_id = $_POST['application_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($action === 'hide') {
        // Ẩn đơn ứng tuyển
        $sql = "UPDATE application SET hidden_from_employer = 1 WHERE id = ? AND job_id IN (SELECT id FROM job WHERE posted_by_employer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $application_id, $employerid);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Application hidden successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to hide application']);
        }
        $stmt->close();
        
    } elseif (isset($_POST['status'])) {
        // Cập nhật trạng thái
        $new_status = $_POST['status'];
        $allowed_statuses = ['pending', 'interviewed', 'hired', 'rejected'];
        
        if (!in_array($new_status, $allowed_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit();
        }

        $detailsStmt = $conn->prepare(
            "SELECT a.email, j.job_title, c.company_name
             FROM application a
             JOIN job j ON a.job_id = j.id
             JOIN company c ON j.company_id = c.id
             WHERE a.id = ? AND j.posted_by_employer_id = ?
             LIMIT 1"
        );
        $detailsStmt->bind_param("ii", $application_id, $employerid);
        $detailsStmt->execute();
        $detailsResult = $detailsStmt->get_result();
        $applicationDetails = $detailsResult->fetch_assoc();
        $detailsStmt->close();

        if (!$applicationDetails) {
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            exit();
        }
        
        $sql = "UPDATE application SET status = ? WHERE id = ? AND job_id IN (SELECT id FROM job WHERE posted_by_employer_id = ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $application_id, $employerid);
        
        if ($stmt->execute()) {
            sendApplicationStatusEmail(
                $applicationDetails['email'],
                $applicationDetails['job_title'],
                $applicationDetails['company_name'],
                $new_status
            );
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>