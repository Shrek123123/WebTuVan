<?php
session_start();
if (empty($_SESSION['customer_password_reset_csrf'])) {
    $_SESSION['customer_password_reset_csrf'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['jobseeker_password_reset_user_id']) && !isset($_SESSION['jobseeker_password_reset_verified_user_id'])) {
    header('Location: jobseekerforgetpassword.php');
    exit();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true)) {
    $_SESSION['lang'] = 'vi';
}
include __DIR__ . '/lang/' . basename($_SESSION['lang']) . '.php';
$isVerified = isset($_SESSION['jobseeker_password_reset_verified_user_id']);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['jobseekerforgetpassword_title'] ?? 'Password recovery', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root { --red: #b42318; --ink: #202124; --muted: #667085; --line: #e4e7ec; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px; background: linear-gradient(135deg, #fff4f2, #f4f7fb); font-family: Arial, sans-serif; color: var(--ink); }
        .recovery-shell { width: min(100%, 440px); padding: 28px; border: 1px solid var(--line); border-radius: 16px; background: #fff; box-shadow: 0 18px 55px rgba(16, 24, 40, .12); text-align: center; }
        .recovery-shell h1 { margin: 0 0 8px; color: var(--red); font-size: 24px; }
        .recovery-shell p { margin: 0; color: var(--muted); line-height: 1.5; }
        .modal-backdrop[hidden] { display: none; }
        .modal-backdrop { position: fixed; inset: 0; z-index: 10; display: grid; place-items: center; padding: 18px; background: rgba(16, 24, 40, .58); }
        .modal-card { position: relative; width: min(100%, 430px); padding: 30px; border-radius: 16px; background: #fff; box-shadow: 0 24px 70px rgba(16, 24, 40, .25); }
        .modal-card h2 { margin: 0 36px 8px 0; color: var(--red); font-size: 22px; }
        .modal-card p { color: var(--muted); line-height: 1.5; }
        .modal-close { position: absolute; top: 12px; right: 14px; border: 0; background: transparent; color: #667085; font-size: 28px; line-height: 1; cursor: pointer; }
        label { display: block; margin: 16px 0 7px; font-weight: 700; text-align: left; }
        input { width: 100%; padding: 12px 13px; border: 1px solid var(--line); border-radius: 8px; font-size: 16px; }
        input:focus { outline: 3px solid rgba(180, 35, 24, .14); border-color: var(--red); }
        .otp-input { text-align: center; letter-spacing: 9px; font-size: 24px; font-weight: 700; }
        .primary-button, .resend-button { width: 100%; margin-top: 18px; padding: 12px 16px; border: 0; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .primary-button { color: #fff; background: var(--red); }
        .primary-button:disabled, .resend-button:disabled { opacity: .55; cursor: not-allowed; }
        .resend-button { margin-top: 10px; color: var(--red); background: #fff4f2; }
        .message { min-height: 21px; margin: 14px 0 0 !important; color: var(--red) !important; font-size: 14px; }
        .password-wrap { position: relative; }
        .password-wrap input { padding-right: 46px; }
        .toggle-password { position: absolute; top: 50%; right: 8px; width: 34px; height: 34px; transform: translateY(-50%); border: 0; border-radius: 6px; background: transparent; color: #667085; cursor: pointer; font-size: 18px; }
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
    <main class="recovery-shell">
        <h1><?= htmlspecialchars($lang['otp_title'] ?? 'Verify your email', ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($lang['otp_instruction'] ?? 'Enter the 5-digit code sent to your email.', ENT_QUOTES, 'UTF-8') ?></p>
    </main>

    <div class="modal-backdrop" id="otp-modal" <?= $isVerified ? 'hidden' : '' ?>>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="otp-modal-title">
            <button type="button" class="modal-close" data-close-modal aria-label="Close">&times;</button>
            <h2 id="otp-modal-title"><?= htmlspecialchars($lang['otp_title'] ?? 'Verify your email', ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($lang['otp_instruction'] ?? 'Enter the 5-digit code sent to your email.', ENT_QUOTES, 'UTF-8') ?></p>
            <form id="otp-form">
                <label for="otp-code"><?= htmlspecialchars($lang['otp_label'] ?? 'Verification code', ENT_QUOTES, 'UTF-8') ?></label>
                <input class="otp-input" id="otp-code" name="otp" type="text" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" autocomplete="one-time-code" required>
                <p class="message" id="otp-message" role="alert"></p>
                <button class="primary-button" type="submit" id="verify-otp"><?= htmlspecialchars($lang['verify_otp'] ?? 'Verify code', ENT_QUOTES, 'UTF-8') ?></button>
            </form>
            <button class="resend-button" type="button" id="resend-otp"><?= htmlspecialchars($lang['resend_otp'] ?? 'Resend code', ENT_QUOTES, 'UTF-8') ?></button>
            <p class="message" id="resend-message" role="status"></p>
        </section>
    </div>

    <div class="modal-backdrop" id="password-modal" <?= $isVerified ? '' : 'hidden' ?>>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="password-modal-title">
            <button type="button" class="modal-close" data-close-modal aria-label="Close">&times;</button>
            <h2 id="password-modal-title"><?= htmlspecialchars($lang['reset_password'] ?? 'Reset password', ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars($lang['new_password'] ?? 'Enter your new password.', ENT_QUOTES, 'UTF-8') ?></p>
            <form id="password-form">
                <label for="new-password"><?= htmlspecialchars($lang['new_password'] ?? 'New password', ENT_QUOTES, 'UTF-8') ?></label>
                <div class="password-wrap"><input id="new-password" name="new_password" type="password" minlength="8" autocomplete="new-password" required><button type="button" class="toggle-password" data-target="new-password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button></div>
                <label for="confirm-password"><?= htmlspecialchars($lang['confirm_password'] ?? 'Confirm password', ENT_QUOTES, 'UTF-8') ?></label>
                <div class="password-wrap"><input id="confirm-password" name="confirm_password" type="password" minlength="8" autocomplete="new-password" required><button type="button" class="toggle-password" data-target="confirm-password" aria-label="Show password" aria-pressed="false"><span class="password-eye-icon" aria-hidden="true"></span></button></div>
                <p class="message" id="password-message" role="alert"></p>
                <button class="primary-button" type="submit" id="reset-password"><?= htmlspecialchars($lang['reset_password'] ?? 'Reset password', ENT_QUOTES, 'UTF-8') ?></button>
            </form>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const endpoint = 'jobseekerforgetpassword_process.php';
            const otpModal = document.getElementById('otp-modal');
            const passwordModal = document.getElementById('password-modal');
            const otpForm = document.getElementById('otp-form');
            const passwordForm = document.getElementById('password-form');
            const otpMessage = document.getElementById('otp-message');
            const resendMessage = document.getElementById('resend-message');
            const passwordMessage = document.getElementById('password-message');
            const resendButton = document.getElementById('resend-otp');
            let resendTimer = null;

            async function sendAction(action, values) {
                const body = new URLSearchParams({ action, csrf_token: <?= json_encode($_SESSION['customer_password_reset_csrf']) ?>, ...(values || {}) });
                const response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
                return response.json();
            }
            function setMessage(element, text) { element.textContent = text || ''; }
            function startResendLock() {
                clearInterval(resendTimer);
                let seconds = 60;
                resendButton.disabled = true;
                resendMessage.textContent = String(seconds);
                resendTimer = setInterval(function () {
                    seconds -= 1;
                    resendMessage.textContent = seconds > 0 ? String(seconds) : '';
                    if (seconds <= 0) { clearInterval(resendTimer); resendButton.disabled = false; }
                }, 1000);
            }
            function moveToPasswordModal() {
                otpModal.hidden = true;
                passwordModal.hidden = false;
                document.getElementById('new-password').focus();
            }

            if (!passwordModal.hidden) document.getElementById('new-password').focus();
            else document.getElementById('otp-code').focus();

            otpForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = document.getElementById('verify-otp');
                button.disabled = true;
                setMessage(otpMessage, '');
                try {
                    const data = await sendAction('verify_otp', { otp: document.getElementById('otp-code').value });
                    setMessage(otpMessage, data.message);
                    if (data.success) moveToPasswordModal();
                } catch (error) { setMessage(otpMessage, '<?= addslashes($lang['reset_generic_error'] ?? 'An error occurred. Please try again.') ?>'); }
                button.disabled = false;
            });

            resendButton.addEventListener('click', async function () {
                resendButton.disabled = true;
                try {
                    const data = await sendAction('resend_otp');
                    setMessage(resendMessage, data.message);
                    if (data.success) startResendLock();
                    else resendButton.disabled = false;
                } catch (error) { setMessage(resendMessage, '<?= addslashes($lang['reset_generic_error'] ?? 'An error occurred. Please try again.') ?>'); resendButton.disabled = false; }
            });

            passwordForm.addEventListener('submit', async function (event) {
                event.preventDefault();
                const button = document.getElementById('reset-password');
                button.disabled = true;
                try {
                    const data = await sendAction('reset_password', { new_password: document.getElementById('new-password').value, confirm_password: document.getElementById('confirm-password').value });
                    setMessage(passwordMessage, data.message);
                    if (data.success) setTimeout(function () { window.location.href = 'jobseekerlogin.php'; }, 1200);
                } catch (error) { setMessage(passwordMessage, '<?= addslashes($lang['reset_generic_error'] ?? 'An error occurred. Please try again.') ?>'); }
                button.disabled = false;
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (button) {
                button.addEventListener('click', function () { window.location.href = 'jobseekerforgetpassword.php'; });
            });
            document.querySelectorAll('.toggle-password').forEach(function (button) {
                button.addEventListener('click', function () {
                    const input = document.getElementById(button.dataset.target);
                    const visible = input.type === 'text';
                    input.type = visible ? 'password' : 'text';
                    button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
                    button.setAttribute('aria-pressed', visible ? 'false' : 'true');
                });
            });
        });
    </script>
</body>
</html>
