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
$dbname     = "urbanflow";

// Conectare
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Eroare la conectarea la BD: " . $conn->connect_error);
}

$oras = '';
$tabela_statii = 'stations';
$tabela_rute   = 'rute';

if (isset($_SESSION['admin_email'])) {
    $email = $_SESSION['admin_email'];

    // Căutăm orașul corespunzător utilizatorului curent
    $stmt = $conn->prepare("SELECT oras FROM admin_users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $oras = preg_replace('/[^a-zA-Z0-9]/', '_', $row['oras']);  // transformăm în format tabel
        $tabela_statii = "statii_" . $oras;
        $tabela_rute   = "rute_" . $oras;
    } else {
        die("Eroare: Orașul nu a fost găsit în baza de date.");
    }
    $stmt->close();
} else {
    die("Eroare: Utilizatorul nu este autentificat.");
}


// SETARE NUME INSTITUȚIE PENTRU TEST (doar temporar)
if (!isset($_SESSION['admin_institution'])) {
  $_SESSION['admin_institution'] = 'Primăria Pașcani';
}

// Parametrii MySQL

// Conectare
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Eroare la conectarea la BD: " . $conn->connect_error);
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
  <!-- Stil rapid --> <!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  
<link rel="stylesheet" href="dashboard.css">
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
</script>
<script src="dashboard.js"></script>
 
</body>
</html>
