<?php
$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;
$tripId = "1_0"; // hardcodare pt test (poți face dinamic mai târziu)

$headers = [
    "Accept: application/json",
    "X-API-KEY: $apiKey",
    "X-Agency-Id: $agencyId"
];

function apiGET($url, $headers) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

// ✅ Citim ID-urile din URL
$startId = $_GET['startId'] ?? null;
$endId = $_GET['endId'] ?? null;

if (!$startId || !$endId) {
    die("Trebuie să specifici ?startId=...&endId=... în URL.");
}

// ✅ Cerere corectă cu agency_id
$stopsRaw = apiGET("https://api.tranzy.ai/v1/opendata/stops?agency_id=$agencyId", $headers);
$stops = $stopsRaw['data'] ?? [];

function getStatieById($id, $stops) {
    foreach ($stops as $s) {
        if ($s['stop_id'] == $id) return $s;
    }
    return null;
}

$startStatie = getStatieById($startId, $stops);
$endStatie   = getStatieById($endId, $stops);

if (!$startStatie || !$endStatie) {
    die("Nu am găsit una dintre stații după ID.");
}

// Coordonate pentru mers pe jos
$startLat = $startStatie['lat'];
$startLng = $startStatie['lon'];
$endLat   = $endStatie['lat'];
$endLng   = $endStatie['lon'];

// Obținem stop_times pentru acel trip
$stopTimes = apiGET("https://api.tranzy.ai/v1/opendata/stop_times?trip_id=$tripId", $headers);
if (!$stopTimes) die("Eroare la încărcarea stop_times.");

$startIndex = $endIndex = null;
foreach ($stopTimes as $i => $stop) {
    if ($stop['stop_id'] == $startId) $startIndex = $i;
    if ($stop['stop_id'] == $endId) $endIndex = $i;
}
if ($startIndex === null || $endIndex === null || $startIndex >= $endIndex) {
    die("Ordinea stațiilor este invalidă.");
}

$segment = array_slice($stopTimes, $startIndex, $endIndex - $startIndex + 1);

// Coordonate pentru traseul de transport
$coords = [];
foreach ($segment as $stopTime) {
    foreach ($stops as $s) {
        if ($s['stop_id'] == $stopTime['stop_id']) {
            $coords[] = [$s['lat'], $s['lon']];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Traseu UrbanFlow</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        body { margin: 0; }
        #mapid { height: 100vh; width: 100vw; }
    </style>
</head>
<body>
<div id="mapid"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
const map = L.map('mapid').setView([<?= $startLat ?>, <?= $startLng ?>], 13);

// Tile layer
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
}).addTo(map);

// Marcatori
const start = [<?= $startLat ?>, <?= $startLng ?>];
const end = [<?= $endLat ?>, <?= $endLng ?>];
const routeCoords = <?= json_encode($coords) ?>;

L.marker(start).addTo(map).bindPopup("Start: <?= $startStatie['stop_name'] ?>").openPopup();
L.marker(end).addTo(map).bindPopup("Destinație: <?= $endStatie['stop_name'] ?>");

// 🟢 Mers pe jos la început
L.polyline([start, routeCoords[0]], { color: "green" }).addTo(map);

// 🔵 Transport public
L.polyline(routeCoords, { color: "blue" }).addTo(map);

// 🟡 Mers pe jos la final
L.polyline([routeCoords[routeCoords.length - 1], end], { color: "orange" }).addTo(map);
</script>
</body>
</html>
