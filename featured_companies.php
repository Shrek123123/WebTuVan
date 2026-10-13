<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($offset === false || $offset === null || $offset % 6 !== 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid offset']);
    exit;
}

$companies = getProminentCompanies($conn, 7, $offset);
$hasMore = count($companies) > 6;
$companies = array_slice($companies, 0, 6);
$responseCompanies = [];
foreach ($companies as $company) {
    $companyName = (string) $company['company_name'];
    $responseCompanies[] = [
        'id' => (int) $company['id'],
        'company_name' => $companyName,
        'display_name' => truncateCompanyName($companyName),
        'logo' => normalizeCompanyLogoPath($company['company_logo'] ?? ''),
    ];
}

echo json_encode([
    'companies' => $responseCompanies,
    'has_more' => $hasMore,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);