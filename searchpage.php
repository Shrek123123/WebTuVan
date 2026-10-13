<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tìm hiểu hồ sơ hòa giải viên và gửi yêu cầu đặt lịch tư vấn phù hợp.">
    <title>Tìm hòa giải viên</title>
    <style>
        :root {
            color-scheme: light;
            --mediator-paper: #f5f3ed;
            --mediator-ink: #1d302f;
            --mediator-teal: #2f7068;
            --mediator-terracotta: #a75e49;
            --mediator-muted: #5e6d69;
            --mediator-line: #dce1da;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--mediator-ink);
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--mediator-paper);
        }

        h1 {
            margin: 0;
        }
        body > footer { margin-top: auto; }
        :focus-visible { outline: 3px solid rgba(47, 112, 104, .4); outline-offset: 3px; }
    </style>

    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>

<body>
    <?php
    include_once 'searchpage/header.php';
    include_once 'searchpage/body.php';
    ?>
    <?php include_once 'searchpage/footer.php'; ?>
</body>

</html>