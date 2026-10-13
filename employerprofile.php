<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your profile - ViecNhe</title>
</head>

<body>

    <?php
    session_start();
    if (!isset($_SESSION['usernameemployer'])) {
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'employerpage.php';
        header("Location: $redirect");
        exit();
    }

    // Kiểm tra quyền truy cập employerprofile.php
    if (!isset($_SESSION['employerid'])) {
        header("Location: employerpage.php");
        exit();
    }
    include 'employerprofile/header.php';
    include 'employerprofile/body.php';
    ?>

    <footer>
        <?php
        include 'employerprofile/footer.php';
        ?>
    </footer>
</body>
</html>