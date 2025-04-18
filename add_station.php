<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method (POST).']);
    exit;
}

$nume = isset($_POST['nume']) ? trim($_POST['nume']) : '';
$lat  = isset($_POST['lat'])  ? floatval($_POST['lat']) : 0;
$lng  = isset($_POST['lng'])  ? floatval($_POST['lng']) : 0;

if ($nume === '' || ($lat == 0 && $lng == 0)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO stations (nume, lat, lng) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("sdd", $nume, $lat, $lng);
if ($stmt->execute()) {
    $id = $stmt->insert_id;
    echo json_encode([
        'success' => true,
        'station' => [
            'id'   => $id,
            'nume' => $nume,
            'lat'  => $lat,
            'lng'  => $lng
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
