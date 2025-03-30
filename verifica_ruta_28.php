<?php
$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;
$routeId = 28; // ID-ul trebuie verificat, dar presupunem că 28 e route_short_name
$statieStartId = 8;
$statieFinalaId = 44;

function apiGET($url, $headers) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

$headers = [
    "Accept: application/json",
    "X-API-KEY: $apiKey",
    "X-Agency-Id: $agencyId"
];

// Obținem trips pentru ruta 28
$trips = apiGET("https://api.tranzy.ai/v1/opendata/trips?route_id=$routeId", $headers);
if (empty($trips)) {
    die("Nu există trips pentru ruta $routeId.");
}

$tripId = $trips[0]['trip_id'];

// Obținem stop_times pentru trip
$stopTimes = apiGET("https://api.tranzy.ai/v1/opendata/stop_times?trip_id=$tripId", $headers);
if (empty($stopTimes)) {
    die("Nu există stop_times pentru trip $tripId.");
}

$stopIds = array_column($stopTimes, 'stop_id');

$pozStart = array_search($statieStartId, $stopIds);
$pozFinal = array_search($statieFinalaId, $stopIds);

$result = [
    'route_id' => $routeId,
    'trip_id' => $tripId,
    'statie_start_gasita' => $pozStart !== false,
    'statie_finala_gasita' => $pozFinal !== false,
    'in_ordine_corecta' => $pozStart !== false && $pozFinal !== false && $pozStart < $pozFinal,
    'pozitie_start' => $pozStart,
    'pozitie_finala' => $pozFinal
];

header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT);
