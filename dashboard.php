<?php
/************************************************************/
/*                CONFIG + CONEXIUNE DB                     */
/************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 0); // Dezactivează afișarea erorilor în output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

session_start();
// Obține numele orașului din sesiune și setează numele tabelelor personalizate
$oras = '';
$tabela_statii = 'stations';
$tabela_rute   = 'rute';

if (isset($_SESSION['admin_institution']) &&
    preg_match('/Primăria\s+(.*)/i', $_SESSION['admin_institution'], $m)) {
    
    $oras = preg_replace('/[^a-zA-Z0-9]/', '_', $m[1]); // curăță numele
    $tabela_statii = "statii_" . $oras;
    $tabela_rute   = "rute_" . $oras;
}
if (!$oras) {
  die("Eroare: Instituție invalidă. Acces interzis.");
}


// SETARE NUME INSTITUȚIE PENTRU TEST (doar temporar)
if (!isset($_SESSION['admin_institution'])) {
  $_SESSION['admin_institution'] = 'Primăria Pașcani';
}

// Parametrii MySQL
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "urbanflow";  // Baza ta de date

// Conectare
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Eroare la conectarea la BD: " . $conn->connect_error);
}


// Extragere nume oraș din instituție (ex: "Primăria Pașcani" => "Pascani")
$oras = '';
if (isset($_SESSION['admin_institution'])) {
    $pattern = '/Primăria\s+(.*)/i'; // regex pentru "Primăria X"
    if (preg_match($pattern, $_SESSION['admin_institution'], $matches)) {
        $oras = preg_replace('/[^a-zA-Z0-9]/', '_', $matches[1]); // curățare pentru nume tabel
    }
}

if ($oras) {
    $tabela_rute   = "rute_" . $oras;
    $tabela_statii = "statii_" . $oras;

    // Verificăm dacă există tabelul rute_ORAS
    $res1 = $conn->query("SHOW TABLES LIKE '$tabela_rute'");
    if ($res1->num_rows === 0) {
        $conn->query("CREATE TABLE `$tabela_rute` LIKE `rute`");
        $conn->query("INSERT INTO `$tabela_rute` SELECT * FROM `rute`");
    }

    // Verificăm dacă există tabelul statii_ORAS
    $res2 = $conn->query("SHOW TABLES LIKE '$tabela_statii'");
    if ($res2->num_rows === 0) {
        $conn->query("CREATE TABLE `$tabela_statii` LIKE `stations`");
        $conn->query("INSERT INTO `$tabela_statii` SELECT * FROM `stations`");
    }
}

/************************************************************/
/*               LOGICA AJAX (add/edit/delete)              */
/************************************************************/
/************************************************************/
/*               LOGICA AJAX (add/edit/delete)              */
/************************************************************/
if (isset($_GET['ajax'])) {
  header('Content-Type: application/json');

  // Obține numele orașului din instituție
  $oras = '';
  $tabela_statii = 'stations'; // fallback

  if (isset($_SESSION['admin_institution'])) {
      if (preg_match('/Primăria\s+(.*)/i', $_SESSION['admin_institution'], $m)) {
          $oras = preg_replace('/[^a-zA-Z0-9]/', '_', $m[1]);
          $tabela_statii = "statii_" . $oras;
      }
  }

  // Creează tabela dacă nu există (opțional)
  $conn->query("CREATE TABLE IF NOT EXISTS `$tabela_statii` LIKE `stations`");

  $action = $_POST['action'] ?? '';

  // Adăugare stație
  if ($action === 'add_station') {
      $nume = trim($_POST['nume'] ?? '');
      $lat  = floatval($_POST['lat'] ?? 0);
      $lng  = floatval($_POST['lng'] ?? 0);
      $tip_transport = trim($_POST['tip_transport'] ?? '');

      if ($nume === '' || ($lat == 0 && $lng == 0) || $tip_transport === '') {
          echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
          exit;
      }

      $stmt = $conn->prepare("INSERT INTO `$tabela_statii` (nume, lat, lng, tip_transport) VALUES (?, ?, ?, ?)");
      if (!$stmt) {
          echo json_encode(['success' => false, 'error' => $conn->error]);
          exit;
      }
      $stmt->bind_param("sdds", $nume, $lat, $lng, $tip_transport);
      if ($stmt->execute()) {
          $id = $stmt->insert_id;
          echo json_encode([
              'success' => true,
              'station' => compact('id', 'nume', 'lat', 'lng', 'tip_transport')
          ]);
      } else {
          echo json_encode(['success' => false, 'error' => $stmt->error]);
      }
      $stmt->close();
      exit;
  }

  // Editare stație
  if ($action === 'edit_station') {
      $id   = intval($_POST['id'] ?? 0);
      $nume = trim($_POST['nume'] ?? '');
      $lat  = floatval($_POST['lat'] ?? 0);
      $lng  = floatval($_POST['lng'] ?? 0);
      $tip_transport = trim($_POST['tip_transport'] ?? '');

      if ($id <= 0 || $nume === '' || ($lat == 0 && $lng == 0) || $tip_transport === '') {
          echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
          exit;
      }

      $stmt = $conn->prepare("UPDATE `$tabela_statii` SET nume = ?, lat = ?, lng = ?, tip_transport = ? WHERE id = ?");
      if (!$stmt) {
          echo json_encode(['success' => false, 'error' => $conn->error]);
          exit;
      }
      $stmt->bind_param("sddsi", $nume, $lat, $lng, $tip_transport, $id);
      if ($stmt->execute()) {
          echo json_encode([
              'success' => true,
              'station' => compact('id', 'nume', 'lat', 'lng', 'tip_transport')
          ]);
      } else {
          echo json_encode(['success' => false, 'error' => $stmt->error]);
      }
      $stmt->close();
      exit;
  }

  // Ștergere stație
  if ($action === 'delete_station') {
      $id = intval($_POST['id'] ?? 0);
      if ($id <= 0) {
          echo json_encode(['success' => false, 'error' => 'ID stație invalid.']);
          exit;
      }

      $stmt = $conn->prepare("DELETE FROM `$tabela_statii` WHERE id = ?");
      if (!$stmt) {
          echo json_encode(['success' => false, 'error' => $conn->error]);
          exit;
      }
      $stmt->bind_param("i", $id);
      echo json_encode(['success' => $stmt->execute()]);
      $stmt->close();
      exit;
  }

  // Obține stație pentru editare
  if ($action === 'get_station') {
      $id = intval($_POST['id'] ?? 0);
      if ($id <= 0) {
          echo json_encode(['success' => false, 'error' => 'ID stație invalid.']);
          exit;
      }

      $stmt = $conn->prepare("SELECT id, nume, lat, lng, tip_transport FROM `$tabela_statii` WHERE id = ?");
      if (!$stmt) {
          echo json_encode(['success' => false, 'error' => $conn->error]);
          exit;
      }
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows > 0) {
          echo json_encode($result->fetch_assoc());
      } else {
          echo json_encode(['success' => false, 'error' => 'Stația nu a fost găsită.']);
      }
      $stmt->close();
      exit;
  }// Obține rută pentru editare
if ($action === 'get_route') {
  $id = intval($_POST['id'] ?? 0);
  if ($id <= 0) {
      echo json_encode(['success' => false, 'error' => 'ID rută invalid.']);
      exit;
  }

  $stmt = $conn->prepare("SELECT id, nr_transport, id_vehicul, statii FROM `$tabela_rute` WHERE id = ?");
  if (!$stmt) {
      echo json_encode(['success' => false, 'error' => $conn->error]);
      exit;
  }
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
      echo json_encode($result->fetch_assoc());
  } else {
      echo json_encode(['success' => false, 'error' => 'Ruta nu a fost găsită.']);
  }
  $stmt->close();
  exit;
}

// Modificare rută
if ($action === 'edit_route') {
  $id = intval($_POST['id'] ?? 0);
  $nr_transport = trim($_POST['nr_transport'] ?? '');
  $id_vehicul   = trim($_POST['id_vehicul'] ?? '');
  $statii       = trim($_POST['statii'] ?? '');

  if ($id <= 0 || $nr_transport === '' || $id_vehicul === '' || $statii === '') {
      echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt necesare.']);
      exit;
  }

  $stmt = $conn->prepare("UPDATE `$tabela_rute` SET nr_transport = ?, id_vehicul = ?, statii = ? WHERE id = ?");
  if (!$stmt) {
      echo json_encode(['success' => false, 'error' => $conn->error]);
      exit;
  }
  $stmt->bind_param("sssi", $nr_transport, $id_vehicul, $statii, $id);
  if ($stmt->execute()) {
      echo json_encode([
          'success' => true,
          'route' => compact('id', 'nr_transport', 'id_vehicul', 'statii')
      ]);
  } else {
      echo json_encode(['success' => false, 'error' => $stmt->error]);
  }
  $stmt->close();
  exit;
}

// Adăugare rută
if ($action === 'add_route') {
  $nr_transport = trim($_POST['nr_transport'] ?? '');
  $id_vehicul   = trim($_POST['id_vehicul'] ?? '');
  $statii       = trim($_POST['statii'] ?? '');

  if ($nr_transport === '' || $id_vehicul === '' || $statii === '') {
      echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt necesare.']);
      exit;
  }

  $stmt = $conn->prepare("INSERT INTO `$tabela_rute` (nr_transport, id_vehicul, statii) VALUES (?, ?, ?)");
  if (!$stmt) {
      echo json_encode(['success' => false, 'error' => $conn->error]);
      exit;
  }

  $stmt->bind_param("sss", $nr_transport, $id_vehicul, $statii);
  if ($stmt->execute()) {
      $id = $stmt->insert_id;
      echo json_encode([
          'success' => true,
          'route' => compact('id', 'nr_transport', 'id_vehicul', 'statii')
      ]);
  } else {
      echo json_encode(['success' => false, 'error' => $stmt->error]);
  }
  $stmt->close();
  exit;
}


  // Alte acțiuni...
  echo json_encode(['success' => false, 'error' => 'Acțiune necunoscută.']);
  exit;
}


/************************************************************/
/*               AFIȘARE PAGINĂ (fără AJAX)                 */
/************************************************************/
$sqlRoutes = "SELECT * FROM `$tabela_rute`";

$resRoutes = $conn->query($sqlRoutes);
$routes = ($resRoutes && $resRoutes->num_rows > 0) ? $resRoutes->fetch_all(MYSQLI_ASSOC) : [];

$sqlStations = "SELECT * FROM `$tabela_statii`";
$resStations = $conn->query($sqlStations);
$stations = [];
if ($resStations && $resStations->num_rows > 0) {
    while ($s = $resStations->fetch_assoc()) {
        $stations[] = $s;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Single File</title>
  <!-- Stil rapid -->
  <style>
    body {
      margin: 0; padding: 20px; font-family: Arial, sans-serif;
      background: linear-gradient(to right, #e8f5e9, #f1f8e9);
    }
    .logout-button {
      position: absolute; top: 20px; right: 20px;
      background-color: #66bb6a; color: #fff; border: none;
      padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer;
      font-weight: bold; box-shadow: 0 2px 6px rgba(0, 100, 0, 0.2);
    }
    .logout-button:hover { background-color: #558b2f; }
    .section-wrapper {
      max-width: 1100px; margin: 5rem auto; padding: 2rem;
      background: #f2fcf4; border-radius: 20px;
      box-shadow: 0 6px 20px rgba(0, 100, 0, 0.1);
    }
    .section {
      margin-bottom: 3rem; padding: 2rem;
      background: #f9fff9; border: 2px solid #cde3cd;
      border-radius: 16px; box-shadow: 0 4px 12px rgba(0, 100, 0, 0.08);
    }
    .section h2 {
      color: #2e7d32; margin-bottom: 1rem; font-size: 1.8rem;
    }
    #publicMap, #stationMap {
      width: 100%; height: 400px; border-radius: 8px; border: 1px solid #ccc;
    }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    th, td {
      border: 1px solid #cde3cd; padding: 0.8rem; text-align: center;
      background: #f9fff9;
    }
    th { background-color: #2ecc71; color: #fff; }
    button {
      padding: 0.5rem 1rem; border: none; border-radius: 8px; cursor: pointer;
      font-weight: bold; transition: background-color 0.3s ease;
    }
    .add-route-btn {
      background-color: #27ae60; color: #fff; margin-bottom: 1rem;
    }
    .add-route-btn:hover { background-color: #1e8449; }
    .edit-btn { background-color: #3498db; color: #fff; }
    .edit-btn:hover { background-color: #2980b9; }
    .delete-btn { background-color: #e74c3c; color: #fff; }
    .delete-btn:hover { background-color: #c0392b; }
    .confirm-btn {
      background-color: #f39c12; color: #fff; margin-top: 1rem;
    }
    .confirm-btn:hover { background-color: #e67e22; }
    .edit-station-btn { background-color: #3498db; color: #fff; }
    .edit-station-btn:hover { background-color: #2980b9; }
    .delete-station-btn { background-color: #e74c3c; color: #fff; }
    .delete-station-btn:hover { background-color: #c0392b; }

    .institution-badge {
        margin: 1rem 0;
        font-size: 1.1rem;
        font-weight: bold;
        background-color: #c8e6c9;
        padding: 0.6rem 1.2rem;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        color: #2e7d32;
        display: inline-block;
      }
  </style>
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

<!-- Buton logout (opțional) -->
<form action="logout.php" method="POST">
  <button class="logout-button" type="submit">Logout</button>
</form>

<div class="section-wrapper">
<?php if (isset($_SESSION['admin_institution'])): ?>
  <div style="display: flex; justify-content: center; margin-top: 1rem;">
    <div class="institution-badge">
      👤 Instituție: <?= htmlspecialchars($_SESSION['admin_institution']) ?>
    </div>
  </div>
<?php endif; ?>

  <!-- Harta Publică -->
  <div class="section">
    <h2>🗺️ Hartă Transport Public</h2>
    <div id="publicMap"></div>
  </div>

  <!-- Management Rute -->
  <div class="section">
    <h2>🚌 Management Rute</h2>
    <p>Listă de rute. Poți șterge sau modifica (demo).</p>
    <button class="add-route-btn" id="addRouteBtn">Adaugă Rută (demo)</button>
    <form id="addRouteForm" style="display:none; margin-top: 1rem;">
  <label for="routeNr">Nr. Transport:</label>
  <input type="text" id="routeNr" required>

  <label for="vehicleId">ID Vehicul:</label>
  <input type="text" id="vehicleId" required>

  <label for="routeStations">Stații (separate prin virgulă):</label>
  <input type="text" id="routeStations" required>

  <button type="submit" class="confirm-btn">Salvează Ruta</button>
</form>

    <table>
    <thead>
  <tr>
    <th>Nr. Transport</th>
    <th>ID Vehicul</th>
    <th>Stații</th>
    <th>Acțiuni</th>
  </tr>
</thead>
<tbody id="routesTableBody">
  <?php foreach ($routes as $r): ?>
    <tr data-id="<?= $r['id'] ?>">
      <td><?= htmlspecialchars($r['nr_transport']) ?></td>
      <td><?= htmlspecialchars($r['id_vehicul']) ?></td>
      <td><?= htmlspecialchars($r['statii']) ?></td>
      <td><button class="edit-btn" onclick="editRoute(<?= $r['id'] ?>)">Modifică</button>
<button class="delete-btn" onclick="deleteRoute(<?= $r['id'] ?>)">Șterge</button>
<button class="edit-btn" onclick="viewRoute(<?= $r['id'] ?>)">Vizualizează</button>

      </td>
    </tr>
  <?php endforeach; ?>
</tbody>

    </table>
  </div>

  <!-- Management Stații -->
  <div class="section">
    <h2>🚏 Management Stații</h2>
    <p>Adaugă, modifică sau șterge stații. Coordonatele se iau din pin-ul Leaflet (draggable).</p>

    <div id="stationMap"></div>

    <!-- Formular stație -->
    <form id="stationForm" style="margin-top:1rem;">
      <input type="hidden" id="stationId" value="">
      <label for="stationName">Nume Stație:</label>
      <input type="text" id="stationName" required placeholder="Nume stație">
      <label for="stationType">Tip Transport:</label>
      <select id="stationType" required>
        <option value="">--Selectează--</option>
        <option value="autobuz">Autobuz</option>
        <option value="tramvai">Tramvai</option>
      </select>
      <button type="submit" class="confirm-btn">Salvează Stația</button>
    </form>

    <table style="margin-top:1rem;">
      <thead>
        <tr>
          <th>Nume Stație</th>
          <th>Coordonate</th>
          <th>Tip Transport</th>
          <th>Acțiuni</th>
        </tr>
      </thead>
      <tbody id="stationsTableBody">
        <?php if (empty($stations)): ?>
        <tr><td colspan="4">Nu există stații.</td></tr>
        <?php else: ?>
          <?php foreach ($stations as $st): ?>
          <tr data-id="<?= $st['id'] ?>">
            <td class="station-name"><?= htmlspecialchars($st['nume']) ?></td>
            <td class="station-coords"><?= htmlspecialchars($st['lat']) ?>, <?= htmlspecialchars($st['lng']) ?></td>
            <td class="station-type"><?= htmlspecialchars($st['tip_transport']) ?></td>
            <td>
              <button class="edit-station-btn" onclick="editStation(<?= $st['id'] ?>)">Modifică</button>
              <button class="delete-station-btn" onclick="deleteStation(<?= $st['id'] ?>)">Șterge</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>

<script>
  const stations = <?= json_encode($stations) ?>;

// ============= HARTA PUBLICĂ =============
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

// ============= MANAGEMENT Rute ============
document.getElementById('addRouteBtn').addEventListener('click', function() {
  document.getElementById('addRouteForm').style.display = 'block';
});


function editRoute(routeId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("action=get_route&id=" + encodeURIComponent(routeId));

  xhr.onload = function () {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp.id) {
        document.getElementById('routeNr').value = resp.nr_transport;
        document.getElementById('vehicleId').value = resp.id_vehicul;
        document.getElementById('routeStations').value = resp.statii;
        document.getElementById('addRouteForm').style.display = 'block';
        document.getElementById('addRouteForm').setAttribute('data-id', resp.id);
      } else {
        alert("Eroare: " + (resp.error || "Ruta nu a fost găsită."));
      }
    }
  };
}
let currentPolyline = null;

function viewRoute(routeId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("action=get_route&id=" + encodeURIComponent(routeId));

  xhr.onload = function () {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp && resp.statii) {
        const coordList = resp.statii.split(',').map(s => s.trim()).filter(Boolean);
        const coordPairs = [];

        for (let i = 0; i < coordList.length; i++) {
          const stationName = coordList[i];
          const match = stations.find(s => s.nume === stationName);
          if (match) {
            coordPairs.push([parseFloat(match.lat), parseFloat(match.lng)]);
          }
        }

        if (currentPolyline) {
          publicMap.removeLayer(currentPolyline);
        }

        currentPolyline = getRouteOnRoad(coordPairs);

        publicMap.fitBounds(currentPolyline.getBounds());
      } else {
        alert("Ruta nu are stații valide sau nu a fost găsită.");
      }
    }
  };
}


function deleteRoute(routeId) {
  if (!confirm("Sigur ștergi ruta cu ID " + routeId + "?")) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=delete_route&id=" + encodeURIComponent(routeId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Ruta a fost ștearsă!");
          location.reload();
        } else {
          alert("Eroare: " + resp.error);
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare la cererea HTTP pentru ștergere rută.");
    }
  };
}

// =========== MANAGEMENT STAȚII ===========

// 1) Harta + Marker
var stationMap = L.map('stationMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(stationMap);

var stationMarker = L.marker([47.25, 26.75], { draggable: true }).addTo(stationMap);

stationMarker.on('moveend', function(e) {
  console.log("Marker moved to:", e.target.getLatLng());
});

// 2) Submit formular => add/edit stație
document.getElementById('stationForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var stationId = document.getElementById('stationId').value;
  var stationName = document.getElementById('stationName').value.trim();
  var stationType = document.getElementById('stationType').value;

  if (!stationName || !stationType) {
    alert("Te rog introdu numele stației și selectează tipul transportului.");
    return;
  }

  var latLng = stationMarker.getLatLng();
  var lat = latLng.lat.toFixed(6);
  var lng = latLng.lng.toFixed(6);

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  var action = stationId ? "edit_station" : "add_station";
  var params = "action=" + action +
               "&nume=" + encodeURIComponent(stationName) +
               "&lat=" + encodeURIComponent(lat) +
               "&lng=" + encodeURIComponent(lng) +
               "&tip_transport=" + encodeURIComponent(stationType);
  if (stationId) {
    params += "&id=" + encodeURIComponent(stationId);
  }

  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Stația a fost salvată cu succes!");
          if (!stationId) {
            addStationRow(resp.station);
          } else {
            updateStationRow(resp.station);
          }
          document.getElementById('stationForm').reset();
          document.getElementById('stationId').value = "";
          stationMarker.setLatLng([47.25, 26.75]);
          stationMap.setView([47.25, 26.75], 13);
        } else {
          alert("Eroare la salvare: " + resp.error);
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la salvarea stației.");
    }
  };
});

// 3) Funcții add/update row
function addStationRow(station) {
  var tbody = document.getElementById('stationsTableBody');
  var emptyRow = tbody.querySelector('td[colspan="4"]');
  if (emptyRow) {
    tbody.innerHTML = "";
  }
  var tr = document.createElement('tr');
  tr.setAttribute('data-id', station.id);

  var tdName = document.createElement('td');
  tdName.className = 'station-name';
  tdName.textContent = station.nume;

  var tdCoords = document.createElement('td');
  tdCoords.className = 'station-coords';
  tdCoords.textContent = station.lat + ", " + station.lng;

  var tdType = document.createElement('td');
  tdType.className = 'station-type';
  tdType.textContent = station.tip_transport;

  var tdAct = document.createElement('td');
  var btnEdit = document.createElement('button');
  btnEdit.className = 'edit-station-btn';
  btnEdit.textContent = 'Modifică';
  btnEdit.onclick = function() { editStation(station.id); };

  var btnDel = document.createElement('button');
  btnDel.className = 'delete-station-btn';
  btnDel.textContent = 'Șterge';
  btnDel.onclick = function() { deleteStation(station.id); };

  tdAct.appendChild(btnEdit);
  tdAct.appendChild(btnDel);

  tr.appendChild(tdName);
  tr.appendChild(tdCoords);
  tr.appendChild(tdType);
  tr.appendChild(tdAct);
  tbody.appendChild(tr);
}

function updateStationRow(station) {
  var row = document.querySelector('tr[data-id="'+station.id+'"]');
  if (row) {
    row.querySelector('.station-name').textContent = station.nume;
    row.querySelector('.station-coords').textContent = station.lat + ", " + station.lng;
    row.querySelector('.station-type').textContent = station.tip_transport;
  }
}

// 4) Editare stație => get_station
function editStation(stationId) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=get_station&id=" + encodeURIComponent(stationId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var st = JSON.parse(xhr.responseText);
        if (st.id) {
          document.getElementById('stationId').value = st.id;
          document.getElementById('stationName').value = st.nume;
          document.getElementById('stationType').value = st.tip_transport;
          stationMarker.setLatLng([parseFloat(st.lat), parseFloat(st.lng)]);
          stationMap.setView([parseFloat(st.lat), parseFloat(st.lng)], 13);
          stationMap.invalidateSize();
        } else {
          alert("Eroare: Stația nu a fost găsită.");
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la get_station.");
    }
  };
}

// 5) Ștergere stație
function deleteStation(stationId) {
  if (!confirm("Sigur dorești să ștergi stația ID " + stationId + "?")) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=delete_station&id=" + encodeURIComponent(stationId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Stația a fost ștearsă!");
          var row = document.querySelector('tr[data-id="'+stationId+'"]');
          if (row) row.remove();
        } else {
          alert("Eroare la ștergere: " + resp.error);
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la ștergere stație.");
    }
  };
}document.getElementById('addRouteBtn').addEventListener('click', function() {
  document.getElementById('addRouteForm').style.display = 'block';
});
document.getElementById('addRouteForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const nr = document.getElementById('routeNr').value.trim();
  const vehicul = document.getElementById('vehicleId').value.trim();
  const statii = document.getElementById('routeStations').value.trim();

  if (!nr || !vehicul || !statii) {
    alert("Completează toate câmpurile!");
    return;
  }

  const xhr = new XMLHttpRequest();
xhr.open("POST", "?ajax=1", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

const routeId = document.getElementById('addRouteForm').getAttribute('data-id');
const action = routeId ? 'edit_route' : 'add_route';

let params = "action=" + action +
             "&nr_transport=" + encodeURIComponent(nr) +
             "&id_vehicul=" + encodeURIComponent(vehicul) +
             "&statii=" + encodeURIComponent(statii);

if (routeId) {
  params += "&id=" + encodeURIComponent(routeId);
}


  xhr.onload = function() {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp.success) {
        alert("Rută salvată!");
        addRouteRow(resp.route);
        document.getElementById('addRouteForm').reset();
        document.getElementById('addRouteForm').style.display = 'none';
      } else {
        alert("Eroare: " + resp.error);
      }if (routeId) {
  alert("Rută actualizată!");
  location.reload();
}

    }
  };

  xhr.send(params);
});

function getRouteOnRoad(coordPairs) {
  const url = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';

  const body = {
    coordinates: coordPairs.map(pair => [pair[1], pair[0]]) // lng, lat
  };

  fetch(url, {
    method: 'POST',
    headers: {
      'Authorization': '5b3ce3597851110001cf6248d3f47cc712ed42bdbc3b848f8854acc6',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  })
  .then(res => res.json())
  .then(data => {
    if (currentPolyline) publicMap.removeLayer(currentPolyline);
    currentPolyline = L.geoJSON(data, {
      style: { color: 'blue', weight: 4 }
    }).addTo(publicMap);
    publicMap.fitBounds(currentPolyline.getBounds());
  });
}

function addRouteRow(route) {
  const tbody = document.getElementById('routesTableBody');
  const tr = document.createElement('tr');

  tr.setAttribute('data-id', route.id);
  tr.innerHTML = `
    <td>${route.nr_transport}</td>
    <td>${route.id_vehicul}</td>
    <td>${route.statii}</td>
    <td>
      <button class="edit-btn" onclick="editRoute(${route.id})">Modifică</button>
      <button class="delete-btn" onclick="deleteRoute(${route.id})">Șterge</button>
      <button class="edit-btn" onclick="viewRoute(${route.id})">Vizualizează</button>
    </td>
  `;

  tbody.appendChild(tr);
}


document.getElementById('addRouteForm').reset();
document.getElementById('addRouteForm').style.display = 'none';
document.getElementById('addRouteForm').removeAttribute('data-id');

</script>
</body>
</html>
