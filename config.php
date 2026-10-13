<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'sql_viecn_22679b');
define('DB_PASS', '9bd597303441b');
define('DB_NAME', 'sql_viecn_22679b');
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'jobhive');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}