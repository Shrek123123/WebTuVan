<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PayOS\PayOS;

$payosConfig = require __DIR__ . '/../config/payos.php';

$payOS = new PayOS(
    clientId: $payosConfig['client_id'],
    apiKey: $payosConfig['api_key'],
    checksumKey: $payosConfig['checksum_key']
);

$YOUR_DOMAIN = $payosConfig['public_url'];

$data = [
    "orderCode" => intval(substr(strval(microtime(true) * 10000), -6)),
    "amount" => 2000,
    "description" => "Thanh toán đơn hàng",
    "returnUrl" => $YOUR_DOMAIN . "/public/success.html",
    "cancelUrl" => $YOUR_DOMAIN . "/public/cancel.html"
];

$response = $payOS->paymentRequests->create($data);

header("HTTP/1.1 303 See Other");
header("Location: " . $response->checkoutUrl);