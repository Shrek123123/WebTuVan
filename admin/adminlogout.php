<?php
session_start();

if (!isset($_POST['admin_logout'])) {
    // Redirect back to the previous page
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Destroy session and logout
session_unset();
session_destroy();

// Redirect to login page or homepage after logout
header('Location: adminlogin.php');
exit();

?>