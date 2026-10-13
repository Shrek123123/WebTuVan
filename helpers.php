<?php

function getProminentCompanies($conn, int $limit = 0, int $offset = 0): array {
    $sql = "SELECT c.id, c.company_name, c.company_logo, MIN(pc.paid_at) AS paid_at
        FROM prominent_company pc
        JOIN company c ON c.id = pc.company_id
        WHERE pc.status = 'paid' AND c.status = 'approved'
        GROUP BY c.id, c.company_name, c.company_logo
        ORDER BY MIN(pc.paid_at) ASC, MIN(pc.id) ASC";
    if ($limit > 0) {
        $sql .= ' LIMIT ? OFFSET ?';
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($limit > 0) {
        $offset = max(0, $offset);
        $stmt->bind_param('ii', $limit, $offset);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return [];
    }

    $result = $stmt->get_result();
    $companies = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $companies;
}

function normalizeCompanyLogoPath($path): string {
    $value = trim((string) $path);
    if ($value === '') {
        return 'image/logo1.png';
    }

    $value = preg_replace('#^(?:\./)+#', '', $value);
    $value = preg_replace('#^(?:\.\./)+#', '', $value);
    $value = preg_replace('#^/+#', '', $value);

    if (stripos($value, 'uploads/companylogo/') === 0) {
        return $value;
    }
    if (stripos($value, 'companylogo/') === 0) {
        return 'uploads/' . $value;
    }
    if (stripos($value, 'uploads/') === 0) {
        return $value;
    }
    if (stripos($value, 'http://') === 0 || stripos($value, 'https://') === 0) {
        return $value;
    }

    return 'uploads/companylogo/' . basename($value);
}

function truncateCompanyName(string $companyName, int $maxCharacters = 35): string {
    if ($maxCharacters < 1) {
        return '';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($companyName, 'UTF-8') > $maxCharacters
            ? mb_substr($companyName, 0, $maxCharacters, 'UTF-8') . '...'
            : $companyName;
    }

    $characters = preg_split('//u', $companyName, -1, PREG_SPLIT_NO_EMPTY);
    if ($characters === false) {
        return strlen($companyName) > $maxCharacters
            ? substr($companyName, 0, $maxCharacters) . '...'
            : $companyName;
    }
    return count($characters) > $maxCharacters
        ? implode('', array_slice($characters, 0, $maxCharacters)) . '...'
        : $companyName;
}

function getAppBaseUrl(): string {
    $baseUrl = $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL');
    if ($baseUrl === false || trim((string) $baseUrl) === '') {
        require_once __DIR__ . '/vendor/autoload.php';
        if (is_file(__DIR__ . '/.env')) {
            Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
        }
        $baseUrl = $_ENV['APP_URL'] ?? $_SERVER['APP_URL'] ?? getenv('APP_URL');
    }
    if ($baseUrl === false || trim((string) $baseUrl) === '') {
        $baseUrl = 'https://unmade-petite-carefully.ngrok-free.dev/ViecNhe';
    }

    return rtrim(trim((string) $baseUrl), '/');
}

function sendRegistrationSuccessEmail($recipientEmail, $accountType, $loginPath) {
    $recipientEmail = trim((string) $recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $accountLabel = $accountType === 'employer' ? 'nhà tuyển dụng' : 'ứng viên';
    $loginUrl = getAppBaseUrl() . '/' . ltrim($loginPath, '/');

    $subject = 'Việc Nhẹ.VN - Tạo tài khoản thành công';
    $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
    $message = '<p>Việc Nhẹ.VN thông báo bạn đã tạo tài khoản ' . $accountLabel . ' thành công!</p>'
        . '<p>Đăng nhập tại: <a href="' . $safeLoginUrl . '">' . $safeLoginUrl . '</a></p>';

    return sendEmail($recipientEmail, '', $subject, $message);
}

function sendApplicationStatusEmail($recipientEmail, $jobTitle, $companyName, $status) {
    $recipientEmail = trim((string) $recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $statusLabels = [
        'pending' => 'Đang chờ',
        'viewed' => 'Đã xem',
        'interviewed' => 'Đã phỏng vấn',
        'hired' => 'Đã tuyển dụng',
        'rejected' => 'Đã từ chối',
    ];
    $statusLabel = $statusLabels[strtolower((string) $status)] ?? (string) $status;
    $jobTitle = trim((string) $jobTitle);
    $companyName = trim((string) $companyName);

    $subject = 'Việc Nhẹ.VN thông báo trạng thái đơn ứng tuyển của bạn cho vị trí '
        . $jobTitle . ' - ' . $companyName;
    $detailsUrl = getAppBaseUrl() . '/jobseekerprofile/don_ung_tuyen.php';
    $safeJobTitle = htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8');
    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeStatus = htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8');
    $safeDetailsUrl = htmlspecialchars($detailsUrl, ENT_QUOTES, 'UTF-8');
    $message = '<p>Nhà tuyển dụng công ty ' . $safeCompanyName . '</p>'
        . '<p>đã cập nhật trạng thái đơn ứng tuyển của bạn là: <strong>' . $safeStatus . '</strong>'
        . ' cho vị trí <strong>' . $safeJobTitle . '</strong></p>'
        . '<p>Vào trang sau để xem chi tiết: <a href="' . $safeDetailsUrl . '">' . $safeDetailsUrl . '</a></p>'
        . '<p>Trân trọng,<br>ViecNhe.vn</p>';

    return sendEmail($recipientEmail, '', $subject, $message);
}

function getChangedJobFieldLabels(array $before, array $after, array $fieldLabels): array {
    $changedLabels = [];
    foreach ($fieldLabels as $field => $label) {
        if ((string) ($before[$field] ?? '') !== (string) ($after[$field] ?? '')) {
            $changedLabels[] = $label;
        }
    }
    return $changedLabels;
}

function sendJobUpdateNotificationEmails(mysqli $conn, int $jobId, string $jobTitle, string $companyName, array $changedFieldLabels): int {
    if ($changedFieldLabels === []) {
        return 0;
    }

    $stmt = $conn->prepare('SELECT DISTINCT u.email, u.username FROM job_interest_count jic JOIN `user` u ON u.id = jic.jobseeker_id WHERE jic.job_id = ? AND u.user_type = \'jobseeker\'');
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('i', $jobId);
    if (!$stmt->execute()) {
        $stmt->close();
        return 0;
    }
    $recipients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($recipients === []) {
        return 0;
    }

    require_once __DIR__ . '/config/mail.php';
    $subject = 'ViecNhe.vn thông báo bài đăng bạn quan tâm vừa được cập nhật';
    $safeJobTitle = htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8');
    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $jobUrl = 'https://viecnhe.io.vn/jobdetail.php?id=' . $jobId;
    $safeJobUrl = htmlspecialchars($jobUrl, ENT_QUOTES, 'UTF-8');
    $changedList = '<ul>';
    foreach (array_unique($changedFieldLabels) as $label) {
        $changedList .= '<li>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    $changedList .= '</ul>';
    $message = '<p>Bài đăng vị trí <strong>' . $safeJobTitle . '</strong> của công ty <strong>' . $safeCompanyName . '</strong> vừa được cập nhật ở các mục sau:</p>'
        . $changedList
        . '<p>Để biết thêm thông tin, hãy truy cập trang bài đăng: <a href="' . $safeJobUrl . '">' . $safeJobUrl . '</a></p>'
        . '<p>Trân trọng,<br>Đội ngũ ViecNhe</p>';

    $sentCount = 0;
    foreach ($recipients as $recipient) {
        $email = trim((string) ($recipient['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && sendEmail($email, (string) ($recipient['username'] ?? ''), $subject, $message)) {
            $sentCount++;
        }
    }
    return $sentCount;
}

function sendEmailChangeNotification($newEmail, $userType) {
    $newEmail = trim((string) $newEmail);
    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $accountLabel = strtolower((string) $userType) === 'employer' ? 'nhà tuyển dụng' : 'tìm việc';
    $safeEmail = htmlspecialchars($newEmail, ENT_QUOTES, 'UTF-8');
    $subject = 'Việc Nhẹ.VN thông báo bạn đã đổi email tài khoản ' . $accountLabel;
    $message = '<p>Việc Nhẹ.VN thông báo bạn đã đổi email tài khoản ' . $accountLabel
        . ' sang email mới có địa chỉ: <strong>' . $safeEmail . '</strong></p>'
        . '<p>Trân trọng,<br>ViecNhe.vn</p>';

    return sendEmail($newEmail, '', $subject, $message);
}

function sendProminentCompanyPaymentConfirmation($conn, int $registrationId): bool {
    $resetStmt = $conn->prepare("UPDATE prominent_company SET confirmation_email_status = 'pending' WHERE id = ? AND status = 'paid' AND confirmation_email_status = 'sending' AND updated_at < NOW() - INTERVAL 10 MINUTE");
    if ($resetStmt) {
        $resetStmt->bind_param('i', $registrationId);
        $resetStmt->execute();
        $resetStmt->close();
    }

    $claimStmt = $conn->prepare("UPDATE prominent_company SET confirmation_email_status = 'sending' WHERE id = ? AND status = 'paid' AND confirmation_email_status = 'pending'");
    if (!$claimStmt) {
        return false;
    }
    $claimStmt->bind_param('i', $registrationId);
    $claimed = $claimStmt->execute() && $claimStmt->affected_rows === 1;
    $claimStmt->close();
    if (!$claimed) {
        return false;
    }

    $stmt = $conn->prepare('SELECT u.email, c.company_name, c.id AS company_id, pc.transaction_code, pc.amount, pc.paid_at FROM prominent_company pc JOIN company c ON c.id = pc.company_id JOIN user u ON u.id = pc.employer_id WHERE pc.id = ? AND pc.status = \'paid\' LIMIT 1');
    if (!$stmt) {
        $conn->query("UPDATE prominent_company SET confirmation_email_status = 'pending' WHERE id = " . $registrationId . " AND confirmation_email_status = 'sending'");
        return false;
    }
    $stmt->bind_param('i', $registrationId);
    $stmt->execute();
    $registration = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $recipientEmail = trim((string) ($registration['email'] ?? ''));
    if (!$registration || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        $resetStmt = $conn->prepare("UPDATE prominent_company SET confirmation_email_status = 'pending' WHERE id = ? AND confirmation_email_status = 'sending'");
        if ($resetStmt) {
            $resetStmt->bind_param('i', $registrationId);
            $resetStmt->execute();
            $resetStmt->close();
        }
        return false;
    }

    $companyName = (string) $registration['company_name'];
    $companyProfileUrl = getAppBaseUrl() . '/companyprofile.php?company_id=' . (int) $registration['company_id'];
    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeProfileUrl = htmlspecialchars($companyProfileUrl, ENT_QUOTES, 'UTF-8');
    $paidAt = htmlspecialchars((string) $registration['paid_at'], ENT_QUOTES, 'UTF-8');
    $transactionCode = htmlspecialchars((string) $registration['transaction_code'], ENT_QUOTES, 'UTF-8');
    $amount = number_format((int) $registration['amount'], 0, ',', ',');
    $subject = 'ViecNhe | Đăng ký công ty nổi bật thành công';
    $message = '<div style="max-width:600px;margin:0 auto;padding:28px 24px;color:#20332e;font-family:Arial,sans-serif;line-height:1.65">'
        . '<h1 style="margin:0 0 16px;font-size:24px">Đăng ký công ty nổi bật thành công</h1>'
        . '<p>ViecNhe đã xác nhận thanh toán cho công ty <strong>' . $safeCompanyName . '</strong>.</p>'
        . '<p><strong>Số tiền:</strong> ' . $amount . ' VND<br><strong>Mã giao dịch:</strong> ' . $transactionCode . '<br><strong>Thời gian thanh toán:</strong> ' . $paidAt . '</p>'
        . '<p>Công ty đã được đưa vào danh sách công ty nổi bật và các bài đăng sẽ được ưu tiên theo thời điểm thanh toán.</p>'
        . '<p><a href="' . $safeProfileUrl . '">Xem hồ sơ công ty</a></p>'
        . '<p>Trân trọng,<br><strong>Đội ngũ ViecNhe</strong></p></div>';

    $sent = false;
    try {
        require_once __DIR__ . '/config/mail.php';
        $sent = sendEmail($recipientEmail, $companyName, $subject, $message);
    } catch (Throwable $exception) {
        error_log('Prominent company confirmation email error for registration ' . $registrationId . ': ' . $exception->getMessage());
    }

    if ($sent) {
        $updateStmt = $conn->prepare("UPDATE prominent_company SET confirmation_email_status = 'sent', confirmation_email_sent_at = NOW() WHERE id = ? AND confirmation_email_status = 'sending'");
    } else {
        $updateStmt = $conn->prepare("UPDATE prominent_company SET confirmation_email_status = 'pending' WHERE id = ? AND confirmation_email_status = 'sending'");
    }
    if ($updateStmt) {
        $updateStmt->bind_param('i', $registrationId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    return $sent;
}

function sendPasswordResetOtp($recipientEmail, $otp) {
    $recipientEmail = trim((string) $recipientEmail);
    $otp = trim((string) $otp);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) || !preg_match('/^\d{5}$/', $otp)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $subject = 'Việc Nhẹ.VN - Mã xác nhận đặt lại mật khẩu';
    $message = '<p>Việc Nhẹ.VN gửi bạn mã xác nhận để đặt lại mật khẩu:</p>'
        . '<p style="font-size: 24px; font-weight: bold; letter-spacing: 6px;">' . $otp . '</p>'
        . '<p>Trân trọng,<br>ViecNhe.vn</p>';

    return sendEmail($recipientEmail, '', $subject, $message);
}

function getJobPayOSClient(): PayOS\PayOS {
    require_once __DIR__ . '/vendor/autoload.php';
    $config = require __DIR__ . '/config/payos.php';
    foreach (['client_id', 'api_key', 'checksum_key'] as $key) {
        if (empty($config[$key])) {
            throw new RuntimeException('Thiếu cấu hình PayOS: ' . $key);
        }
    }

    return new PayOS\PayOS(
        clientId: $config['client_id'],
        apiKey: $config['api_key'],
        checksumKey: $config['checksum_key']
    );
}

function createJobPaymentCheckout($conn, $employerId, $companyId, $duration, $amount, array $jobData) {
    foreach (['job_title', 'job_description', 'job_location', 'salary', 'contact_email', 'contact_phone', 'job_type', 'job_position', 'job_experience', 'job_requirement'] as $requiredField) {
        if (trim((string) ($jobData[$requiredField] ?? '')) === '') {
            throw new InvalidArgumentException('Vui lòng điền đầy đủ thông tin bắt buộc của bài đăng.');
        }
    }
    if (!filter_var($jobData['contact_email'], FILTER_VALIDATE_EMAIL) || (int) ($jobData['no_employee_needed'] ?? 0) <= 0) {
        throw new InvalidArgumentException('Thông tin email hoặc số lượng tuyển dụng không hợp lệ.');
    }

    $duration = (int) $duration;
    if (!in_array($duration, [30, 60, 180, 365], true)) {
        throw new InvalidArgumentException('Thời hạn đăng tin không hợp lệ.');
    }
    $jobData['company_id'] = (int) $companyId;
    $jobData['post_duration'] = $duration;
    $payload = json_encode($jobData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $employerId = (int) $employerId;
    $amount = (int) $amount;
    $conn->query("CREATE TABLE IF NOT EXISTS job_payments (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employer_id INT UNSIGNED NOT NULL,
        job_id INT UNSIGNED NULL,
        order_code BIGINT UNSIGNED NOT NULL UNIQUE,
        payment_link_id VARCHAR(100) NULL,
        checkout_url TEXT NULL,
        amount BIGINT UNSIGNED NOT NULL,
        status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        confirmation_email_status ENUM('pending', 'sending', 'sent') NOT NULL DEFAULT 'pending',
        job_payload JSON NOT NULL,
        provider_reference VARCHAR(255) NULL,
        raw_response JSON NULL,
        paid_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_job_payments_employer (employer_id),
        INDEX idx_job_payments_job (job_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $emailStatusColumn = $conn->query("SHOW COLUMNS FROM job_payments LIKE 'confirmation_email_status'");
    if ($emailStatusColumn && $emailStatusColumn->num_rows === 0) {
        $conn->query("ALTER TABLE job_payments ADD COLUMN confirmation_email_status ENUM('pending', 'sending', 'sent') NOT NULL DEFAULT 'pending'");
    }
    $orderCode = random_int(100000, 999999999);
    $stmt = $conn->prepare('INSERT INTO job_payments (employer_id, amount, order_code, job_payload, status) VALUES (?, ?, ?, ?, \'pending\')');
    if (!$stmt) {
        throw new RuntimeException('Không thể tạo giao dịch thanh toán: ' . $conn->error);
    }
    $stmt->bind_param('iiis', $employerId, $amount, $orderCode, $payload);
    if (!$stmt->execute()) {
        throw new RuntimeException('Không thể lưu giao dịch thanh toán: ' . $stmt->error);
    }
    $paymentId = (int) $conn->insert_id;
    $stmt->close();

    try {
        $config = require __DIR__ . '/config/payos.php';
        $baseUrl = rtrim((string) ($config['public_url'] ?? ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Thiếu cấu hình PAYOS_PUBLIC_URL.');
        }
        $returnUrl = $baseUrl . '/public/success.html';
        $cancelUrl = $baseUrl . '/public/cancel.html';
        $paymentRequest = [
            'orderCode' => $orderCode,
            'amount' => $amount,
            'description' => 'Dang tin ' . $orderCode,
            'returnUrl' => $returnUrl,
            'cancelUrl' => $cancelUrl,
        ];
        for ($attempt = 0; ; $attempt++) {
            try {
                $response = getJobPayOSClient()->paymentRequests->create($paymentRequest);
                break;
            } catch (Throwable $exception) {
                $isDnsResolutionFailure = preg_match('/cURL error 6|Could not resolve|Name or service not known/i', $exception->getMessage()) === 1;
                if (!$isDnsResolutionFailure || $attempt >= 2) {
                    throw $exception;
                }
                usleep(250000 * ($attempt + 1));
            }
        }
        $stmt = $conn->prepare('UPDATE job_payments SET payment_link_id = ?, checkout_url = ? WHERE id = ?');
        $stmt->bind_param('ssi', $response->paymentLinkId, $response->checkoutUrl, $paymentId);
        if (!$stmt->execute()) {
            throw new RuntimeException('Không thể lưu liên kết thanh toán: ' . $stmt->error);
        }
        $stmt->close();

        return [
            'order_code' => $orderCode,
            'checkout_url' => $response->checkoutUrl,
        ];
    } catch (Throwable $exception) {
        $stmt = $conn->prepare("UPDATE job_payments SET status = 'failed' WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $stmt->close();
        throw $exception;
    }
}

function createProminentCompanyPaymentCheckout($conn, int $employerId, int $companyId): array {
    $amount = 69000;
    $companyStmt = $conn->prepare('SELECT id FROM company WHERE id = ? AND employer_id = ? LIMIT 1');
    if (!$companyStmt) {
        throw new RuntimeException('Không thể xác minh công ty: ' . $conn->error);
    }
    $companyStmt->bind_param('ii', $companyId, $employerId);
    $companyStmt->execute();
    $company = $companyStmt->get_result()->fetch_assoc();
    $companyStmt->close();
    if (!$company) {
        throw new InvalidArgumentException('Công ty đã chọn không hợp lệ.');
    }

    $paidStmt = $conn->prepare("SELECT id FROM prominent_company WHERE company_id = ? AND status = 'paid' LIMIT 1");
    if (!$paidStmt) {
        throw new RuntimeException('Không thể kiểm tra trạng thái công ty: ' . $conn->error);
    }
    $paidStmt->bind_param('i', $companyId);
    $paidStmt->execute();
    $alreadyPaid = (bool) $paidStmt->get_result()->fetch_assoc();
    $paidStmt->close();
    if ($alreadyPaid) {
        throw new InvalidArgumentException('Prominent company is already registered.', 409);
    }

    $transactionCode = (string) random_int(100000, 999999999);
    $stmt = $conn->prepare("INSERT INTO prominent_company (company_id, employer_id, transaction_code, amount, status) VALUES (?, ?, ?, ?, 'pending')");
    if (!$stmt) {
        throw new RuntimeException('Không thể lưu giao dịch: ' . $conn->error);
    }
    $stmt->bind_param('iisi', $companyId, $employerId, $transactionCode, $amount);
    if (!$stmt->execute()) {
        throw new RuntimeException('Không thể lưu giao dịch: ' . $stmt->error);
    }
    $paymentId = (int) $conn->insert_id;
    $stmt->close();

    try {
        $config = require __DIR__ . '/config/payos.php';
        $baseUrl = rtrim((string) ($config['public_url'] ?? ''), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('Thiếu cấu hình APP_URL.');
        }
        $response = getJobPayOSClient()->paymentRequests->create([
            'orderCode' => (int) $transactionCode,
            'amount' => $amount,
            'description' => 'Cong ty noi bat',
            'returnUrl' => $baseUrl . '/dang_ky_cong_ty_nb_thanh_cong.php',
            'cancelUrl' => $baseUrl . '/huy_dang_ky_cong_ty_nb.php',
        ]);
        $checkoutUrl = trim((string) ($response->checkoutUrl ?? ''));
        if (!filter_var($checkoutUrl, FILTER_VALIDATE_URL)
            || strtolower((string) parse_url($checkoutUrl, PHP_URL_SCHEME)) !== 'https') {
            throw new RuntimeException('PayOS không trả về liên kết thanh toán hợp lệ.');
        }
        $updateStmt = $conn->prepare('UPDATE prominent_company SET payment_link_id = ?, checkout_url = ? WHERE id = ? AND status = \'pending\'');
        if (!$updateStmt) {
            throw new RuntimeException('Không thể lưu liên kết PayOS: ' . $conn->error);
        }
        $updateStmt->bind_param('ssi', $response->paymentLinkId, $checkoutUrl, $paymentId);
        if (!$updateStmt->execute()) {
            throw new RuntimeException('Không thể lưu liên kết PayOS: ' . $updateStmt->error);
        }
        $updateStmt->close();

        return ['checkout_url' => $checkoutUrl];
    } catch (Throwable $exception) {
        $failedStmt = $conn->prepare("UPDATE prominent_company SET status = 'failed' WHERE id = ? AND status = 'pending'");
        if ($failedStmt) {
            $failedStmt->bind_param('i', $paymentId);
            $failedStmt->execute();
            $failedStmt->close();
        }
        throw $exception;
    }
}

function reconcileProminentCompanyPayment($conn, int $transactionCode): string {
    if ($transactionCode <= 0) {
        return 'pending';
    }
    $code = (string) $transactionCode;
    $stmt = $conn->prepare('SELECT id, amount, status FROM prominent_company WHERE transaction_code = ? LIMIT 1');
    if (!$stmt) {
        return 'pending';
    }
    $stmt->bind_param('s', $code);
    $stmt->execute();
    $registration = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$registration) {
        return 'pending';
    }
    if ($registration['status'] !== 'pending') {
        if ($registration['status'] === 'paid') {
            sendProminentCompanyPaymentConfirmation($conn, (int) $registration['id']);
        }
        return (string) $registration['status'];
    }

    try {
        $paymentInfo = getJobPayOSClient()->paymentRequests->get($transactionCode);
    } catch (Throwable $exception) {
        error_log('PayOS prominent-company lookup failed: ' . $exception->getMessage());
        return 'pending';
    }
    if ((int) $paymentInfo->orderCode !== $transactionCode || (int) $paymentInfo->amount !== (int) $registration['amount']) {
        error_log('PayOS prominent-company order mismatch for ' . $transactionCode);
        return 'pending';
    }

    $providerStatus = $paymentInfo->status->value ?? '';
    if ($providerStatus === 'PAID'
        && (int) $paymentInfo->amountPaid === (int) $registration['amount']
        && (int) $paymentInfo->amountRemaining === 0) {
        $rawResponse = json_encode($paymentInfo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $paymentLinkId = (string) $paymentInfo->id;
        $providerReference = (string) ($paymentInfo->transactions[0]->reference ?? '');
        $updateStmt = $conn->prepare("UPDATE prominent_company SET status = 'paid', payment_link_id = ?, provider_reference = ?, raw_response = ?, paid_at = NOW() WHERE id = ? AND status = 'pending'");
        if ($updateStmt) {
            $updateStmt->bind_param('sssi', $paymentLinkId, $providerReference, $rawResponse, $registration['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    } elseif (in_array($providerStatus, ['CANCELLED', 'EXPIRED', 'FAILED'], true)) {
        $updateStmt = $conn->prepare("UPDATE prominent_company SET status = 'failed', raw_response = ? WHERE id = ? AND status = 'pending'");
        if ($updateStmt) {
            $rawResponse = json_encode($paymentInfo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $updateStmt->bind_param('si', $rawResponse, $registration['id']);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    $statusStmt = $conn->prepare('SELECT status FROM prominent_company WHERE id = ? LIMIT 1');
    if (!$statusStmt) {
        return 'pending';
    }
    $statusStmt->bind_param('i', $registration['id']);
    $statusStmt->execute();
    $status = (string) ($statusStmt->get_result()->fetch_assoc()['status'] ?? 'pending');
    $statusStmt->close();
    if ($status === 'paid') {
        sendProminentCompanyPaymentConfirmation($conn, (int) $registration['id']);
    }
    return $status;
}

function sendEmployerJobPaymentConfirmation($conn, int $paymentId): bool {
    $tierDetails = [
        30 => ['label' => '30 ngày', 'amount' => 50000, 'subject' => 'Thanh toán gói đăng tin 30 ngày thành công'],
        60 => ['label' => '60 ngày', 'amount' => 80000, 'subject' => 'Thanh toán gói đăng tin 60 ngày thành công'],
        180 => ['label' => '6 tháng', 'amount' => 250000, 'subject' => 'Thanh toán gói đăng tin 6 tháng thành công'],
        365 => ['label' => '1 năm', 'amount' => 430000, 'subject' => 'Thanh toán gói đăng tin 1 năm thành công'],
    ];

    $conn->query("UPDATE job_payments SET confirmation_email_status = 'pending' WHERE id = " . $paymentId . " AND status = 'paid' AND confirmation_email_status = 'sending' AND updated_at < NOW() - INTERVAL 10 MINUTE");
    $claimStmt = $conn->prepare("UPDATE job_payments SET confirmation_email_status = 'sending' WHERE id = ? AND status = 'paid' AND job_id IS NOT NULL AND confirmation_email_status = 'pending'");
    if (!$claimStmt) {
        return false;
    }
    $claimStmt->bind_param('i', $paymentId);
    $claimed = $claimStmt->execute() && $claimStmt->affected_rows === 1;
    $claimStmt->close();
    if (!$claimed) {
        return false;
    }

    $stmt = $conn->prepare('SELECT u.email, j.job_title, j.post_duration, p.amount FROM job_payments p JOIN user u ON u.id = p.employer_id JOIN job j ON j.id = p.job_id WHERE p.id = ? AND p.status = \'paid\' LIMIT 1');
    if (!$stmt) {
        $conn->query("UPDATE job_payments SET confirmation_email_status = 'pending' WHERE id = " . $paymentId . " AND confirmation_email_status = 'sending'");
        return false;
    }
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $duration = (int) ($payment['post_duration'] ?? 0);
    $tier = $tierDetails[$duration] ?? null;
    $recipientEmail = trim((string) ($payment['email'] ?? ''));
    if (!$payment || !$tier || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)
        || (int) $payment['amount'] !== $tier['amount']) {
        $conn->query("UPDATE job_payments SET confirmation_email_status = 'pending' WHERE id = " . $paymentId . " AND confirmation_email_status = 'sending'");
        error_log('Job payment confirmation email skipped: invalid recipient, tier, or amount for payment ' . $paymentId);
        return false;
    }

    require_once __DIR__ . '/config/mail.php';
    $safeJobTitle = htmlspecialchars((string) $payment['job_title'], ENT_QUOTES, 'UTF-8');
    $safeTierLabel = htmlspecialchars($tier['label'], ENT_QUOTES, 'UTF-8');
    $amountLabel = number_format($tier['amount'], 0, ',', ',');
    $subject = 'ViecNhe | ' . $tier['subject'];
    $message = '<div style="max-width:600px;margin:0 auto;padding:28px 24px;color:#20332e;font-family:Arial,sans-serif;line-height:1.65">'
        . '<p style="margin:0 0 8px;color:#19765b;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">ViecNhe | Xác nhận thanh toán</p>'
        . '<h1 style="margin:0 0 18px;font-size:24px;line-height:1.3">Thanh toán thành công</h1>'
        . '<p>Xin chào Nhà tuyển dụng,</p>'
        . '<p>ViecNhe đã xác nhận thanh toán của bạn. Thông tin giao dịch:</p>'
        . '<div style="padding:16px 18px;border:1px solid #dce8e2;border-radius:8px;background:#f6faf8">'
        . '<p style="margin:0 0 8px"><strong>Số tiền:</strong> ' . $amountLabel . ' VND</p>'
        . '<p style="margin:0 0 8px"><strong>Công việc:</strong> ' . $safeJobTitle . '</p>'
        . '<p style="margin:0"><strong>Thời hạn đăng:</strong> ' . $safeTierLabel . '</p>'
        . '</div>'
        . '<p>Bài đăng đã được gửi tới đội ngũ ViecNhe để xét duyệt. Chúng tôi sẽ thông báo kết quả khi quá trình xét duyệt hoàn tất.</p>'
        . '<p>Trân trọng,<br><strong>Đội ngũ ViecNhe</strong></p></div>';

    $sent = false;
    try {
        $sent = sendEmail($recipientEmail, '', $subject, $message);
    } catch (Throwable $exception) {
        error_log('Job payment confirmation email error for payment ' . $paymentId . ': ' . $exception->getMessage());
    }
    $emailStatus = $sent ? 'sent' : 'pending';
    $updateStmt = $conn->prepare('UPDATE job_payments SET confirmation_email_status = ? WHERE id = ? AND confirmation_email_status = \'sending\'');
    if ($updateStmt) {
        $updateStmt->bind_param('si', $emailStatus, $paymentId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    return $sent;
}

function reconcileJobPaymentOrder($conn, $orderCode) {
    $orderCode = (int) $orderCode;
    if ($orderCode <= 0) {
        return false;
    }

    $stmt = $conn->prepare('SELECT id, amount, status, job_id FROM job_payments WHERE order_code = ? LIMIT 1');
    $stmt->bind_param('i', $orderCode);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        return false;
    }
    if (!empty($order['job_id'])) {
        sendEmployerJobPaymentConfirmation($conn, (int) $order['id']);
        return true;
    }

    try {
        $paymentInfo = getJobPayOSClient()->paymentRequests->get($orderCode);
    } catch (Throwable $exception) {
        error_log('PayOS payment lookup failed: ' . $exception->getMessage());
        return false;
    }
    $providerStatus = $paymentInfo->status->value ?? '';
    if ((int) $paymentInfo->orderCode !== $orderCode || (int) $paymentInfo->amount !== (int) $order['amount']) {
        error_log('PayOS order mismatch for orderCode ' . $orderCode);
        return false;
    }
    if ($providerStatus !== 'PAID' || (int) $paymentInfo->amountPaid !== (int) $order['amount'] || (int) $paymentInfo->amountRemaining !== 0) {
        if (in_array($providerStatus, ['CANCELLED', 'EXPIRED', 'FAILED'], true)) {
            $stmt = $conn->prepare("UPDATE job_payments SET status = 'failed' WHERE order_code = ? AND status = 'pending' AND job_id IS NULL");
            $stmt->bind_param('i', $orderCode);
            $stmt->execute();
            $stmt->close();
        }
        return false;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('SELECT id, employer_id, amount, status, job_id, job_payload FROM job_payments WHERE order_code = ? LIMIT 1 FOR UPDATE');
        $stmt->bind_param('i', $orderCode);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$order) {
            throw new RuntimeException('Không tìm thấy giao dịch thanh toán.');
        }
        if (!empty($order['job_id'])) {
            $conn->commit();
            return true;
        }
        if ($order['status'] !== 'pending' || (int) $order['amount'] !== (int) $paymentInfo->amount) {
            throw new RuntimeException('Trạng thái hoặc số tiền giao dịch không hợp lệ.');
        }

        $job = json_decode((string) $order['job_payload'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($job)) {
            throw new RuntimeException('Dữ liệu bài đăng không hợp lệ.');
        }
        $employerId = (int) $order['employer_id'];
        $companyId = (int) $job['company_id'];
        $companyStmt = $conn->prepare('SELECT id FROM company WHERE id = ? AND employer_id = ? LIMIT 1');
        $companyStmt->bind_param('ii', $companyId, $employerId);
        $companyStmt->execute();
        if (!$companyStmt->get_result()->fetch_assoc()) {
            throw new RuntimeException('Công ty không thuộc nhà tuyển dụng này.');
        }
        $companyStmt->close();

        $numberStmt = $conn->prepare('SELECT COALESCE(MAX(job_post_number_by_employer), 0) + 1 AS next_number FROM job WHERE posted_by_employer_id = ? FOR UPDATE');
        $numberStmt->bind_param('i', $employerId);
        $numberStmt->execute();
        $nextNumber = (int) ($numberStmt->get_result()->fetch_assoc()['next_number'] ?? 1);
        $numberStmt->close();

        $jobStmt = $conn->prepare('INSERT INTO job (posted_by_employer_id, company_id, job_post_number_by_employer, job_title, job_description, job_location, salary, post_duration, contact_email, contact_phone, required_certification, job_experience, job_requirement, job_benefit, no_employee_needed, job_position, job_detailed_location, job_location_district, job_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $jobStatus = 'pending_review';
        $jobStmt->bind_param('iiissssissssssisssss', $employerId, $companyId, $nextNumber, $job['job_title'], $job['job_description'], $job['job_location'], $job['salary'], $job['post_duration'], $job['contact_email'], $job['contact_phone'], $job['required_certification'], $job['job_experience'], $job['job_requirement'], $job['job_benefit'], $job['no_employee_needed'], $job['job_position'], $job['job_detailed_location'], $job['job_location_district'], $job['job_type'], $jobStatus);
        if (!$jobStmt->execute()) {
            throw new RuntimeException('Không thể tạo bài đăng: ' . $jobStmt->error);
        }
        $jobId = (int) $conn->insert_id;
        $jobStmt->close();

        $rawResponse = json_encode($paymentInfo, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $reference = (string) ($paymentInfo->transactions[0]->reference ?? '');
        $paymentLinkId = (string) $paymentInfo->id;
        $updateStmt = $conn->prepare("UPDATE job_payments SET status = 'paid', job_id = ?, payment_link_id = ?, provider_reference = ?, raw_response = ?, paid_at = NOW() WHERE id = ? AND status = 'pending' AND job_id IS NULL");
        $updateStmt->bind_param('isssi', $jobId, $paymentLinkId, $reference, $rawResponse, $order['id']);
        if (!$updateStmt->execute() || $updateStmt->affected_rows !== 1) {
            throw new RuntimeException('Không thể cập nhật trạng thái thanh toán.');
        }
        $updateStmt->close();
        $conn->commit();
        sendEmployerJobPaymentConfirmation($conn, (int) $order['id']);
        return true;
    } catch (Throwable $exception) {
        $conn->rollback();
        error_log('PayOS reconciliation error: ' . $exception->getMessage());
        return false;
    }
}

function sendEmployerPasswordResetOtp($recipientEmail, $otp) {
    return sendPasswordResetOtp($recipientEmail, $otp);
}

function sendCompanyReviewEmail($recipientEmail, $companyName, $status, $adminNote = '') {
    $recipientEmail = trim((string) $recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $isApproved = strtolower((string) $status) === 'approved';
    $statusLabel = $isApproved ? 'đã được phê duyệt' : 'đã bị từ chối';
    $safeCompanyName = htmlspecialchars(trim((string) $companyName), ENT_QUOTES, 'UTF-8');
    $subject = 'Việc Nhẹ.VN thông báo kết quả duyệt thông tin công ty ' . trim((string) $companyName);
    $message = '<p>Việc Nhẹ.VN thông báo thông tin công ty <strong>' . $safeCompanyName
        . '</strong> của bạn ' . $statusLabel . '.</p>';

    if (!$isApproved && trim((string) $adminNote) !== '') {
        $message .= '<p>Lý do từ chối: <strong>'
            . htmlspecialchars(trim((string) $adminNote), ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }

    $message .= '<p>Trân trọng,<br>ViecNhe.vn</p>';
    return sendEmail($recipientEmail, '', $subject, $message);
}

function sendJobReviewEmail($recipientEmail, $jobTitle, $companyName, $status, $adminNote = '') {
    $recipientEmail = trim((string) $recipientEmail);
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $isApproved = strtolower((string) $status) === 'approved';
    $statusLabel = $isApproved ? 'đã được phê duyệt' : 'đã bị từ chối';
    $jobTitle = trim((string) $jobTitle);
    $companyName = trim((string) $companyName);
    $safeJobTitle = htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8');
    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $subject = 'Việc Nhẹ.VN thông báo kết quả duyệt bài đăng tuyển dụng ' . $jobTitle;
    $message = '<p>Việc Nhẹ.VN thông báo bài đăng tuyển dụng <strong>' . $safeJobTitle
        . '</strong> của công ty <strong>' . $safeCompanyName . '</strong> ' . $statusLabel . '.</p>';

    if (!$isApproved && trim((string) $adminNote) !== '') {
        $message .= '<p>Lý do từ chối: <strong>'
            . htmlspecialchars(trim((string) $adminNote), ENT_QUOTES, 'UTF-8') . '</strong></p>';
    }

    $message .= '<p>Trân trọng,<br>ViecNhe.vn</p>';
    return sendEmail($recipientEmail, '', $subject, $message);
}

function sendFiveApplicationsNotification($conn, $jobId) {
    $jobId = (int) $jobId;
    if ($jobId <= 0 || !($conn instanceof mysqli)) {
        return false;
    }

    $jobStmt = $conn->prepare(
        'SELECT u.email, j.job_title
         FROM job j
         JOIN user u ON u.id = j.posted_by_employer_id
         WHERE j.id = ?
         LIMIT 1'
    );
    if (!$jobStmt) {
        return false;
    }

    $jobStmt->bind_param('i', $jobId);
    $jobStmt->execute();
    $jobResult = $jobStmt->get_result();
    $jobData = $jobResult->fetch_assoc();
    $jobStmt->close();

    $recipientEmail = trim((string) ($jobData['email'] ?? ''));
    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $jobTitle = trim((string) ($jobData['job_title'] ?? ''));

    $applicationsStmt = $conn->prepare(
        'SELECT fullname
         FROM application
         WHERE job_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    );
    if (!$applicationsStmt) {
        return false;
    }

    $applicationsStmt->bind_param('i', $jobId);
    $applicationsStmt->execute();
    $applicationsResult = $applicationsStmt->get_result();
    $applications = [];
    while ($application = $applicationsResult->fetch_assoc()) {
        $applications[] = $application;
    }
    $applicationsStmt->close();

    if (count($applications) < 5) {
        return false;
    }

    require_once __DIR__ . '/config/mail.php';

    $applicationUrl = getAppBaseUrl() . '/employerprofile/view_applications.php?job_id=' . $jobId;
    $safeApplicationUrl = htmlspecialchars($applicationUrl, ENT_QUOTES, 'UTF-8');
    $applicationLinks = '';
    foreach ($applications as $index => $application) {
        $safeFullname = htmlspecialchars((string) ($application['fullname'] ?? ''), ENT_QUOTES, 'UTF-8');
        $applicationLinks .= '<li>Ứng viên <strong>' . $safeFullname . '</strong>, '
            . '<a href="' . $safeApplicationUrl . '">truy cập trang xem hồ sơ ứng tuyển</a></li>';
    }

    $subject = 'Việc Nhẹ.vn thông báo bạn có thêm 5 đơn ứng tuyển mới từ người tìm việc cho vị trí '
        . $jobTitle;
    $safeJobTitle = htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8');
    $message = '<p>Việc Nhẹ.vn thông báo bạn có thêm 5 đơn ứng tuyển mới từ người tìm việc cho vị trí '
        . '<strong>' . $safeJobTitle . '</strong>.</p>'
        . '<p>Hãy rà soát hồ sơ ứng viên ngay tại đây:</p>'
        . '<ol>' . $applicationLinks . '</ol>'
        . '<p>Trân trọng,<br>ViecNhe.vn</p>';

    return sendEmail($recipientEmail, '', $subject, $message);
}

function trans($key, $replace = []) {
    global $lang;
    $text = $lang[$key] ?? $key;
    foreach ($replace as $k => $v) {
        $text = str_replace($k, $v, $text);
    }
    return $text;
}

function normalizeIndustryKey($value) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $key = strtolower($raw);
    $key = str_replace(['/', '&', '-', '.', ','], ' ', $key);
    $key = preg_replace('/[^a-z0-9_\s]+/u', '', $key);
    $key = preg_replace('/\s+/', '_', trim($key));
    $key = preg_replace('/_+/', '_', $key);
    return trim($key, '_');
}

function translateIndustryDisplay($value, $lang = []) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    if (isset($lang[$raw]) && is_string($lang[$raw])) {
        return $lang[$raw];
    }

    $key = normalizeIndustryKey($raw);
    if ($key === '') {
        return trim(preg_replace('/_+/', ' ', $raw) ?? $raw);
    }

    if (isset($lang[$key]) && is_string($lang[$key])) {
        return $lang[$key];
    }

    $mappedKey = [
        'kinh_doanh_ban_hang' => 'kinh_doanh_ban_hang',
        'marketing_truyen_thong' => 'marketing_truyen_thong',
        'tai_chinh_ke_toan_ngan_hang' => 'tai_chinh_ke_toan_ngan_hang',
        'cong_nghe_thong_tin' => 'cong_nghe_thong_tin',
        'nhan_su' => 'nhan_su',
        'hanh_chinh_van_phong' => 'hanh_chinh_van_phong',
        'ky_thuat' => 'ky_thuat',
        'san_xuat_van_hanh_day_chuyen' => 'san_xuat_van_hanh_day_chuyen',
        'van_tai_logistics' => 'van_tai_logistics',
        'bat_dong_san' => 'bat_dong_san',
        'bao_hiem' => 'bao_hiem',
        'xay_dung_kien_truc' => 'xay_dung_kien_truc',
        'y_te_duoc_pham' => 'y_te_duoc_pham',
        'giao_duc_dao_tao' => 'giao_duc_dao_tao',
        'du_lich_khach_san_nha_hang' => 'du_lich_khach_san_nha_hang',
        'luat' => 'luat',
        'thiet_ke_nghe_thuat' => 'thiet_ke_nghe_thuat',
        'lao_dong_pho_thong' => 'lao_dong_pho_thong',
        'nong_nghiep' => 'nong_nghiep',
        'food_beverage' => 'food_beverage',
        'nha_nuoc_cong_vu' => 'nha_nuoc_cong_vu',
        'khac' => 'khac',
    ];

    if (isset($mappedKey[$key]) && isset($lang[$mappedKey[$key]]) && is_string($lang[$mappedKey[$key]])) {
        return $lang[$mappedKey[$key]];
    }

    $pretty = preg_replace('/_+/', ' ', $raw);
    $pretty = preg_replace('/\s+/', ' ', $pretty);
    $pretty = trim((string) $pretty);

    return $pretty === '' ? $raw : $pretty;
}

function translateCompanyIndustry($value, $lang = []) {
    return translateIndustryDisplay($value, $lang);
}

function normalizeJobExperienceKey($value) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $normalized = strtolower($raw);
    $normalized = preg_replace('/[_\-]+/', ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    $normalized = trim($normalized);

    $aliases = [
        'none' => 'no_experience',
        'no experience' => 'no_experience',
        'no experience required' => 'no_experience',
        'not required' => 'no_experience',
        'khong yeu cau kinh nghiem' => 'no_experience',
        'under 1 year' => 'under_1_year',
        'under 1 year experience' => 'under_1_year',
        'under 1 year kinh nghiem' => 'under_1_year',
        'duoi 1 nam' => 'under_1_year',
        '1 year' => '1_year',
        '1 year experience' => '1_year',
        '1 year kinh nghiem' => '1_year',
        '1 nam' => '1_year',
        '2 years' => '2_years',
        '2 years experience' => '2_years',
        '2 years kinh nghiem' => '2_years',
        '2 nam' => '2_years',
        '3 years' => '3_years',
        '3 years experience' => '3_years',
        '3 years kinh nghiem' => '3_years',
        '3 nam' => '3_years',
        '4 years' => '4_years',
        '4 years experience' => '4_years',
        '4 years kinh nghiem' => '4_years',
        '4 nam' => '4_years',
        '5 years' => '5_years_plus',
        '5 years plus' => '5_years_plus',
        '5 years or more' => '5_years_plus',
        '5 years or more experience' => '5_years_plus',
        '5 years kinh nghiem' => '5_years_plus',
        '5 nam' => '5_years_plus',
        '6 months' => '6_months',
        '6 month' => '6_months',
        '6 months experience' => '6_months',
        '6 months kinh nghiem' => '6_months',
        '6 thang' => '6_months',
    ];

    if (isset($aliases[$normalized])) {
        return $aliases[$normalized];
    }

    $canonicalKey = str_replace(' ', '_', $normalized);
    $canonicalKey = preg_replace('/_+/', '_', $canonicalKey);
    return trim($canonicalKey, '_');
}

function normalizeAssetPath($path) {
    $raw = trim((string) $path);
    if ($raw === '') {
        return '';
    }

    if (preg_match('#^(https?://|data:|//)#i', $raw)) {
        return $raw;
    }

    $hasUploadsSegment = stripos($raw, 'uploads/') !== false || stripos($raw, 'companylogo/') !== false;
    if (!$hasUploadsSegment) {
        return $raw;
    }

    $normalized = preg_replace('#^(?:\./)+#', '', $raw);
    $normalized = preg_replace('#^(?:\.\./)+#', '', $normalized);
    $normalized = preg_replace('#^/+#', '', $normalized);

    return $normalized;
}

function translateJobExperience($value, $lang) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $canonicalKey = normalizeJobExperienceKey($raw);

    if (isset($lang['job_experience_labels'][$canonicalKey])) {
        $currentLang = $_SESSION['lang'] ?? 'en';
        return $lang['job_experience_labels'][$canonicalKey][$currentLang]
            ?? $lang['job_experience_labels'][$canonicalKey]['en']
            ?? $lang['job_experience_labels'][$canonicalKey];
    }

    return $raw;
}

function translateJobPosition($value, $lang = [], $displayLanguage = null) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $locale = $displayLanguage ?: ($_SESSION['lang'] ?? 'en');
    $normalizedKey = strtolower($raw);
    $normalizedKey = preg_replace('/[\s\-_]+/', '_', $normalizedKey);
    $normalizedKey = trim((string) $normalizedKey, '_');

    if (isset($lang['job_position_options']) && is_array($lang['job_position_options'])) {
        $optionMap = $lang['job_position_options'];
        if (isset($optionMap[$normalizedKey])) {
            $option = $optionMap[$normalizedKey];
            if (is_array($option)) {
                if (isset($option[$locale])) {
                    return (string) $option[$locale];
                }
                if (isset($option['en'])) {
                    return (string) $option['en'];
                }
                return (string) reset($option);
            }
            return (string) $option;
        }
    }

    $fallbackMap = [
        'senior' => ['en' => 'Senior', 'vi' => 'Chuyên viên cấp cao'],
        'junior' => ['en' => 'Junior', 'vi' => 'Chuyên viên'],
        'cong_tac_vien' => ['en' => 'Associate', 'vi' => 'Công tác viên'],
        'manager' => ['en' => 'Manager', 'vi' => 'Quản lý'],
        'director' => ['en' => 'Director', 'vi' => 'Giám đốc'],
        'lead' => ['en' => 'Team Lead', 'vi' => 'Trưởng nhóm'],
        'staff' => ['en' => 'Staff', 'vi' => 'Nhân viên'],
        'specialist' => ['en' => 'Specialist', 'vi' => 'Chuyên gia'],
        'assistant' => ['en' => 'Assistant', 'vi' => 'Trợ lý'],
        'executive' => ['en' => 'Executive', 'vi' => 'Chuyên viên cao cấp'],
        'other' => ['en' => 'Other', 'vi' => 'Khác'],
    ];

    if (isset($fallbackMap[$normalizedKey])) {
        return $fallbackMap[$normalizedKey][$locale] ?? $fallbackMap[$normalizedKey]['en'];
    }

    return ucfirst(str_replace('_', ' ', $normalizedKey));
}

function translateJobSalary($value, $lang = []) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $key = strtolower(preg_replace('/[\s\-]+/', '_', $raw));
    if ($key === 'thoa_thuan' || $key === 'negotiable') {
        return $lang['salary_negotiate'] ?? ($key === 'thoa_thuan' ? 'Thỏa thuận' : 'Negotiable');
    }

    return $raw;
}

function translateJobType($value, $lang = []) {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    $key = strtolower(preg_replace('/[\s\-]+/', '_', $raw));
    if ($key === 'internship') {
        $key = 'intern';
    }
    if (isset($lang['job_type_filters'][$key])) {
        $label = $lang['job_type_filters'][$key];
        if (is_array($label)) {
            $locale = $_SESSION['lang'] ?? 'en';
            return (string) ($label[$locale] ?? $label['en'] ?? reset($label));
        }
        return (string) $label;
    }

    return $raw;
}

// format ngày theo session ngôn ngữ
function formatDateByLocale($datetime) {
    $created_at = new DateTime($datetime);
    $lang = $_SESSION['lang'];

    if ($lang === 'vi') {
        $months = [
            'Jan'=>'Tháng 1','Feb'=>'Tháng 2','Mar'=>'Tháng 3','Apr'=>'Tháng 4',
            'May'=>'Tháng 5','Jun'=>'Tháng 6','Jul'=>'Tháng 7','Aug'=>'Tháng 8',
            'Sep'=>'Tháng 9','Oct'=>'Tháng 10','Nov'=>'Tháng 11','Dec'=>'Tháng 12',
        ];
        $month = $created_at->format('M');
        return $created_at->format('d ') . $months[$month] . $created_at->format(' Y, H:i');
    } else {
        return $created_at->format('M d, Y H:i');
    }
}
