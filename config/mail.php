<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

function sendEmail($to, $name, $subject, $body)
{
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();

    $getMailEnv = static function (string $key, string $default = ''): string {
        return trim((string) ($_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default));
    };

    $username = $getMailEnv('BREVO_SMTP_USERNAME');
    $password = $getMailEnv('BREVO_SMTP_PASSWORD');
    $fromEmail = $getMailEnv('BREVO_FROM_EMAIL');
    $fromName = $getMailEnv('BREVO_FROM_NAME', 'Việc Nhẹ.vn');

    if ($username === '' || $password === '' || $fromEmail === '') {
        error_log('Brevo email configuration is incomplete. Set BREVO_SMTP_USERNAME, BREVO_SMTP_PASSWORD, and BREVO_FROM_EMAIL.');
        return false;
    }

    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 0;

    try {
        $mail->isSMTP();
        $mail->Host       = $getMailEnv('BREVO_SMTP_HOST', 'smtp-relay.brevo.com');
        $mail->SMTPAuth   = true;
        $mail->Username   = $username;
        $mail->Password   = $password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int) $getMailEnv('BREVO_SMTP_PORT', '587');

        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();

        return true;

    } catch (Exception $e) {
        error_log('Brevo email delivery failed: ' . $e->getMessage() . ' ' . $mail->ErrorInfo);
        return false;
    }
}