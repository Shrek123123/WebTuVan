<?php
error_reporting(E_ALL); // báo lỗi ra
ini_set('display_errors', 1); // đảm bảo lỗi hiển thị

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

if ($action === 'search' || $action === 'results') {
    header('Location: searchpage.php?' . http_build_query($_GET), true, 302);
    exit;
}


?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Tra cứu hồ sơ hòa giải viên theo chuyên môn, mức phí và tình trạng hiện tại.">
    <title>Danh bạ Hòa giải viên</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <link rel="stylesheet" href="mediato-theme.css?v=6">
</head>

<body>
    <?php
    include_once(__DIR__ . '/homepage/header.php');
    include_once(__DIR__ . '/homepage/body.php');
    ?>
</body>
<footer>
    <?php
    include_once(__DIR__ . '/homepage/footer.php');
    ?>
</footer>

</html>