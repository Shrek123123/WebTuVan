<?php
// Thay đổi chuỗi mật khẩu bí mật này khớp với Secret bạn cài trên GitHub Webhook
$secret = 'matkhaucucmanh2026';

if (!isset($_GET['token']) || $_GET['token'] !== $secret) {
    http_response_code(403);
    die("Access denied");
}

// Kéo dữ liệu mới nhất từ remote và ép reset hard về nhánh main
$output = shell_exec('cd /www/wwwroot/viecnhe.io.vn && git fetch origin main && git reset --hard origin/main 2>&1');

// Ghi kết quả ra log và in ra màn hình (đã fix lỗi deprecation null)
echo "<pre>";
echo "=== WEBHOOK AUTO-DEPLOY SUCCESS ===\n\n";
echo htmlspecialchars((string)$output);
echo "</pre>";
?>