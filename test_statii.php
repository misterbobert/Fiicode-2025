<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db.php';

// Creăm tabela stations dacă nu există (o singură dată e suficient)
$sqlCreate = "CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    lat DOUBLE NOT NULL,
    lng DOUBLE NOT NULL
)";
$conn->query($sqlCreate);

// === ADĂUGARE STAȚIE === (fără AJAX, doar form POST)
if (isset($_POST['submit_station'])) {
    $nume = isset($_POST['nume']) ? trim($_POST['nume']) : '';
    $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
    $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;

    if (!empty($nume) && $lat != 0 && $lng != 0) {
        $stmt = $conn->prepare("INSERT INTO stations (nume, lat, lng) VALUES (?, ?, ?)");
        $stmt->bind_param("sdd", $nume, $lat, $lng);
        $stmt->execute();
        $stmt->close();
    }
}

// === ȘTERGERE STAȚIE ===
if (isset($_GET['del'])) {
    $id_del = intval($_GET['del']);
    if ($id_del > 0) {
        $stmt = $conn->prepare("DELETE FROM stations WHERE id = ?");
        $stmt->bind_param("i", $id_del);
        $stmt->execute();
        $stmt->close();
    }
}

// Afișăm stațiile curente
$result = $conn->query("SELECT * FROM stations ORDER BY id DESC");
$stations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $stations[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <title>Test Stații Leaflet</title>

    <!-- CSS Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0; 
            padding: 20px;
        }
        #mapContainer {
            width: 800px; 
            height: 400px;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #aaa;
            padding: 8px 12px;
        }
        th {
            background-color: #eee;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>

<h1>Test - Stații cu Leaflet (Pin draggable)</h1>

<!-- FORMULAR ADĂUGARE STAȚIE (nu afisăm lat/lng - le ținem ascunse) -->
<form method="POST">
    <label>Nume Stație:</label><br>
    <input type="text" name="nume" required placeholder="Introdu numele stației"><br><br>

    <!-- Câmpuri ascunse pentru lat/lng, actualizate de Leaflet JS -->
    <input type="hidden" id="latField" name="lat" value="0">
    <input type="hidden" id="lngField" name="lng" value="0">

    <button type="submit" name="submit_station">Salvează Stația</button>
</form>

<!-- HARTA -->
<div id="mapContainer"></div>

<!-- LISTĂ STAȚII DIN DB -->
<h2>Lista Stații Existente</h2>
<?php if (empty($stations)): ?>
    <p>Nu există stații încă.</p>
<?php else: ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Nume</th>
            <th>Coordonate</th>
            <th>Acțiune</th>
        </tr>
        <?php foreach ($stations as $st): ?>
        <tr>
            <td><?php echo $st['id']; ?></td>
            <td><?php echo htmlspecialchars($st['nume']); ?></td>
            <td><?php echo $st['lat'] . ", " . $st['lng']; ?></td>
            <td>
                <a href="?del=<?php echo $st['id']; ?>" onclick="return confirm('Sigur ștergi stația?');">Șterge</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

<!-- Script Leaflet -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// 1) Inițializare hartă
var map = L.map('mapContainer').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(map);

// 2) Pin draggable
var marker = L.marker([47.25, 26.75], {draggable: true}).addTo(map);

// 3) Când se mișcă pinul, actualizăm câmpurile ascunse
marker.on('moveend', function(e) {
    var lat = e.target.getLatLng().lat.toFixed(6);
    var lng = e.target.getLatLng().lng.toFixed(6);
    document.getElementById("latField").value = lat;
    document.getElementById("lngField").value = lng;
    console.log("Marker moved to:", lat, lng);
});
</script>

</body>
</html>
