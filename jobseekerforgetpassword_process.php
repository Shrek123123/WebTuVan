<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$respond = static function (bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit();
};

$action = (string) ($_POST['action'] ?? '');
$language = $_SESSION['lang'] ?? 'vi';
$langFile = __DIR__ . '/lang/' . basename($language) . '.php';
if (is_file($langFile)) {
    include $langFile;
}

$genericError = $lang['reset_generic_error'] ?? 'An error occurred. Please try again.';
$noEmailFound = $lang['no_email_found'] ?? "The email typed in doesn't match our records.";
$otpInvalid = $lang['otp_invalid'] ?? 'The verification code is invalid.';
$otpSent = $lang['otp_sent'] ?? 'A verification code has been sent to your email.';
$otpResent = $lang['otp_resent'] ?? 'A new verification code has been sent.';
$passwordMismatch = $lang['password_mismatch'] ?? 'Passwords do not match.';
$passwordTooShort = 'Mật khẩu cần có ít nhất 8 ký tự.';
$passwordResetSuccess = $lang['password_reset_success'] ?? 'Your password has been reset successfully.';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(false, $genericError);
}
if (empty($_SESSION['customer_password_reset_csrf'])
    || !hash_equals((string) $_SESSION['customer_password_reset_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
    $respond(false, $genericError);
}

if ($action === 'send_otp') {
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $respond(false, $noEmailFound);
    }

    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? AND role = 'customer' AND status = 'active' LIMIT 1");
    if (!$stmt) {
        $respond(false, $genericError);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $respond(false, $noEmailFound);
    }

    $otp = (string) random_int(10000, 99999);
    if (!sendPasswordResetOtp($user['email'], $otp)) {
        $respond(false, $genericError);
    }

    $userId = (int) $user['id'];
    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $resetStmt = $conn->prepare('INSERT INTO customer_password_reset (user_id, otp_hash, attempts) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE otp_hash = VALUES(otp_hash), attempts = 0, created_at = CURRENT_TIMESTAMP');
    if (!$resetStmt) {
        $respond(false, $genericError);
    }
    $resetStmt->bind_param('is', $userId, $otpHash);
    $resetStmt->execute();
    $resetStmt->close();
    $_SESSION['jobseeker_password_reset_user_id'] = $userId;
    $respond(true, $otpSent);
}

$resetUserId = (int) ($_SESSION['jobseeker_password_reset_user_id'] ?? 0);
$verifiedUserId = (int) ($_SESSION['jobseeker_password_reset_verified_user_id'] ?? 0);
if ($action === 'reset_password') {
    if ($verifiedUserId <= 0) {
        $respond(false, $otpInvalid);
    }

    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    if (strlen($newPassword) < 6) {
        $respond(false, $passwordTooShort);
    }
    if ($newPassword !== $confirmPassword) {
        $respond(false, $passwordMismatch);
    }

    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'customer' AND status = 'active'");
    if (!$stmt) {
        $respond(false, $genericError);
    }
    $stmt->bind_param('si', $passwordHash, $verifiedUserId);
    $updated = $stmt->execute();
    $stmt->close();

    if (!$updated) {
        $respond(false, $genericError);
    }

    unset($_SESSION['jobseeker_password_reset_user_id'], $_SESSION['jobseeker_password_reset_verified_user_id']);
    unset($_SESSION['customer_password_reset_csrf']);
    $respond(true, $passwordResetSuccess);
}

if ($resetUserId <= 0) {
    $respond(false, $noEmailFound);
}

if ($action === 'resend_otp') {
    $stmt = $conn->prepare("SELECT email FROM users WHERE id = ? AND role = 'customer' AND status = 'active' LIMIT 1");
    $stmt->bind_param('i', $resetUserId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) {
        $respond(false, $noEmailFound);
    }

    $otp = (string) random_int(10000, 99999);
    if (!sendPasswordResetOtp($user['email'], $otp)) {
        $respond(false, $genericError);
    }

    $otpHash = password_hash($otp, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('UPDATE customer_password_reset SET otp_hash = ?, attempts = 0, created_at = CURRENT_TIMESTAMP WHERE user_id = ?');
    $stmt->bind_param('si', $otpHash, $resetUserId);
    $stmt->execute();
    $stmt->close();
    $respond(true, $otpResent);
}

if ($action === 'verify_otp') {
    $otp = trim((string) ($_POST['otp'] ?? ''));
    $stmt = $conn->prepare('SELECT otp_hash, attempts FROM customer_password_reset WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $resetUserId);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$reset || !preg_match('/^\d{5}$/', $otp) || !password_verify($otp, $reset['otp_hash'])) {
        $attempts = (int) ($reset['attempts'] ?? 0) + 1;
        if ($attempts >= 5) {
            $deleteStmt = $conn->prepare('DELETE FROM customer_password_reset WHERE user_id = ?');
            $deleteStmt->bind_param('i', $resetUserId);
            $deleteStmt->execute();
            $deleteStmt->close();
        } else {
            $attemptStmt = $conn->prepare('UPDATE customer_password_reset SET attempts = ? WHERE user_id = ?');
            $attemptStmt->bind_param('ii', $attempts, $resetUserId);
            $attemptStmt->execute();
            $attemptStmt->close();
        }
        $respond(false, $otpInvalid);
    }

    $deleteStmt = $conn->prepare('DELETE FROM customer_password_reset WHERE user_id = ?');
    $deleteStmt->bind_param('i', $resetUserId);
    $deleteStmt->execute();
    $deleteStmt->close();
    $_SESSION['jobseeker_password_reset_verified_user_id'] = $resetUserId;
    unset($_SESSION['jobseeker_password_reset_user_id']);
    $respond(true, $lang['otp_verified'] ?? 'Code verified. Enter your new password.');
}

$respond(false, $genericError);
