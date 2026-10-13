<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

$langCode = in_array($_SESSION['lang'] ?? 'vi', ['vi', 'en'], true) ? $_SESSION['lang'] : 'vi';
$lang = [];
require __DIR__ . '/lang/' . $langCode . '.php';
$orderCode = (int) ($_GET['orderCode'] ?? 0);
$status = $orderCode > 0 ? reconcileProminentCompanyPayment($conn, $orderCode) : 'failed';
$messageKey = $status === 'paid'
    ? 'prominent_company_payment_paid'
    : ($status === 'failed' ? 'prominent_company_payment_failed' : 'prominent_company_payment_pending');
?><!doctype html>
<html lang="<?= htmlspecialchars($langCode, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($lang['prominent_company_payment_title'], ENT_QUOTES, 'UTF-8') ?> | ViecNhe</title>
    <style>
        body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;box-sizing:border-box;background:#f3f7f5;color:#20332e;font-family:Arial,sans-serif}
        main{width:min(100%,560px);padding:36px;background:#fff;border:1px solid #dce8e2;border-radius:8px;text-align:center;box-shadow:0 18px 48px #18342c18}
        .brand{display:block;width:min(210px,72%);height:auto;margin:0 auto 24px}h1{font-size:24px;line-height:1.35}p{line-height:1.65;color:#52645f}a{display:inline-block;margin-top:16px;padding:11px 16px;background:#19765b;color:#fff;text-decoration:none;border-radius:4px}
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>
<body>
<main>
    <img class="brand" src="image/logo.png" alt="ViecNhe" onerror="this.src='image/favicon.svg'">
    <h1><?= htmlspecialchars($lang['prominent_company_payment_title'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($lang[$messageKey], ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($orderCode > 0): ?><p><?= htmlspecialchars((string) $orderCode, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <a href="employerpage.php"><?= htmlspecialchars($lang['prominent_company_back'], ENT_QUOTES, 'UTF-8') ?></a>
</main>
</body>
</html>