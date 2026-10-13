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
    if (!isset($_SESSION['username'])) {
        $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
        header("Location: $redirect");
        exit();
    }

    // Kiểm tra quyền truy cập jobseekerprofile.php?id=...
    if (!isset($_SESSION['jobseeker_id'])) {
        header("Location: index.php");
        exit();
    }
    include 'jobseekerprofile/header.php';
    include 'jobseekerprofile/body.php';
    ?>

    <footer>
        <?php
        include 'jobseekerprofile/footer.php';
        ?>
    </footer>
</body>

</html>