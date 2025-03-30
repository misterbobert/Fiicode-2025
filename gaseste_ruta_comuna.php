<?php
ob_start(); // permite flush în browser

$apiKey = 'f5CfxGaxn8cC3jQYK8tgPnGvSaRnTdsZyGJV4HxE';
$agencyId = 1;
$statieStartId = 20;  // Gara
$statieFinalaId = 99; // Casa Sindicatelor



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

// pregătim afișare HTML
header('Content-Type: text/html');
echo "<pre>";

$rute = apiGET("https://api.tranzy.ai/v1/opendata/routes", $headers);
$rute = array_slice($rute, 0, 3); // doar 3 rute pentru test
$ruteValide = [];

foreach ($rute as $ruta) {
    $routeId = $ruta['route_id'];
    $nume = $ruta['route_short_name'] ?? $ruta['route_long_name'] ?? "Ruta $routeId";

    echo "🔄 Verific ruta $nume (ID $routeId)...\n";
    flush(); ob_flush();

    $trips = apiGET("https://api.tranzy.ai/v1/opendata/trips?route_id=$routeId", $headers);
    if (empty($trips)) {
        echo "⛔️ Nicio cursă pentru această rută.\n";
        continue;
    }

    foreach (array_slice($trips, 0, 2) as $trip) {
        $tripId = $trip['trip_id'];
        echo "   ➤ Verific trip ID $tripId...\n";
        flush(); ob_flush();

        $stopTimes = apiGET("https://api.tranzy.ai/v1/opendata/stop_times?trip_id=$tripId", $headers);
        if (empty($stopTimes)) continue;

        $stopIds = array_column($stopTimes, 'stop_id');
        $pozStart = array_search($statieStartId, $stopIds);
        $pozFinal = array_search($statieFinalaId, $stopIds);

        if ($pozStart !== false && $pozFinal !== false && $pozStart < $pozFinal) {
            echo "✅ Rută validă găsită: $nume ($routeId), trip $tripId\n";
            $ruteValide[] = [
                'route_id' => $routeId,
                'trip_id' => $tripId,
                'nume' => $nume,
                'pozitie_start' => $pozStart,
                'pozitie_finala' => $pozFinal
            ];
            break;
        }
    }
}

echo "\n=== RUTE GĂSITE ===\n";
print_r($ruteValide);
echo "</pre>";
