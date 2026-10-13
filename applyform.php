<?php
require_once 'config.php';
require_once __DIR__ . '/helpers.php';
session_start();
// Nếu người dùng bấm đổi ngôn ngữ
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Nếu chưa có ngôn ngữ thì mặc định là tiếng Việt
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}

// Load file ngôn ngữ tương ứng
include 'lang/' . $_SESSION['lang'] . '.php';
$job_id = (int) ($_GET['id'] ?? 0);
$user_email = '';
$user_phone_number = '';

if (!empty($_SESSION['jobseeker_id'])) {
    $contact_stmt = $conn->prepare('SELECT email, phone_number FROM user WHERE id = ? LIMIT 1');
    if ($contact_stmt) {
        $contact_jobseeker_id = (int) $_SESSION['jobseeker_id'];
        $contact_stmt->bind_param('i', $contact_jobseeker_id);
        $contact_stmt->execute();
        $contact_result = $contact_stmt->get_result();
        if ($contact = $contact_result->fetch_assoc()) {
            $user_email = trim((string) ($contact['email'] ?? ''));
            $user_phone_number = trim((string) ($contact['phone_number'] ?? ''));
        }
        $contact_stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $respond = function ($payload, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    };

    if (!isset($_SESSION['username'], $_SESSION['role']) || $_SESSION['role'] !== 'jobseeker') {
        $respond(['success' => false, 'error' => 'You must be logged in as a job seeker to apply for a job.']);
    }

    $jobseeker_id = (int) ($_SESSION['jobseeker_id'] ?? 0);
    $job_id = (int) ($_POST['job_id'] ?? 0);
    if ($jobseeker_id <= 0 || $job_id <= 0) {
        $respond(['success' => false, 'error' => 'Invalid job ID.']);
    }

    // Kiểm tra trước khi upload CV hoặc ghi dữ liệu mới.
    $duplicate_stmt = $conn->prepare(
        'SELECT id FROM application WHERE job_id = ? AND jobseeker_id = ? LIMIT 1'
    );
    if (!$duplicate_stmt) {
        $respond(['success' => false, 'error' => 'Unable to verify the existing application. Please try again.']);
    }

    $duplicate_stmt->bind_param('ii', $job_id, $jobseeker_id);
    $duplicate_stmt->execute();
    $duplicate_stmt->store_result();
    $has_applied = $duplicate_stmt->num_rows > 0;
    $duplicate_stmt->close();

    if ($has_applied) {
        $respond([
            'success' => false,
            'error' => $lang['you_have_apply_before_warning'] ?? 'You have applied to this job before and therefore cannot apply again.'
        ]);
    }

    $fullname = trim((string) ($_POST['fullname'] ?? ''));
    $email = $user_email;
    $submitted_phone = trim((string) ($_POST['phone_number'] ?? ''));
    $phone = $user_phone_number !== '' ? $user_phone_number : $submitted_phone;
    $cover_letter = trim((string) ($_POST['cover_letter'] ?? ''));
    $cover_letter_length = function_exists('mb_strlen') ? mb_strlen($cover_letter, 'UTF-8') : strlen($cover_letter);
    $allow_search = isset($_POST['allow_search']) ? 1 : 0;
    $phone_pattern = '/^(?:0(?:3|5|7|8|9)\d{8}|\+84(?:3|5|7|8|9)\d{8})$/';

    if ($email === '') {
        $respond(['success' => false, 'error' => 'Unable to load your account email. Please try again.']);
    }

    if ($phone === '' && $user_phone_number === '') {
        $respond(['success' => false, 'error' => $lang['phone_number_required'] ?? 'Please enter your phone number.']);
    }

    if ($user_phone_number === '' && !preg_match($phone_pattern, $phone)) {
        $respond(['success' => false, 'error' => $lang['invalid_phone_number'] ?? 'Please enter a valid Vietnamese phone number.']);
    }

    if ($cover_letter_length > 5000) {
        $respond(['success' => false, 'error' => $lang['cover_letter_too_long'] ?? 'Cover letter must not exceed 5,000 characters.']);
    }

    if (!isset($_FILES['cv_file'])) {
        $content_length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        $message = $content_length > 0
            ? 'The CV could not be received. It may exceed the server upload limit.'
            : 'Please upload a valid CV file.';
        $respond(['success' => false, 'error' => $message], 400);
    }

    $uploaded_file = $_FILES['cv_file'];
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The CV exceeds the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The CV exceeds the form upload limit.',
            UPLOAD_ERR_PARTIAL => 'The CV upload was incomplete. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please upload a CV file.',
            UPLOAD_ERR_NO_TMP_DIR => 'The server temporary upload directory is unavailable.',
            UPLOAD_ERR_CANT_WRITE => 'The server could not write the uploaded CV.',
            UPLOAD_ERR_EXTENSION => 'A server extension prevented the CV upload.'
        ];
        $respond([
            'success' => false,
            'error' => $upload_errors[$uploaded_file['error']] ?? 'An unknown CV upload error occurred.'
        ], 400);
    }

    $original_file_name = basename($uploaded_file['name']);
    $file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
    if (!in_array($file_extension, ['pdf', 'doc', 'docx'], true)) {
        $respond(['success' => false, 'error' => 'CV must be a PDF, DOC, or DOCX file.'], 400);
    }
    if ($uploaded_file['size'] > 5 * 1024 * 1024) {
        $respond(['success' => false, 'error' => 'CV file size must not exceed 5 MB.'], 400);
    }

    $upload_dir = 'uploads/cv_file/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
        $respond(['success' => false, 'error' => 'The server could not create the CV upload directory.'], 500);
    }

    $stored_file_name = $jobseeker_id . '_' . $job_id . '_' . bin2hex(random_bytes(8)) . '.' . $file_extension;
    $file_path = $upload_dir . $stored_file_name;

    if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
        $respond(['success' => false, 'error' => 'The server could not save the CV. Please try again.'], 500);
    }

    $conn->begin_transaction();

    $latest_application_stmt = $conn->prepare(
        'SELECT numbered_of_fifth_application
         FROM application
         WHERE job_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 1
         FOR UPDATE'
    );
    if (!$latest_application_stmt) {
        error_log('Application sequence query failed: ' . $conn->error);
        $conn->rollback();
        @unlink($file_path);
        $respond(['success' => false, 'error' => 'Unable to determine the application number. Please try again.']);
    }

    $latest_application_stmt->bind_param('i', $job_id);
    if (!$latest_application_stmt->execute()) {
        $latest_application_stmt->close();
        $conn->rollback();
        @unlink($file_path);
        $respond(['success' => false, 'error' => 'Unable to determine the application number. Please try again.']);
    }

    $latest_application_result = $latest_application_stmt->get_result();
    $latest_application = $latest_application_result->fetch_assoc();
    $latest_application_stmt->close();

    $latest_number = (int) ($latest_application['numbered_of_fifth_application'] ?? 0);
    $application_number = $latest_number >= 1 && $latest_number < 5
        ? $latest_number + 1
        : 1;

    // Chỉ bổ sung số điện thoại nếu tài khoản chưa có số.
    if ($user_phone_number === '') {
        $update_phone_stmt = $conn->prepare(
            "UPDATE user SET phone_number = COALESCE(NULLIF(phone_number, ''), ?) WHERE id = ?"
        );
        if (!$update_phone_stmt) {
            $conn->rollback();
            @unlink($file_path);
            $respond(['success' => false, 'error' => 'Unable to update your phone number. Please try again.']);
        }

        $update_phone_stmt->bind_param('si', $phone, $jobseeker_id);
        if (!$update_phone_stmt->execute()) {
            $phone_update_error = $update_phone_stmt->errno;
            $update_phone_stmt->close();
            $conn->rollback();
            @unlink($file_path);

            if ($phone_update_error === 1062) {
                $respond(['success' => false, 'error' => $lang['phone_number_in_use'] ?? 'This phone number is already in use.']);
            }

            $respond(['success' => false, 'error' => 'Unable to update your phone number. Please try again.']);
        }
        $update_phone_stmt->close();
    }

    $stmt = $conn->prepare(
        'INSERT INTO application (job_id, jobseeker_id, fullname, email, phonenumber, cv_file, cover_letter, allow_search, status, numbered_of_fifth_application) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt) {
        error_log('Application insert preparation failed: ' . $conn->error);
        $conn->rollback();
        @unlink($file_path);
        $respond(['success' => false, 'error' => 'Error submitting application. Please try again.']);
    }

    $status = 'pending';
    $stmt->bind_param('iisssssisi', $job_id, $jobseeker_id, $fullname, $email, $phone, $file_path, $cover_letter, $allow_search, $status, $application_number);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->commit();
        if ($application_number === 5) {
            sendFiveApplicationsNotification($conn, $job_id);
        }
        $respond([
            'success' => true,
            'message' => $lang['applied_successfully'] ?? 'Applied successfully!'
        ]);
    }

    // UNIQUE(job_id, jobseeker_id) protects against two simultaneous requests.
    $is_duplicate_insert = $stmt->errno === 1062;
    error_log('Application insert failed: ' . $stmt->error);
    $stmt->close();
    $conn->rollback();
    @unlink($file_path);
    if ($is_duplicate_insert) {
        $respond([
            'success' => false,
            'error' => $lang['you_have_apply_before_warning'] ?? 'You have applied to this job before and therefore cannot apply again.'
        ]);
    }

    $respond(['success' => false, 'error' => 'Error submitting application. Please try again.']);
}
?>
<div class="modal-header">
    <h5 class="w-100 text-center"><?= $lang['application_form'] ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= $lang['close'] ?>"></button>
</div>

<form id="apply-form" method="post" enctype="multipart/form-data">
    <input type="hidden" name="job_id" value="<?= htmlspecialchars($job_id) ?>">

    <div class="modal-body">
        <div class="mb-3">
            <label class="form-label"><?= $lang['full_name'] ?> *</label>
            <input type="text" class="form-control" name="fullname" required>
        </div>

        <div class="mb-3">
            <label class="form-label"><?= $lang['email'] ?> *</label>
            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8') ?>" disabled>
        </div>

        <div class="mb-3">
            <label class="form-label"><?= $lang['phone_number'] ?> *</label>
            <input type="tel" class="form-control" name="phone_number"
                value="<?= htmlspecialchars($user_phone_number, ENT_QUOTES, 'UTF-8') ?>"
                placeholder="0901234567"
                pattern="(?:0(3|5|7|8|9)[0-9]{8}|\+84(3|5|7|8|9)[0-9]{8})"
                inputmode="tel"
                <?= $user_phone_number !== '' ? 'disabled' : 'required' ?>
                aria-describedby="phone-help">
            <div id="phone-help" class="form-text">
                <?= $user_phone_number === '' ? $lang['invalid_phone_number'] : '' ?>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label" for="cover-letter"><?= $lang['cover_letter'] ?></label>
            <textarea class="form-control" id="cover-letter" name="cover_letter" rows="5" maxlength="5000"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label"><?= $lang['upload_cv'] ?></label>
            <input type="file" class="form-control" name="cv_file" accept=".pdf,.doc,.docx" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="allow_search" id="allowSearch">
            <label class="form-check-label" for="allowSearch">
                <?= $lang['allow_employers_find'] ?>
            </label>
        </div>
    </div>

    <div class="modal-footer">
        <button type="submit" class="btn btn-success w-100">
            <?= $lang['apply'] ?>
        </button>
    </div>

    <p class="note mt-3" style="margin-left: 15px; margin-right: 15px; text-align: center;">
        <?= $lang['agreement_text'] ?>
        <a href="#"><?= $lang['terms_of_use'] ?></a>,
        <a href="#"><?= $lang['privacy_policy'] ?></a>,
        <?= $lang['and'] ?>
        <a href="#"><?= $lang['personal_data_policy'] ?></a>.
    </p>
</form>
