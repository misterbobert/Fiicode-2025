<?php
$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;
$url = "https://api.tranzy.ai/v1/opendata/routes";

$headers = [
    "Accept: application/json",
    "X-API-KEY: $apiKey",
    "X-Agency-Id: $agencyId"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if ($response === false) {
    echo 'cURL error: ' . curl_error($ch);
    exit;
}
curl_close($ch);

// Afișăm JSON-ul în browser
header('Content-Type: application/json');
echo $response;
