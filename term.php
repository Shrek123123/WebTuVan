<?php
session_start();

if (isset($_GET['lang']) && in_array($_GET['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang']) || !in_array($_SESSION['lang'], ['vi', 'en'], true)) {
    $_SESSION['lang'] = 'vi';
}

include __DIR__ . '/lang/' . $_SESSION['lang'] . '.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_SESSION['lang'], ENT_QUOTES, 'UTF-8') ?>">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang['terms_page_title'], ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
            color: #333;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .logo {
            display: block;
            height: 60px;
            max-width: 100%;
            margin-bottom: 15px;
            object-fit: contain;
            object-position: left center;
        }

        .intro-title {
            font-weight: bold;
            margin-top: 10px;
        }

        .intro-description {
            margin-top: 8px;
            line-height: 1.5;
        }

        .red-box {
            margin-top: 20px;
            padding: 15px;
            background-color: #f4f4f4;
            border: 1px solid #e74c3c;
            border-radius: 5px;
            line-height: 1.5;
        }

        .red-box-title {
            font-weight: bold;
            font-size: 20px;
        }

        .red-box p {
            margin: 12px 0;
        }

        .warning {
            color: red;
        }

        .hotline {
            white-space: nowrap;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                padding: 16px;
            }

            .logo {
                height: 48px;
            }

            .hotline {
                white-space: normal;
            }
        }
    </style>
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>

<body>
    <main class="container">
        <a href="index.php">
            <img class="logo" src="image/logo.png" alt="<?= htmlspecialchars($lang['terms_logo_alt'], ENT_QUOTES, 'UTF-8') ?>">
        </a>
        <div class="intro-title"><?= htmlspecialchars($lang['terms_register_title'], ENT_QUOTES, 'UTF-8') ?></div>
        <div class="intro-description"><?= htmlspecialchars($lang['terms_register_description'], ENT_QUOTES, 'UTF-8') ?></div>

        <section class="red-box">
            <div class="red-box-title"><?= htmlspecialchars($lang['terms_regulations_title'], ENT_QUOTES, 'UTF-8') ?></div>
            <p><?= $lang['terms_multiple_accounts'] ?></p>
            <p><?= htmlspecialchars($lang['terms_duplicate_accounts'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><?= htmlspecialchars($lang['terms_recruiter_support'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><?= htmlspecialchars($lang['terms_contact_notice'], ENT_QUOTES, 'UTF-8') ?></p>
            <p class="hotline"><strong><?= htmlspecialchars($lang['terms_hotline_1'], ENT_QUOTES, 'UTF-8') ?></strong>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <strong><?= htmlspecialchars($lang['terms_hotline_2'], ENT_QUOTES, 'UTF-8') ?></strong>
            </p>
        </section>
    </main>
</body>

</html>
