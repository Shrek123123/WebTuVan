<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['employerpage_action'] ?? '') === 'prominent_company_payment') {
  require __DIR__ . '/dang_ky_cong_ty_nb_thanh_toan.php';
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Việc Nhẹ.vn - Trang nhà Tuyển dụng</title>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet"/>
  <style>
    body {
      margin: 0;
      font-family: 'Roboto', sans-serif;
    }

   
  </style>
</head>

<body>
    <?php
    include 'employerpage/header.php';
    include 'employerpage/body.php';
    ?>
</body>
<footer>
    <?php
    include 'employerpage/footer.php';
    ?>
</footer>
</html>