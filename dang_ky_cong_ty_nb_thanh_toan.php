<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: employerpage.php', true, 303);
    exit;
}
if (!isset($_SESSION['employerid'])) {
    header('Location: employerlogin.php?redirect=employerpage.php', true, 303);
    exit;
}

$sessionToken = (string) ($_SESSION['csrf_prominent_company'] ?? '');
$postedToken = (string) ($_POST['csrf_token'] ?? '');
if ($sessionToken === '' || $postedToken === '' || !hash_equals($sessionToken, $postedToken)) {
    http_response_code(400);
    exit('Phiên đăng ký không hợp lệ. Vui lòng quay lại và thử lại.');
}

$langCode = in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true) ? $_SESSION['lang'] : 'vi';
$lang = [];
require __DIR__ . '/lang/' . $langCode . '.php';
$errorId = bin2hex(random_bytes(4));

try {
    $companyId = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($companyId === false || $companyId === null) {
        throw new InvalidArgumentException('Invalid company selection.');
    }
    $payment = createProminentCompanyPaymentCheckout($conn, (int) $_SESSION['employerid'], (int) $companyId);
    $checkoutUrl = (string) ($payment['checkout_url'] ?? '');
    if ($checkoutUrl === '') {
        throw new RuntimeException('PayOS checkout URL is empty.');
    }
    if (headers_sent($sentFile, $sentLine)) {
        throw new RuntimeException('Response headers already sent at ' . $sentFile . ':' . $sentLine);
    }
    header('Cache-Control: no-store');
    header('Location: ' . $checkoutUrl, true, 303);
    exit;
} catch (InvalidArgumentException $exception) {
    if ($exception->getCode() === 409) {
        http_response_code(200);
        $duplicateMessage = json_encode($lang['prominent_company_already_registered'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        echo '<script>alert(' . $duplicateMessage . '); window.location.replace("employerpage.php");</script>';
        exit;
    }
    error_log('Prominent company checkout error [' . $errorId . ']: ' . $exception->getMessage());
    http_response_code(502);
} catch (Throwable $exception) {
    error_log('Prominent company checkout error [' . $errorId . ']: ' . $exception->getMessage());
    http_response_code(502);
}
?><!doctype html>
<html lang="<?= htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($lang['prominent_company_payment_title'], ENT_QUOTES, 'UTF-8') ?> | ViecNhe</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;box-sizing:border-box;background:#f3f7f5;color:#20332e;font-family:Arial,sans-serif}
        main{width:min(100%,560px);padding:36px;background:#fff;border:1px solid #dce8e2;border-radius:8px;text-align:center;box-shadow:0 18px 48px #18342c18}
        .brand{display:block;width:min(210px,72%);height:auto;margin:0 auto 24px}h1{font-size:24px;line-height:1.35}p{line-height:1.65;color:#52645f}.error-id{font-family:monospace;color:#657673}a{display:inline-block;margin-top:16px;padding:11px 16px;background:#19765b;color:#fff;text-decoration:none;border-radius:4px}
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
<main>
    <img class="brand" src="image/logo.png" alt="ViecNhe" onerror="this.src='image/favicon.svg'">
    <h1><?= htmlspecialchars($lang['prominent_company_payment_error'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($lang['prominent_company_payment_title'], ENT_QUOTES, 'UTF-8') ?></p>
    <p class="error-id"><?= htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') ?></p>
    <a href="employerpage.php"><?= htmlspecialchars($lang['prominent_company_back'], ENT_QUOTES, 'UTF-8') ?></a>
</main>
</body>
</html>