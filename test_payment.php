<?php
$payload = json_decode(file_get_contents('php://input'), true);

// Xử lý payload ở đây

header('Content-Type: application/json');
http_response_code(200);
echo json_encode(['success' => true]);