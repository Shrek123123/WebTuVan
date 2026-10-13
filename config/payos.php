<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$payosEnv = static function (string $name, string $legacyName = ''): string {
    $value = $_ENV[$name] ?? $_SERVER[$name] ?? '';
    if (trim((string) $value) === '') {
        $value = getenv($name) ?: '';
    }
    if (trim((string) $value) === '' && $legacyName !== '') {
        $value = $_ENV[$legacyName] ?? $_SERVER[$legacyName] ?? '';
        if (trim((string) $value) === '') {
            $value = getenv($legacyName) ?: '';
        }
    }

    return trim((string) $value);
};

$clientId = $payosEnv('CLIENT_ID', 'PAYOS_CLIENT_ID');
$apiKey = $payosEnv('API_KEY', 'PAYOS_API_KEY');
$checksumKey = $payosEnv('CHECKSUM_KEY', 'PAYOS_CHECKSUM_KEY');

foreach (['CLIENT_ID' => $clientId, 'API_KEY' => $apiKey, 'CHECKSUM_KEY' => $checksumKey] as $name => $value) {
    if ($value === '') {
        throw new RuntimeException("Missing required PayOS environment variable: {$name}");
    }
}

$publicUrl = rtrim($payosEnv('APP_URL'), '/');
if ($publicUrl === '') {
    throw new RuntimeException('Missing required application environment variable: APP_URL');
}

return [
    'client_id' => $clientId,
    'api_key' => $apiKey,
    'checksum_key' => $checksumKey,
    'public_url' => $publicUrl,
];
