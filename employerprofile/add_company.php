<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['employerid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

$employerid = (int) $_SESSION['employerid'];
$company_name = trim((string) ($_POST['company_name'] ?? ''));
$company_address = trim((string) ($_POST['company_address'] ?? ''));
$industry = trim((string) ($_POST['industry'] ?? ''));
$company_size = trim((string) ($_POST['company_size'] ?? ''));
if (!empty($_POST['custom_company_size'])) {
    $company_size = trim((string) $_POST['custom_company_size']);
}
$information = trim((string) ($_POST['information'] ?? ''));

if ($company_name === '' || $company_address === '' || $industry === '' || $company_size === '') {
    echo json_encode(['success' => false, 'message' => 'Company name, address, industry and company size are required.']);
    exit();
}

$company_logo = '';
if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['company_logo'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024;

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, JPEG, PNG, GIF, WEBP are allowed.']);
        exit();
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit();
    }

    $upload_dir = '../uploads/companylogo/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_filename = 'company_logo_' . $employerid . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $company_logo = '../uploads/companylogo/' . $new_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload company logo.']);
        exit();
    }
}

$banner_logo = '';
if (isset($_FILES['banner_logo']) && $_FILES['banner_logo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['banner_logo'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024;

    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_ext)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Only JPG, JPEG, PNG, GIF, WEBP are allowed.']);
        exit();
    }

    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit.']);
        exit();
    }

    $upload_dir = '../uploads/companylogo/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_filename = 'banner_' . $employerid . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        $banner_logo = '../uploads/companylogo/' . $new_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload banner logo.']);
        exit();
    }
}

$company_status = 'pending_review';
$sql = "INSERT INTO company (employer_id, company_name, company_address, industry, company_size, information, company_logo, banner_logo, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("issssssss", $employerid, $company_name, $company_address, $industry, $company_size, $information, $company_logo, $banner_logo, $company_status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Company saved for review.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to insert company: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
