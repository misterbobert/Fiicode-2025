<?php
session_start();
include 'db.php';  // Conectează-te la baza de date

// Verifică dacă utilizatorul este logat ca admin
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Extrage rutele din baza de date
$sql = "SELECT * FROM rute";
$result = $conn->query($sql);

// Verifică dacă există rute
if ($result->num_rows > 0) {
    $routes = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $routes = [];
}

$conn->close(); // Închide conexiunea
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="dashboard.css"> <!-- Link către fișierul CSS extern -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>

  <!-- Buton logout -->
  <form action="logout.php" method="POST">
    <button class="logout-button" type="submit">Logout</button>
  </form>

  <!-- Întregul conținut inclus într-o secțiune wrapper -->
  <div class="section-wrapper">

    <!-- Secțiunea pentru Hartă Transport Public -->
    <div class="section">
      <h2>🗺️ Hartă Transport Public</h2>
      <div id="publicMap"></div> <!-- Harta Leaflet pentru transport public -->
    </div>

    <!-- Management Rute -->
    <div class="section">
      <h2>🚌 Management Rute și Transporturi</h2>
      <p>Secțiune pentru administrarea rutelor de autobuz, tramvai etc. Poți adăuga, modifica sau șterge transporturi.</p>

      <button class="add-route-btn" id="addRouteBtn">Adaugă Rută Nouă</button>

      <table class="routes-table">
        <thead>
          <tr>
            <th>Nr. Transport</th>
            <th>Stații</th>
            <th>Acțiuni</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Afișează fiecare rută
          foreach ($routes as $route) {
            echo "<tr data-id='" . $route['id'] . "'>
                    <td class='nr_transport'>" . htmlspecialchars($route['nr_transport']) . "</td>
                    <td class='statii'>" . htmlspecialchars($route['statii']) . "</td>
                    <td>
                      <button class='edit-btn' id='editRouteBtn_{$route['id']}' onclick='showMapSection({$route['id']})'>Modifică</button>
                      <button class='delete-btn' onclick='deleteRoute({$route['id']})'>Șterge</button>
                    </td>
                  </tr>";
          }
          ?>
        </tbody>
      </table>

      <!-- Secțiunea de Harta pentru adăugare/modificare stație -->
      <div id="mapSection" class="section" style="display: none;">
        <h2>📍 Adaugă sau Modifică Stație pe Hartă</h2>
        <div id="stationMap"></div>
        
        <form id="addStationForm">
          <label for="stationName">Numele Stației:</label>
          <input type="text" id="stationName" name="stationName" placeholder="Introduceți numele stației" required>
          <button type="submit" class="add-station-btn">Adaugă Stație</button>
        </form>
      </div>

    </div>

  </div>

  <!-- Încarcă fișierul JavaScript extern -->
  <script src="dashboard.js"></script>
</body>
</html>
