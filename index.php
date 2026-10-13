<?php
error_reporting(E_ALL); // báo lỗi ra
ini_set('display_errors', 1); // đảm bảo lỗi hiển thị

$action = isset($_GET['action']) ? $_GET['action'] : 'home';

if ($action === 'search' || $action === 'results') {
    include_once(__DIR__ . '/app/controllers/JobControllers.php');
    $controller = new JobController();

    if ($action === 'search') {
        $controller->search();
    } elseif ($action === 'results') {
        $controller->searchResults();
    }

    exit;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nền tảng tìm việc nhẹ, việc làm thêm uy tín tại Việt Nam...">
    <title>Việc Nhẹ VN - Tìm việc làm nhanh, uy tín</title>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
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