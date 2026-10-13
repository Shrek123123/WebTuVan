<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $employer_id = isset($_SESSION['employerid']) ? intval($_SESSION['employerid']) : 0;

    if ($job_id <= 0 || $employer_id <= 0) {
        $response['message'] = 'Invalid data';
        echo json_encode($response);
        exit();
    }

    $sql = "DELETE FROM job WHERE id = ? AND posted_by_employer_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $job_id, $employer_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Deleted successfully';
        } else {
            $response['message'] = 'Không có quyền hoặc bài không tồn tại';
        }

        $stmt->close();
    } else {
        $response['message'] = 'SQL error';
    }

    echo json_encode($response);
    exit();
}