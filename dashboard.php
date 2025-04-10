<?php
/************************************************************/
/*                SECȚIUNE DE INSTALARE                    */
/************************************************************/
$servername = "localhost";
$usernameDB = "root";
$passwordDB = "";
$dbname     = "urbanflow";

// Conectare la serverul MySQL (fără specificarea bazei de date)
$connInstall = new mysqli($servername, $usernameDB, $passwordDB);
if ($connInstall->connect_error) {
    die("Eroare la conectarea la server: " . $connInstall->connect_error);
}

// Creează baza de date dacă nu există
$sqlCreateDB = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$connInstall->query($sqlCreateDB)) {
    die("Eroare la crearea bazei de date: " . $connInstall->error);
}
$connInstall->select_db($dbname);

// Creează tabela admin_users (dacă nu există)
$sqlCreateAdmin = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    institution VARCHAR(255),
    oras VARCHAR(255),
    judet VARCHAR(255),
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
if (!$connInstall->query($sqlCreateAdmin)) {
    die("Eroare la crearea tabelului admin_users: " . $connInstall->error);
}

// Creează tabela stations (sursă pentru stații)
$sqlCreateStations = "CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(255) NOT NULL,
    lat DOUBLE NOT NULL,
    lng DOUBLE NOT NULL,
    tip_transport VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
if (!$connInstall->query($sqlCreateStations)) {
    die("Eroare la crearea tabelului stations: " . $connInstall->error);
}

// Creează tabela rute (sursă pentru rute)
$sqlCreateRute = "CREATE TABLE IF NOT EXISTS rute (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nr_transport VARCHAR(50) NOT NULL,
    id_vehicul VARCHAR(50) NOT NULL,
    statii TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB";
if (!$connInstall->query($sqlCreateRute)) {
    die("Eroare la crearea tabelului rute: " . $connInstall->error);
}

$connInstall->close();

/************************************************************/
/*                CONFIG + AFIȘARE ERORI                    */
/************************************************************/
error_reporting(E_ALL);
ini_set('display_errors', 1); // Activează afișarea erorilor (dezactivează pe producție)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

session_start();
// Pentru testare, dacă nu există sesiune, setăm manual
if (!isset($_SESSION['admin_email'])) {
    $_SESSION['admin_email'] = 'admin@example.com';
    $_SESSION['admin_institution'] = 'Primăria Pașcani';
}

/************************************************************/
/*             CONECTARE LA BAZA DE DATE                    */
/************************************************************/
$conn = new mysqli($servername, $usernameDB, $passwordDB, $dbname);
if ($conn->connect_error) {
    die("Eroare la conectarea la BD: " . $conn->connect_error);
}

/************************************************************/
/*         DETERMINĂM ORAȘUL UTILIZATORULUI DIN BD           */
/************************************************************/
$email = $_SESSION['admin_email'];
$stmt = $conn->prepare("SELECT oras FROM admin_users WHERE email = ?");
if (!$stmt) {
    error_log("Eroare pregătire interogare oras: " . $conn->error);
    die("Eroare la interogarea orașului.");
}
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    die("Eroare: Orașul nu a fost găsit (sau utilizatorul nu există).");
}
$row = $res->fetch_assoc();
$stmt->close();
if (!isset($row['oras']) || trim($row['oras']) === '') {
    die("Eroare: Orașul nu este configurat pentru acest utilizator.");
}
$oras = preg_replace('/[^a-zA-Z0-9]/', '_', $row['oras']);
error_log("Oras extras din BD: " . $oras);

// Numele tabelelor derivate
$tabela_rute   = "rute_" . $oras;
$tabela_statii = "statii_" . $oras;
error_log("Tabel folosit pentru stații: " . $tabela_statii);
error_log("Tabel folosit pentru rute: " . $tabela_rute);

/************************************************************/
/*          CREĂM TABELLELE pentru rute și stații           */
/************************************************************/
$res1 = $conn->query("SHOW TABLES LIKE '$tabela_rute'");
if ($res1 === false) {
    die("Eroare la SHOW TABLES pentru '$tabela_rute': " . $conn->error);
}
if ($res1->num_rows === 0) {
    if (!$conn->query("CREATE TABLE `$tabela_rute` LIKE `rute`")) {
        die("Eroare la crearea tabelului `$tabela_rute`: " . $conn->error);
    }
    if (!$conn->query("INSERT INTO `$tabela_rute` SELECT * FROM `rute`")) {
        die("Eroare la copierea datelor în `$tabela_rute`: " . $conn->error);
    }
}
$res2 = $conn->query("SHOW TABLES LIKE '$tabela_statii'");
if ($res2 === false) {
    die("Eroare la SHOW TABLES pentru '$tabela_statii': " . $conn->error);
}
if ($res2->num_rows === 0) {
    if (!$conn->query("CREATE TABLE `$tabela_statii` LIKE `stations`")) {
        die("Eroare la crearea tabelului `$tabela_statii`: " . $conn->error);
    }
    if (!$conn->query("INSERT INTO `$tabela_statii` SELECT * FROM `stations`")) {
        die("Eroare la copierea datelor în `$tabela_statii`: " . $conn->error);
    }
}

/************************************************************/
/*              LOGICA AJAX: ADD/EDIT/DELETE                */
/************************************************************/
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    error_log("AJAX POST data: " . print_r($_POST, true));
    $action = $_POST['action'] ?? '';
    error_log("Parametrul action primit: " . $action);

    // --- STAȚII ---
    if ($action === 'add_station') {
        $nume = trim($_POST['nume'] ?? '');
        $lat  = floatval($_POST['lat'] ?? 45.75);
        $lng  = floatval($_POST['lng'] ?? 27.95);
        $tip_transport = trim($_POST['tip_transport'] ?? '');
        if ($nume === '' || $tip_transport === '') {
            error_log("add_station validare eșuată. Nume: '$nume', tip: '$tip_transport'");
            echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO `$tabela_statii` (nume, lat, lng, tip_transport) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("add_station: eroare pregătire stmt: " . $conn->error);
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sdds", $nume, $lat, $lng, $tip_transport);
        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            echo json_encode(['success' => true, 'station' => compact('id', 'nume', 'lat', 'lng', 'tip_transport')]);
        } else {
            error_log("add_station: execuție eșuată: " . $stmt->error);
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    if ($action === 'edit_station') {
        $id = intval($_POST['id'] ?? 0);
        $nume = trim($_POST['nume'] ?? '');
        $lat  = floatval($_POST['lat'] ?? 45.75);
        $lng  = floatval($_POST['lng'] ?? 27.95);
        $tip_transport = trim($_POST['tip_transport'] ?? '');
        if ($id <= 0 || $nume === '' || $tip_transport === '') {
            error_log("edit_station validare eșuată. ID: '$id', Nume: '$nume', tip: '$tip_transport'");
            echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE `$tabela_statii` SET nume = ?, lat = ?, lng = ?, tip_transport = ? WHERE id = ?");
        if (!$stmt) {
            error_log("edit_station: eroare pregătire stmt: " . $conn->error);
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sddsi", $nume, $lat, $lng, $tip_transport, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'station' => compact('id', 'nume', 'lat', 'lng', 'tip_transport')]);
        } else {
            error_log("edit_station: execuție eșuată: " . $stmt->error);
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
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
        $execResult = $stmt->execute();
        echo json_encode(['success' => $execResult]);
        $stmt->close();
        exit;
    }
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
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'station' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Stația nu a fost găsită.']);
        }
        $stmt->close();
        exit;
    }
    // --- RUTE ---
    if ($action === 'add_route') {
        $nr_transport = trim($_POST['nr_transport'] ?? '');
        $id_vehicul = trim($_POST['id_vehicul'] ?? '');
        $statii = trim($_POST['statii'] ?? '');  // Lista de stații, separate prin virgulă (ID-uri)
        if ($nr_transport === '' || $id_vehicul === '' || $statii === '') {
            echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
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
            echo json_encode(['success' => true, 'route' => compact('id', 'nr_transport', 'id_vehicul', 'statii')]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    if ($action === 'edit_route') {
        $id = intval($_POST['id'] ?? 0);
        $nr_transport = trim($_POST['nr_transport'] ?? '');
        $id_vehicul = trim($_POST['id_vehicul'] ?? '');
        $statii = trim($_POST['statii'] ?? '');
        if ($id <= 0 || $nr_transport === '' || $id_vehicul === '' || $statii === '') {
            echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE `$tabela_rute` SET nr_transport = ?, id_vehicul = ?, statii = ? WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sssi", $nr_transport, $id_vehicul, $statii, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'route' => compact('id', 'nr_transport', 'id_vehicul', 'statii')]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
        exit;
    }
    if ($action === 'delete_route') {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'error' => 'ID rută invalid.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM `$tabela_rute` WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $id);
        $execResult = $stmt->execute();
        echo json_encode(['success' => $execResult]);
        $stmt->close();
        exit;
    }
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
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'route' => $row]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Rută nu a fost găsită.']);
        }
        $stmt->close();
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'Acțiune necunoscută.']);
    exit;
}

/************************************************************/
/*            AFIȘARE PAGINĂ (FĂRĂ AJAX, HTML complet)       */
/************************************************************/
$sqlRoutes = "SELECT * FROM `$tabela_rute`";
$resRoutes = $conn->query($sqlRoutes);
if ($resRoutes === false) {
    die("Eroare la SELECT din `$tabela_rute`: " . $conn->error);
}
$routes = ($resRoutes->num_rows > 0) ? $resRoutes->fetch_all(MYSQLI_ASSOC) : [];

$sqlStations = "SELECT * FROM `$tabela_statii`";
$resStations = $conn->query($sqlStations);
if ($resStations === false) {
    die("Eroare la SELECT din `$tabela_statii`: " . $conn->error);
}
$stations = [];
if ($resStations->num_rows > 0) {
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
  <!-- Includem CSS și JS pentru Leaflet -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="dashboard.css">
  <style>
    /* Stil pentru hărțile locale și formulare */
    #publicMap, #stationMap { height: 400px; }
    #routeForm, #stationForm { margin-top: 1rem; }
  </style>
</head>
<body>
  <!-- Buton Logout -->
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
      <h2>🗺️ Harta Transport Public</h2>
      <div id="publicMap"></div>
    </div>
    
    <!-- Management Rute -->
    <div class="section">
      <h2>🚌 Management Rute</h2>
      <p>Listă de rute. Poți șterge, modifica sau vizualiza rutele (pe harta publică).</p>
      <button class="add-route-btn" id="showRouteFormBtn">Adaugă Rută (demo)</button>
      <!-- Formularul pentru rute -->
      <form id="routeForm" style="display:none; margin-top: 1rem;">
        <input type="hidden" id="routeId" value="">
        <label for="routeNr">Nr. Transport:</label>
        <input type="text" id="routeNr" required>
        <label for="vehicleId">ID Vehicul:</label>
        <input type="text" id="vehicleId" required>
        <label for="routeStations">Stații:</label>
        <!-- Selectorul multiselect cu 5 rânduri vizibile -->
        <select id="routeStations" multiple size="5" required>
          <?php foreach ($stations as $st): ?>
            <option value="<?= htmlspecialchars($st['id']) ?>"><?= htmlspecialchars($st['nume']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="confirm-btn">Salvează Rută</button>
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
              <td>
                <button class="edit-btn" onclick="editRoute(<?= $r['id'] ?>)">Modifică</button>
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
      <p>Adaugă, modifică sau șterge stații. Modifică locația prin tragerea marker-ului pe harta de stații.</p>
      <div id="stationMap"></div>
      <!-- Formularul pentru stații -->
      <form id="stationForm" style="margin-top: 1rem;">
        <input type="hidden" id="stationId" value="">
        <label for="stationName">Nume Stație:</label>
        <input type="text" id="stationName" required placeholder="Nume stație">
        <!-- Câmpuri ascunse pentru coordonate -->
        <input type="hidden" id="stationLat" value="">
        <input type="hidden" id="stationLng" value="">
        <label for="stationType">Tip Transport:</label>
        <select id="stationType" required>
          <option value="">--Selectează--</option>
          <option value="autobuz">Autobuz</option>
          <option value="tramvai">Tramvai</option>
        </select>
        <button type="submit" class="confirm-btn">Salvează Stația</button>
      </form>
      
      <table style="margin-top: 1rem;">
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
  
  <!-- JavaScript -->
  <script>
    // Variabila globală cu toate stațiile (din baza de date)
    const stations = <?= json_encode($stations, JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    // -------------------------------
    // Harta Publică – pentru vizualizarea rutelor
    // -------------------------------
    let publicMap = L.map('publicMap').setView([45.75, 27.95], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(publicMap);
    // LayerGroup pentru a afișa ruta (marker-e și polyline)
    let routeLayerGroup = L.layerGroup().addTo(publicMap);

    function viewRoute(id) {
      const formData = new URLSearchParams();
      formData.append('action', 'get_route');
      formData.append('id', id);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns get_route:", data);
        if (data.success && data.route) {
          let stationIds = data.route.statii.split(",");
          let routeStations = [];
          stationIds.forEach(idStr => {
            let st = stations.find(s => parseInt(s.id) === parseInt(idStr));
            if (st) {
              routeStations.push(st);
            }
          });
          // Golim stratul de rută existent
          routeLayerGroup.clearLayers();
          if (routeStations.length > 0) {
            publicMap.setView([routeStations[0].lat, routeStations[0].lng], 11);
          }
          let latlngs = [];
          routeStations.forEach(st => {
            let marker = L.marker([st.lat, st.lng]).addTo(routeLayerGroup);
            marker.bindPopup(st.nume);
            latlngs.push([st.lat, st.lng]);
          });
          if (latlngs.length > 1) {
            let polyline = L.polyline(latlngs, {color: 'blue'});
            routeLayerGroup.addLayer(polyline);
          }
        } else {
          alert("Eroare la vizualizarea rutei: " + data.error);
        }
      })
      .catch(error => console.error("Eroare la viewRoute:", error));
    }

    // -------------------------------
    // Harta Stațiilor – pentru managementul stațiilor
    // -------------------------------
    let stationMap = L.map('stationMap').setView([45.75, 27.95], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(stationMap);
    let stationMarker = L.marker([45.75, 27.95], {draggable: true}).addTo(stationMap);
    stationMarker.on('dragend', function() {
      let pos = stationMarker.getLatLng();
      document.getElementById('stationLat').value = pos.lat;
      document.getElementById('stationLng').value = pos.lng;
    });
    function setMarkerPosition(lat, lng) {
      stationMarker.setLatLng([lat, lng]);
      stationMap.setView([lat, lng], 11);
      document.getElementById('stationLat').value = lat;
      document.getElementById('stationLng').value = lng;
    }

    // -----------------------
    // Management Stații – funcții
    // -----------------------
    function deleteStation(id) {
      if (!confirm("Sigur dorești să ștergi această stație?")) return;
      const formData = new URLSearchParams();
      formData.append('action', 'delete_station');
      formData.append('id', id);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns delete_station:", data);
        if (data.success) {
          document.querySelector(`tr[data-id="${id}"]`).remove();
        } else {
          alert("Eroare la delete_station: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch delete_station:', error));
    }
    
    function editStation(id) {
      const formData = new URLSearchParams();
      formData.append('action', 'get_station');
      formData.append('id', id);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns get_station:", data);
        if (data.success && data.station) {
          document.getElementById('stationId').value = data.station.id;
          document.getElementById('stationName').value = data.station.nume;
          document.getElementById('stationType').value = data.station.tip_transport;
          let lat = parseFloat(data.station.lat) || 45.75;
          let lng = parseFloat(data.station.lng) || 27.95;
          setMarkerPosition(lat, lng);
          document.getElementById('stationForm').style.display = 'block';
        } else {
          alert("Eroare la get_station: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch get_station:', error));
    }
    
    function saveStationEdits() {
      const id = document.getElementById('stationId').value;
      const nume = document.getElementById('stationName').value;
      const lat = document.getElementById('stationLat').value;
      const lng = document.getElementById('stationLng').value;
      const tip_transport = document.getElementById('stationType').value;
      const formData = new URLSearchParams();
      formData.append('action', 'edit_station');
      formData.append('id', id);
      formData.append('nume', nume);
      formData.append('lat', lat);
      formData.append('lng', lng);
      formData.append('tip_transport', tip_transport);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns edit_station:", data);
        if (data.success) {
          alert("Modificări salvate cu succes!");
        } else {
          alert("Eroare la edit_station: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch edit_station:', error));
    }
    
    function addNewStation() {
      const nume = document.getElementById('stationName').value;
      const lat = document.getElementById('stationLat').value || 45.75;
      const lng = document.getElementById('stationLng').value || 27.95;
      const tip_transport = document.getElementById('stationType').value;
      const formData = new URLSearchParams();
      formData.append('action', 'add_station');
      formData.append('nume', nume);
      formData.append('lat', lat);
      formData.append('lng', lng);
      formData.append('tip_transport', tip_transport);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns add_station:", data);
        if (data.success) {
          alert("Stație adăugată cu succes!");
        } else {
          alert("Eroare la add_station: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch add_station:', error));
    }
    
    document.getElementById('stationForm').addEventListener('submit', function(e) {
      e.preventDefault();
      if (document.getElementById('stationId').value.trim() === "") {
        addNewStation();
      } else {
        saveStationEdits();
      }
    });

    // ---------------------
    // Management Rute – funcții
    // ---------------------
    document.getElementById('showRouteFormBtn').addEventListener('click', function() {
      document.getElementById('routeForm').style.display = 'block';
      document.getElementById('routeId').value = "";
      document.getElementById('routeNr').value = "";
      document.getElementById('vehicleId').value = "";
      const sel = document.getElementById('routeStations');
      for (let i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = false;
      }
    });
    
    function addRoute() {
      const nr_transport = document.getElementById('routeNr').value;
      const id_vehicul = document.getElementById('vehicleId').value;
      const sel = document.getElementById('routeStations');
      let selectedStations = [];
      for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].selected) {
          selectedStations.push(sel.options[i].value);
        }
      }
      const statii = selectedStations.join(",");
      const formData = new URLSearchParams();
      formData.append('action', 'add_route');
      formData.append('nr_transport', nr_transport);
      formData.append('id_vehicul', id_vehicul);
      formData.append('statii', statii);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns add_route:", data);
        if (data.success) {
          alert("Rută adăugată cu succes!");
        } else {
          alert("Eroare la add_route: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch add_route:', error));
    }
    
    function saveRouteEdits() {
      const id = document.getElementById('routeId').value;
      const nr_transport = document.getElementById('routeNr').value;
      const id_vehicul = document.getElementById('vehicleId').value;
      const sel = document.getElementById('routeStations');
      let selectedStations = [];
      for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].selected) {
          selectedStations.push(sel.options[i].value);
        }
      }
      const statii = selectedStations.join(",");
      const formData = new URLSearchParams();
      formData.append('action', 'edit_route');
      formData.append('id', id);
      formData.append('nr_transport', nr_transport);
      formData.append('id_vehicul', id_vehicul);
      formData.append('statii', statii);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns edit_route:", data);
        if (data.success) {
          alert("Rută modificată cu succes!");
        } else {
          alert("Eroare la edit_route: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch edit_route:', error));
    }
    
    document.getElementById('routeForm').addEventListener('submit', function(e) {
      e.preventDefault();
      if (document.getElementById('routeId').value.trim() === "") {
        addRoute();
      } else {
        saveRouteEdits();
      }
    });
    
    function deleteRoute(id) {
      if (!confirm("Sigur dorești să ștergi această rută?")) return;
      const formData = new URLSearchParams();
      formData.append('action', 'delete_route');
      formData.append('id', id);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns delete_route:", data);
        if (data.success) {
          document.querySelector(`tr[data-id="${id}"]`).remove();
        } else {
          alert("Eroare la delete_route: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch delete_route:', error));
    }
    
    function editRoute(id) {
      const formData = new URLSearchParams();
      formData.append('action', 'get_route');
      formData.append('id', id);
      fetch('dashboard.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(response => response.json())
      .then(data => {
        console.log("Răspuns get_route:", data);
        if (data.success) {
          document.getElementById('routeId').value = data.route.id;
          document.getElementById('routeNr').value = data.route.nr_transport;
          document.getElementById('vehicleId').value = data.route.id_vehicul;
          let routeStations = data.route.statii.split(",");
          const sel = document.getElementById('routeStations');
          for (let i = 0; i < sel.options.length; i++) {
            sel.options[i].selected = routeStations.includes(sel.options[i].value);
          }
          document.getElementById('routeForm').style.display = 'block';
        } else {
          alert("Eroare la get_route: " + data.error);
        }
      })
      .catch(error => console.error('Eroare la fetch get_route:', error));
    }
  </script>
  <script src="dashboard.js"></script>
</body>
</html>
