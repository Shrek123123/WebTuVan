<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['employerid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config.php';

$employerid = $_SESSION['employerid'];
$company_id = $_POST['company_id'] ?? 0;
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

// Validate company_id belongs to this employer
$sql_check = "SELECT id FROM company WHERE id = ? AND employer_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $company_id, $employerid);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
if ($result_check->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Company not found']);
    exit();
}
$stmt_check->close();

// Handle file uploads
$company_logo = '';
if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['company_logo'];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

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
    $max_size = 5 * 1024 * 1024; // 5MB
    
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
        echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
        exit();
    }
}

// Update database and send the company back for review after any edit.
$company_status = 'pending_review';
$sql_update = "UPDATE company SET company_name = ?, company_address = ?, industry = ?, company_size = ?, information = ?, status = ?";
$params = [$company_name, $company_address, $industry, $company_size, $information, $company_status];
$types = "ssssss";

if (!empty($company_logo)) {
    $sql_update .= ", company_logo = ?";
    $params[] = $company_logo;
    $types .= "s";
}

if (!empty($banner_logo)) {
    $sql_update .= ", banner_logo = ?";
    $params[] = $banner_logo;
    $types .= "s";
}

$sql_update .= " WHERE id = ? AND employer_id = ?";
$params[] = $company_id;
$params[] = $employerid;
$types .= "ii";

$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param($types, ...$params);

if ($stmt_update->execute()) {
    echo json_encode(['success' => true, 'message' => 'Company profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update company profile']);
}

$stmt_update->close();
$conn->close();
?>
