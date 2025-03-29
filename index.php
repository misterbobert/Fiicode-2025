<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFlow</title>
  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <style>
    .menu-toggle {
      display: none;
      background-color: transparent;
      border: none;
      font-size: 1.5rem;
      color: #fff;
      cursor: pointer;
      z-index: 1001;
    }

    .toggle-form-btn {
      display: none;
    }

    @media (max-width: 768px) {
      .nav-container {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
      }

      nav {
        width: 100%;
      }

      nav ul {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        background-color: #2ecc71;
        width: 100%;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        z-index: 1000;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
      }

      nav ul.active {
        max-height: 200px;
        opacity: 1;
        transform: translateY(0);
      }

      nav ul li {
        margin: 0.5rem 1rem;
      }

      .menu-toggle {
        display: block;
      }

      .wide-card {
        display: block !important;
        padding: 0;
        background: none;
        box-shadow: none;
      }

      .form-container {
        position: fixed;
        top: 100px;
        right: -100%;
        width: 80%;
        background: white;
        padding: 1rem;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        transition: right 0.3s ease;
        z-index: 999;
        max-height: 80%;
        overflow-y: auto;
        border-radius: 10px;
      }

      .form-container.active {
        right: 10px;
      }

      .toggle-form-btn {
        display: flex;
        position: fixed;
        top: 105px;
        right: 10px;
        z-index: 1000;
        background-color: #2ecc71;
        color: white;
        border: none;
        font-size: 1.5rem;
        padding: 0.5rem 0.7rem;
        cursor: pointer;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
      }

      .toggle-form-btn span {
        display: inline-block;
        transform: rotate(0deg);
        transition: transform 0.3s ease;
      }

      .form-container.active ~ .toggle-form-btn span {
        transform: rotate(180deg);
      }

      #mapid {
        height: calc(100vh - 60px);
        width: 100vw;
        z-index: 1;
      }
    }

    #mapid {
      height: 500px;
      width: 100%;
    }
  </style>
</head>
<body>
  <!-- HEADER: Meniu sus -->
  <header class="top-nav">
    <div class="nav-container">
      <div class="logo">
        <h1>UrbanFlow</h1>
      </div>
      <button class="menu-toggle" id="menuToggle">☰</button>
      <nav>
        <ul id="navList">
          <li><a href="index.php">Acasă</a></li>
          <li><a href="#">Rute</a></li>
          <li><a href="#">Contact</a></li>
          <?php if (isset($_SESSION['email'])): ?>
            <li><a href="account.php">Contul Meu</a></li>
          <?php else: ?>
            <li><a href="register.html">Înregistrează-te</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

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
        <button class="btn-planifica">Planifică</button>
      </div>
      <button class="toggle-form-btn" onclick="toggleForm()"><span>➤</span></button>
    </div>
  </main>

  <!-- FOOTER -->
  <footer>
    <p>&copy; 2025 UrbanFlow | <a href="#">Termeni și condiții</a> | <a href="#">Politica de confidențialitate</a></p>
  </footer>

  <!-- Leaflet JS (pentru harta) -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    // inițializare hartă dacă există elementul cu id mapid
    if (document.getElementById('mapid')) {
      const map = L.map('mapid').setView([47.1585, 27.6014], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);
    }

    document.getElementById("menuToggle").addEventListener("click", function () {
      document.getElementById("navList").classList.toggle("active");
    });

    function toggleForm() {
      document.getElementById("planForm").classList.toggle("active");
    }
  </script>
</body>
</html>