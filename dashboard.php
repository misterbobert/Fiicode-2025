<?php
session_start();

if (!isset($_SESSION['admin'])) {
  header("Location: admin.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="admin.css">
  <style>
    body {
      background: linear-gradient(to right, #e8f5e9, #f1f8e9);
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
    }

    .logout-button {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: #66bb6a;
      color: white;
      border: none;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      box-shadow: 0 2px 6px rgba(0, 100, 0, 0.2);
      transition: background-color 0.3s ease;
    }

    .logout-button:hover {
      background-color: #558b2f;
    }

    .section-wrapper {
      max-width: 1100px;
      margin: 5rem auto;
      padding: 2rem;
      background: #f2fcf4; /* mai deschis decât #f9fff9, dar în ton */
      border-radius: 20px;
      box-shadow: 0 6px 20px rgba(0, 100, 0, 0.1);
    }

    .section {
      margin-bottom: 3rem;
      padding: 2rem;
      background: #f9fff9;
      border: 2px solid #cde3cd;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 100, 0, 0.08);
    }

    .section h2 {
      color: #2e7d32;
      margin-bottom: 1rem;
      font-size: 1.8rem;
    }

    #mapid {
      width: 100%;
      height: 400px;
      border-radius: 8px;
      border: 1px solid #ccc;
    }
  </style>
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

    <!-- Harta -->
    <div class="section">
      <h2>🗺️ Hartă Transport Public</h2>
      <div id="mapid"></div>
    </div>

    <!-- Rapoarte și Alerte -->
    <div class="section">
      <h2>📢 Rapoarte și Alerte de la Utilizatori</h2>
      <p>Aici vor apărea sesizările, problemele raportate și alertele legate de transport.</p>
      <!-- Lista se poate genera din backend -->
    </div>

    <!-- Management Rute -->
    <div class="section">
      <h2>🚌 Management Rute și Transporturi</h2>
      <p>Secțiune pentru administrarea rutelor de autobuz, tramvai etc. Poți adăuga, modifica sau șterge transporturi.</p>
      <!-- Link sau integrare către admin-transport.html -->
    </div>

  </div>

  <script>
    // Inițializare hartă Leaflet
    var map = L.map('mapid').setView([47.25, 26.75], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);
  </script>
</body>
</html>
