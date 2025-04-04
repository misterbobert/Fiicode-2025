<?php
/************************************************************/
/*                CONFIG + CONEXIUNE DB                     */
/************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 0); // Dezactivează afișarea erorilor în output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

session_start();

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

/************************************************************/
/*               LOGICA AJAX (add/edit/delete)              */
/************************************************************/
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Preia acțiunea
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // 1) Adăugare stație
    if ($action === 'add_station') {
        $nume = trim($_POST['nume'] ?? '');
        $lat  = floatval($_POST['lat'] ?? 0);
        $lng  = floatval($_POST['lng'] ?? 0);
        $tip_transport = trim($_POST['tip_transport'] ?? '');

        if ($nume === '' || ($lat == 0 && $lng == 0) || $tip_transport === '') {
            echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO stations (nume, lat, lng, tip_transport) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sdds", $nume, $lat, $lng, $tip_transport);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode([
                'success' => true,
                'station' => [
                    'id' => $id,
                    'nume' => $nume,
                    'lat'  => $lat,
                    'lng'  => $lng,
                    'tip_transport' => $tip_transport
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // 2) Editare stație
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
        $stmt = $conn->prepare("UPDATE stations SET nume = ?, lat = ?, lng = ?, tip_transport = ? WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sddsi", $nume, $lat, $lng, $tip_transport, $id);
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'station' => [
                    'id'   => $id,
                    'nume' => $nume,
                    'lat'  => $lat,
                    'lng'  => $lng,
                    'tip_transport' => $tip_transport
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // 3) Ștergere stație
    if ($action === 'delete_station') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID stație invalid.']);
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
        exit;
    }

    // 4) Obține stația pentru editare
    if ($action === 'get_station') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID stație invalid.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT id, nume, lat, lng, tip_transport FROM stations WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $st = $result->fetch_assoc();
            echo json_encode($st);
        } else {
            echo json_encode(['success' => false, 'error' => 'Stația nu a fost găsită.']);
        }
        $stmt->close();
        exit;
    }

    // 5) Ștergere rută (demonstrativ)
    if ($action === 'delete_route') {
        $routeId = intval($_POST['id'] ?? 0);
        if ($routeId <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID rută invalid.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM rute WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $routeId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    // Acțiune necunoscută:
    echo json_encode(['success' => false, 'error' => 'Acțiune necunoscută.']);
    exit;
}

/************************************************************/
/*               AFIȘARE PAGINĂ (fără AJAX)                 */
/************************************************************/
// (Opțional) Verificare sesiune admin, dacă este necesar
// if (!isset($_SESSION['admin'])) {
//    header("Location: admin.php");
//    exit;
// }

// Obține rutele
$sqlRoutes = "SELECT * FROM rute";
$resRoutes = $conn->query($sqlRoutes);
$routes = ($resRoutes && $resRoutes->num_rows > 0) ? $resRoutes->fetch_all(MYSQLI_ASSOC) : [];

// Obține stațiile
$sqlStations = "SELECT * FROM stations";
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

    <table>
      <thead>
        <tr>
          <th>Nr. Transport</th>
          <th>Stații</th>
          <th>Acțiuni</th>
        </tr>
      </thead>
      <tbody id="routesTableBody">
        <?php if (empty($routes)): ?>
        <tr><td colspan="3">Nu există rute.</td></tr>
        <?php else: ?>
          <?php foreach ($routes as $r): ?>
          <tr data-id="<?= $r['id'] ?>">
            <td><?= htmlspecialchars($r['nr_transport']) ?></td>
            <td><?= htmlspecialchars($r['statii']) ?></td>
            <td>
              <button class="edit-btn" onclick="editRoute(<?= $r['id'] ?>)">Modifică</button>
              <button class="delete-btn" onclick="deleteRoute(<?= $r['id'] ?>)">Șterge</button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
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
// ============= HARTA PUBLICĂ =============
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

// ============= MANAGEMENT Rute ============
document.getElementById('addRouteBtn').addEventListener('click', function() {
  alert("Funcția de adăugare rută nu e implementată încă.");
});

function editRoute(routeId) {
  alert("Funcția de modificare rută nu e implementată. ID = " + routeId);
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
}
</script>
</body>
</html>
