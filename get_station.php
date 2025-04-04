<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method (GET).']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid station id.']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nume, lat, lng FROM stations WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $station = $result->fetch_assoc();
    echo json_encode($station);
} else {
    echo json_encode(['success' => false, 'error' => 'Station not found.']);
}
$stmt->close();
$conn->close();
?>
