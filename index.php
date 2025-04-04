<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFlow</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f1f1f1;
    }

    .wide-card {
      display: flex;
      max-width: 1200px;
      margin: 2rem auto;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      align-items: stretch;
    }

    .map-container {
      flex: 3;
      height: auto;
    }

    #mapid {
      height: 100%;
      min-height: 500px;
      width: 100%;
    }

    .form-container {
      flex: 2;
      padding: 2rem;
      background-color: #f8f8f8;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-container h2 {
      color: #2ecc71;
      margin-bottom: 1rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      font-weight: bold;
      display: block;
      margin-bottom: 0.3rem;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.5rem;
      border-radius: 5px;
      border: 1px solid #ccc;
    }

    .btn-planifica {
      background-color: #2ecc71;
      color: white;
      padding: 0.7rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }

    .btn-planifica:hover {
      background-color: #27ae60;
    }

    footer {
      text-align: center;
      padding: 1rem;
      font-size: 0.9rem;
      background-color: #f4f4f4;
      margin-top: 2rem;
    }

    footer a {
      color: #2ecc71;
      text-decoration: none;
      margin: 0 0.5rem;
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <div id="navbar-container"></div>
  <script>
    fetch("navbar.html")
      .then(res => res.text())
      .then(data => {
        document.getElementById("navbar-container").innerHTML = data;
      });
  </script>

  <!-- MAIN -->
  <main>
    <div class="wide-card">
      <div id="mapid" class="map-container"></div>
      <div class="form-container" id="planForm">
        <h2>Planifică Ruta</h2>
        <div class="form-group autocomplete-wrapper">
          <label for="start">Loc plecare</label>
          <input type="text" id="start" placeholder="Ex: Piața Unirii" />
          <div class="autocomplete-suggestions" id="start-suggestions"></div>
        </div>
        <div class="form-group autocomplete-wrapper">
          <label for="end">Destinație</label>
          <input type="text" id="end" placeholder="Ex: Gara de Nord" />
          <div class="autocomplete-suggestions" id="end-suggestions"></div>
        </div>
        <div class="form-group">
          <label for="transport">Mijloc de transport</label>
          <select id="transport">
            <option value="auto">Mașină</option>
            <option value="public">Transport în comun</option>
            <option value="walk">Pietonal</option>
          </select>
        </div>
        <button class="btn-planifica" onclick="planificaRuta()">Planifică</button>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer>
    <p>&copy; 2025 UrbanFlow | <a href="#">Termeni și condiții</a> | <a href="#">Politica de confidențialitate</a></p>
  </footer>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    const map = L.map('mapid').setView([47.1585, 27.6014], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function planificaRuta() {
      const start = document.getElementById("start").value;
      const end = document.getElementById("end").value;
      const transport = document.getElementById("transport").value;

      if (!start || !end) {
        alert("Te rugăm să completezi ambele locații.");
        return;
      }

      if (transport === "public") {
        // Coord. test: Piața Unirii → Palas (modificabil ulterior)
        const startLat = 47.164129;
        const startLng = 27.582639;
        const endLat = 47.158481;
        const endLng = 27.601800;

        window.location.href = `afiseaza_traseu.php?startLat=${startLat}&startLng=${startLng}&endLat=${endLat}&endLng=${endLng}`;
      } else {
        alert("Funcția pentru acest mijloc de transport nu este implementată momentan.");
      }
    }
  </script>
</body>
</html>