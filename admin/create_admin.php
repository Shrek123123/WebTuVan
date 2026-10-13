<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found.');
}

require_once __DIR__ . '/../config.php';

$email = trim((string) ($argv[1] ?? ''));
$password = getenv('MEDIATO_ADMIN_PASSWORD');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php admin/create_admin.php admin@example.com\n");
    exit(2);
}
if (!is_string($password) || $password === '') {
    fwrite(STDERR, "Set MEDIATO_ADMIN_PASSWORD in the process environment before running this script.\n");
    exit(2);
}
if (strlen($password) < 8) {
    fwrite(STDERR, "Warning: this administrator password is shorter than 8 characters; change it immediately after setup.\n");
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
if ($passwordHash === false) {
    throw new RuntimeException('Could not hash administrator password.');
}

$conn->begin_transaction();
try {
    $lookup = $conn->prepare('SELECT id, role FROM users WHERE email = ? LIMIT 1 FOR UPDATE');
    if (!$lookup) {
        throw new RuntimeException('Could not prepare administrator lookup: ' . $conn->error);
    }
    $lookup->bind_param('s', $email);
    $lookup->execute();
    $existing = $lookup->get_result()->fetch_assoc();
    $lookup->close();

    if ($existing && $existing['role'] !== 'admin') {
        throw new RuntimeException('This email is already assigned to a non-admin account; refusing to promote it.');
    }

    if ($existing) {
        $adminId = (int) $existing['id'];
        $update = $conn->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE id = ? AND role = 'admin'");
        if (!$update) {
            throw new RuntimeException('Could not prepare administrator update: ' . $conn->error);
        }
        $update->bind_param('si', $passwordHash, $adminId);
        $update->execute();
        $update->close();
    } else {
        $fullName = 'Mediato Admin';
        $phone = 'Chưa cập nhật';
        $insert = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, 'admin')");
        if (!$insert) {
            throw new RuntimeException('Could not prepare administrator creation: ' . $conn->error);
        }
        $insert->bind_param('ssss', $fullName, $email, $phone, $passwordHash);
        $insert->execute();
        $adminId = (int) $conn->insert_id;
        $insert->close();
    }

    $conn->commit();
    fwrite(STDOUT, "Administrator account ready for {$email} (id {$adminId}).\n");
} catch (Throwable $exception) {
    $conn->rollback();
    throw $exception;
}
