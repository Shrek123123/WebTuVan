<?php

require_once 'config/mail.php';

$result = sendEmail(
    'hotdogicy2@gmail.com',
    'Minh',
    'Test email từ ViecNhe',
    '<h2>Xin chào!</h2><p>Email SMTP đã hoạt động.</p>'
);

if ($result) {
    echo 'Email đã được gửi!';
} else {
    echo 'Gửi email thất bại!<br>';
    echo 'Hãy kiểm tra error log của PHP.';
}