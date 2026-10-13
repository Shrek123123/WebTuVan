<?php
session_start();
$language = $_SESSION['lang'] ?? 'vi';
if (!in_array($language, ['vi', 'en'], true)) {
    $language = 'vi';
}
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['lang'] = $language;
header("Location: index.php");
exit();
?>