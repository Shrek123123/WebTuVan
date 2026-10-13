<?php
session_start();
session_destroy();
header("Location: employerpage.php");
exit();
?>