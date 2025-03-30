<?php
function haversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $lat1 = deg2rad($lat1);
    $lat2 = deg2rad($lat2);

    $a = sin($dLat / 2) ** 2 + sin($dLon / 2) ** 2 * cos($lat1) * cos($lat2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

// Coordonate Piața Unirii și Palas Mall
$startLat = 47.164129;
$startLng = 27.582639;
$endLat = 47.158481;
$endLng = 27.601800;

// API config
$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;
$url = "https://api.tranzy.ai/v1/opendata/stops";

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

$statii = json_decode($response, true);
if (!is_array($statii)) {
    echo "<pre>Eroare la încărcarea datelor din API.\n\n";
    print_r($statii);
    echo "</pre>";
    exit;
}

$ceaMaiApropiataStart = null;
$ceaMaiApropiataEnd = null;
$minDistStart = PHP_FLOAT_MAX;
$minDistEnd = PHP_FLOAT_MAX;

foreach ($statii as $statie) {
    $lat = $statie['stop_lat'];
    $lon = $statie['stop_lon'];

    $distStart = haversineDistance($startLat, $startLng, $lat, $lon);
    if ($distStart < $minDistStart) {
        $minDistStart = $distStart;
        $ceaMaiApropiataStart = $statie;
    }

    $distEnd = haversineDistance($endLat, $endLng, $lat, $lon);
    if ($distEnd < $minDistEnd) {
        $minDistEnd = $distEnd;
        $ceaMaiApropiataEnd = $statie;
    }
}

// ✅ Output JSON rezultat
header('Content-Type: application/json');
echo json_encode([
    'statie_start' => [
        'nume' => $ceaMaiApropiataStart['stop_name'],
        'id' => $ceaMaiApropiataStart['stop_id'],
        'dist_km' => round($minDistStart, 3)
    ],
    'statie_finala' => [
        'nume' => $ceaMaiApropiataEnd['stop_name'],
        'id' => $ceaMaiApropiataEnd['stop_id'],
        'dist_km' => round($minDistEnd, 3)
    ]
], JSON_PRETTY_PRINT);
