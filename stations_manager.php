<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// Determină acțiunea
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

/*************************************************************
 * ADD STATION
 *************************************************************/
if ($action === 'add_station' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume = trim($_POST['nume'] ?? '');
    $lat  = floatval($_POST['lat'] ?? 0);
    $lng  = floatval($_POST['lng'] ?? 0);

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
    exit;
}

/*************************************************************
 * EDIT STATION
 *************************************************************/
if ($action === 'edit_station' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = intval($_POST['id'] ?? 0);
    $nume = trim($_POST['nume'] ?? '');
    $lat  = floatval($_POST['lat'] ?? 0);
    $lng  = floatval($_POST['lng'] ?? 0);

    if ($id <= 0 || $nume === '' || ($lat == 0 && $lng == 0)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE stations SET nume = ?, lat = ?, lng = ? WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    $stmt->bind_param("sddi", $nume, $lat, $lng, $id);
    if ($stmt->execute()) {
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
    exit;
}

/*************************************************************
 * DELETE STATION
 *************************************************************/
if ($action === 'delete_station' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid station ID.']);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM stations WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    $stmt->close();
    $conn->close();
    exit;
}

/*************************************************************
 * GET STATION
 *************************************************************/
if ($action === 'get_station' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid station ID.']);
        exit;
    }
    $stmt = $conn->prepare("SELECT id, nume, lat, lng FROM stations WHERE id = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $st = $res->fetch_assoc();
        echo json_encode($st);
    } else {
        echo json_encode(['success' => false, 'error' => 'Station not found.']);
    }
    $stmt->close();
    $conn->close();
    exit;
}

// Dacă nu s-a potrivit nicio acțiune
echo json_encode(['success' => false, 'error' => 'Unknown or invalid action/method.']);
$conn->close();
exit;
