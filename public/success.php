<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

$orderCode = filter_input(INPUT_GET, 'orderCode', FILTER_VALIDATE_INT) ?: 0;
if ($orderCode > 0) {
    reconcileJobPaymentOrder($conn, $orderCode);
}
$status = 'pending';
if ($orderCode > 0) {
    $stmt = $conn->prepare('SELECT status FROM job_payments WHERE order_code = ? LIMIT 1');
    $stmt->bind_param('i', $orderCode);
    $stmt->execute();
    $status = (string) ($stmt->get_result()->fetch_assoc()['status'] ?? 'pending');
    $stmt->close();
}
if (!in_array($status, ['paid', 'failed', 'pending'], true)) {
    $status = 'pending';
}
$statusContent = [
    'paid' => [
        'eyebrow' => 'Giao dịch hoàn tất',
        'title' => 'Thanh toán thành công',
        'message' => 'Bài đăng đã được tạo và đang chờ quản trị viên duyệt.',
        'icon' => '✓',
    ],
    'failed' => [
        'eyebrow' => 'Giao dịch chưa hoàn tất',
        'title' => 'Thanh toán không thành công',
        'message' => 'Bài đăng chưa được tạo. Bạn có thể quay lại và thử thanh toán lại.',
        'icon' => '!',
    ],
    'pending' => [
        'eyebrow' => 'Đang đối soát với PayOS',
        'title' => 'Đang xác nhận thanh toán',
        'message' => 'PayOS chưa xác nhận giao dịch thành công. Bài đăng chưa được tạo.',
        'icon' => '…',
    ],
][$status];
?><!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7f5">
    <title><?= htmlspecialchars($statusContent['title'], ENT_QUOTES, 'UTF-8') ?> | ViecNhe</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #172b2a;
            --muted: #657673;
            --line: #dce6e2;
            --surface: #ffffff;
            --canvas: #f3f7f5;
            --green: #19765b;
            --green-soft: #e5f4ed;
            --red: #ad3d3d;
            --red-soft: #fff0ed;
            --gold: #93670b;
            --gold-soft: #fff5d9;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 28px;
            display: grid;
            place-items: center;
            color: var(--ink);
            background-color: var(--canvas);
            background-image: linear-gradient(135deg, rgba(25, 118, 91, .045) 25%, transparent 25%),
                linear-gradient(315deg, rgba(173, 61, 61, .035) 25%, transparent 25%);
            background-size: 34px 34px;
            font-family: "Segoe UI", Tahoma, sans-serif;
        }

        .result {
            width: min(100%, 560px);
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 24px 70px rgba(24, 52, 44, .11);
            animation: arrive .45s ease-out both;
        }

        .result__topline { height: 7px; background: var(--green); }
        .result--failed .result__topline { background: var(--red); }
        .result--pending .result__topline { background: #d9a32b; }

        .result__content { padding: 42px 46px 36px; text-align: center; }

        .result__icon {
            display: grid;
            width: 68px;
            height: 68px;
            margin: 0 auto 22px;
            place-items: center;
            border-radius: 50%;
            color: var(--green);
            background: var(--green-soft);
            font-size: 34px;
            font-weight: 700;
        }

        .result--failed .result__icon { color: var(--red); background: var(--red-soft); }
        .result--pending .result__icon { color: var(--gold); background: var(--gold-soft); }

        .result__eyebrow {
            margin: 0 0 9px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        h1 { margin: 0; font-size: 30px; line-height: 1.2; }

        .result__message {
            max-width: 390px;
            margin: 14px auto 27px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.65;
        }

        .reference {
            padding: 15px 18px;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #f9fbfa;
            text-align: left;
        }

        .reference__label { display: block; margin-bottom: 5px; color: var(--muted); font-size: 12px; }
        .reference__value { font-size: 15px; font-weight: 700; overflow-wrap: anywhere; }

        .result__actions { margin-top: 26px; }

        .result__button {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 0 22px;
            border-radius: 6px;
            color: #fff;
            background: var(--ink);
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color .18s ease, transform .18s ease;
        }

        .result__button:hover { background: var(--green); transform: translateY(-1px); }
        .result__brand { padding: 15px; border-top: 1px solid var(--line); color: #71817d; font-size: 12px; text-align: center; }
        .result__brand img { display: block; width: min(190px, 70%); height: auto; margin: 0 auto 8px; }

        @keyframes arrive {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 520px) {
            body { padding: 16px; }
            .result__content { padding: 32px 22px 27px; }
            .result__icon { width: 60px; height: 60px; margin-bottom: 19px; font-size: 30px; }
            h1 { font-size: 25px; }
            .result__message { font-size: 14px; }
            .result__button { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
    <link rel="stylesheet" href="../mediato-theme.css?v=6">
</head>
<body>
    <main class="result result--<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>">
        <div class="result__topline" aria-hidden="true"></div>
        <section class="result__content" aria-live="polite">
            <div class="result__icon" aria-hidden="true"><?= htmlspecialchars($statusContent['icon'], ENT_QUOTES, 'UTF-8') ?></div>
            <p class="result__eyebrow"><?= htmlspecialchars($statusContent['eyebrow'], ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars($statusContent['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="result__message"><?= htmlspecialchars($statusContent['message'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="reference">
                <span class="reference__label">Mã giao dịch</span>
                <span class="reference__value"><?= $orderCode > 0 ? htmlspecialchars((string) $orderCode, ENT_QUOTES, 'UTF-8') : 'Không có mã giao dịch' ?></span>
            </div>
            <div class="result__actions">
                <a class="result__button" href="../employerprofile/my_job_posts.php">Quay lại danh sách tin</a>
            </div>
        </section>
        <footer class="result__brand"><img src="../image/logo.png" alt="ViecNhe" onerror="this.src='../image/favicon.svg'"><span>Thanh toán bảo mật bởi PayOS</span></footer>
    </main>
</body>
</html>
