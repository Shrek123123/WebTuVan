<?php
require_once '../config.php'; // Đảm bảo config.php được bao gồm
require_once '../helpers.php';
session_start(); // Bắt đầu phiên làm việc
include '../lang/' . $_SESSION['lang'] . '.php';


header('Content-Type: application/json'); // Trả về JSON response

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ form
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $posted_by_employer_id = isset($_POST['posted_by_employer_id']) ? intval($_POST['posted_by_employer_id']) : 0;

    $company_name = $_POST['company_name'] ?? '';
    $company_size = $_POST['company_size'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    $job_description = $_POST['job_description'] ?? '';
    $job_benefit = $_POST['job_benefit'] ?? '';
    $job_requirement = $_POST['job_requirement'] ?? '';
    $job_location = $_POST['job_location'] ?? '';
    $no_employee_needed = $_POST['no_employee_needed'] ?? 0;
    $salary = $_POST['salary'] ?? '';
    $post_duration = $_POST['post_duration'] ?? 0;
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';
    $job_type = $_POST['job_type'] ?? '';
    $job_category = $_POST['job_category'] ?? '';
    $required_certification = $_POST['required_certification'] ?? '';
    $job_experience = $_POST['job_experience'] ?? '';
    $company_logo = $_POST['company_logo'] ?? '';

    // Validate essential fields
    if ($job_id <= 0 || $posted_by_employer_id <= 0 || empty($company_name) || empty($job_title) || empty($job_description) || empty($job_location) || $no_employee_needed <= 0 || empty($salary) || $post_duration <= 0 || empty($contact_email) || empty($job_type) || empty($job_category)) {
        $response['message'] = 'Missing or invalid required fields.';
        echo json_encode($response);
        exit();
    }

    if (isset($conn) && $conn->ping()) {
        $beforeJob = null;
        $beforeStmt = $conn->prepare('SELECT * FROM hoa_giai_vien WHERE id = ? AND posted_by_employer_id = ? LIMIT 1');
        if ($beforeStmt) {
            $beforeStmt->bind_param('ii', $job_id, $posted_by_employer_id);
            if ($beforeStmt->execute()) {
                $beforeJob = $beforeStmt->get_result()->fetch_assoc();
            }
            $beforeStmt->close();
        }

        $newJobFields = [
            'company_name' => $company_name,
            'company_size' => $company_size,
            'job_title' => $job_title,
            'job_description' => $job_description,
            'job_benefit' => $job_benefit,
            'job_requirement' => $job_requirement,
            'job_location' => $job_location,
            'no_employee_needed' => $no_employee_needed,
            'salary' => $salary,
            'post_duration' => $post_duration,
            'contact_email' => $contact_email,
            'contact_phone' => $contact_phone,
            'job_type' => $job_type,
            'job_category' => $job_category,
            'required_certification' => $required_certification,
            'job_experience' => $job_experience,
            'company_logo' => $company_logo,
        ];
        $jobFieldLabels = [
            'company_name' => 'Tên công ty',
            'company_size' => 'Quy mô công ty',
            'job_title' => 'Vị trí tuyển dụng',
            'job_description' => 'Mô tả công việc',
            'job_benefit' => 'Quyền lợi',
            'job_requirement' => 'Yêu cầu công việc',
            'job_location' => 'Địa điểm làm việc',
            'no_employee_needed' => 'Số lượng tuyển dụng',
            'salary' => 'Mức lương',
            'post_duration' => 'Thời hạn đăng tin',
            'contact_email' => 'Email liên hệ',
            'contact_phone' => 'Số điện thoại liên hệ',
            'job_type' => 'Hình thức làm việc',
            'job_category' => 'Danh mục công việc',
            'required_certification' => 'Chứng chỉ yêu cầu',
            'job_experience' => 'Kinh nghiệm yêu cầu',
            'company_logo' => 'Logo công ty',
        ];

        $sql = "UPDATE hoa_giai_vien SET
                    company_name = ?,
                    company_size = ?,
                    job_title = ?,
                    job_description = ?,
                    job_benefit = ?,
                    job_requirement = ?,
                    job_location = ?,
                    no_employee_needed = ?,
                    salary = ?,
                    post_duration = ?,
                    contact_email = ?,
                    contact_phone = ?,
                    job_type = ?,
                    job_category = ?,
                    required_certification = ?,
                    job_experience = ?,
                    company_logo = ?,
                    last_updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND posted_by_employer_id = ?"; // Đảm bảo chỉ employer đó mới được sửa bài của mình

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param(
                "sssssssisisssssssii", // Chuỗi định nghĩa kiểu dữ liệu mới
                $company_name,
                $company_size,
                $job_title,
                $job_description,
                $job_benefit,
                $job_requirement,
                $job_location,
                $no_employee_needed, // i
                $salary,
                $post_duration, // i
                $contact_email,
                $contact_phone,
                $job_type,
                $job_category,
                $required_certification,
                $job_experience,
                $company_logo,
                $job_id, // i (cho WHERE)
                $posted_by_employer_id // i (cho WHERE)
            );

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = $lang['job_update_success'];
                    if ($beforeJob) {
                        $changedFieldLabels = getChangedJobFieldLabels($beforeJob, $newJobFields, $jobFieldLabels);
                        sendJobUpdateNotificationEmails($conn, $job_id, $job_title, $company_name, $changedFieldLabels);
                    }
                } else {
                    $response['message'] = $lang['no_changes_made'] ?? 'No changes made or job not found/authorized.';
                }
            } else {
                $response['message'] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement: ' . $conn->error;
        }
    } else {
        $response['message'] = 'Database connection error.';
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
// Không đóng kết nối $conn ở đây nếu nó được dùng lại ở body.php
// $conn->close();
?>