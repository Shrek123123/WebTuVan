<?php
session_start();
if (!isset($_SESSION['usernameemployer'])) {
    header("Location: employerpage.php");
    exit();
}

// Load language
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'vi';
}
include 'lang/' . $_SESSION['lang'] . '.php';

require_once 'config.php';
require_once 'helpers.php';

$company_id = $_POST['company_id'] ?? 0;
$company_data = null;

if ($company_id > 0 && isset($conn)) {
    $sql = "SELECT * FROM company WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $company_data = $result->fetch_assoc();
    $stmt->close();
}

if (!$company_data) {
    echo "Company not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/svg+xml" href="image/favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Information</title>
</head>
<body>
    <h1>Company Details</h1>
    <p><strong>Name:</strong> <?= htmlspecialchars($company_data['company_name']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($company_data['company_address']) ?></p>
    <p><strong>Industry:</strong> <?= htmlspecialchars(translateCompanyIndustry($company_data['industry'] ?? '', $lang)) ?></p>
    <p><strong>Information:</strong> <?= htmlspecialchars($company_data['information']) ?></p>
    <?php if ($company_data['banner_logo']): ?>
        <img src="<?= htmlspecialchars($company_data['banner_logo']) ?>" alt="Banner" style="max-width: 300px;">
    <?php endif; ?>
</body>
</html>