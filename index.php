<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UrbanFlow</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <style>
    /* Stiluri generale pentru pagină */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #f1f1f1;
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
    
    /* Stiluri pentru cardul existent (Harta și Formularul de planificare) */
    .wide-card {
      display: flex;
      max-width: 1200px;
      margin: 2rem auto;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      align-items: stretch;
      padding: 1rem;
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
    .btn-planifica, .btn-trimite {
      background-color: #2ecc71;
      color: white;
      padding: 0.7rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      transition: background-color 0.3s ease;
    }
    .btn-planifica:hover, .btn-trimite:hover {
      background-color: #27ae60;
    }
    
    /* Stiluri pentru toggle-ul formularului de planificare la mobil */
    #togglePlanForm {
      display: none;
    }
    @media (max-width: 768px) {
      .wide-card {
        flex-direction: column;
      }
      #planForm {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 80%;
        max-width: 300px;
        height: 100%;
        background-color: #f8f8f8;
        box-shadow: -2px 0 5px rgba(0, 0, 0, 0.3);
        overflow-y: auto;
        z-index: 1000;
        padding: 1rem;
      }
      #togglePlanForm {
        display: block;
        position: fixed;
        top: 50%;
        right: 0;
        transform: translateY(-50%);
        background-color: #2ecc71;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-top-left-radius: 5px;
        border-bottom-left-radius: 5px;
        cursor: pointer;
        z-index: 1100;
      }
    }
    
    /* Stiluri pentru navbar (se încarcă din navbar.html) */
    .top-nav {
      background-color: #2ecc71;
      padding: 10px 20px;
    }
    .nav-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
    }
    .logo h1 {
      color: white;
      font-size: 1.5rem;
      margin: 0;
    }
    nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
      padding: 0;
    }
    nav ul li a {
      text-decoration: none;
      color: white;
      font-weight: bold;
    }
    .menu-toggle {
      display: none;
      background: none;
      border: none;
      color: white;
      font-size: 1.8rem;
      cursor: pointer;
    }
    @media (max-width: 768px) {
      .menu-toggle {
        display: block;
      }
      nav ul {
        flex-direction: column;
        position: absolute;
        top: 60px;
        right: 0;
        width: 100%;
        background-color: #2ecc71;
        display: none;
      }
      nav ul.show {
        display: flex;
      }
      nav ul li {
        padding: 10px;
        text-align: center;
      }
    }
    
    /* Stiluri pentru cardul de raportare */
    .report-card {
      max-width: 1200px;
      margin: 2rem auto;
      background-color: #fff;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    .report-card h2 {
      color: #2ecc71;
      margin-bottom: 1rem;
    }
    .report-row {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      align-items: flex-end;
    }
    .report-row > div {
      flex: 1;
      min-width: 150px;
    }
    @media (max-width: 768px) {
      .report-row {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <!-- NAVBAR se încarcă din navbar.html -->
  <div id="navbar-container"></div>
  <script>
    fetch("navbar.html")
      .then(res => res.text())
      .then(data => {
        document.getElementById("navbar-container").innerHTML = data;
      });
  </script>
  
  <!-- Butonul de toggle pentru formularul de planificare (vizibil doar pe mobil) -->
  <button id="togglePlanForm">Planifică Ruta</button>

  <!-- MAIN -->
  <main>
    <!-- Cardul existent cu hartă și formularul de planificare -->
    <div class="wide-card">
      <div id="mapid" class="map-container"></div>
      <div class="form-container" id="planForm">
        <h2>Planifică Ruta</h2>
        <!-- Dropdown județ -->
        <div class="form-group">
          <label for="judet">Județ</label>
          <select id="judet" onchange="onJudetChange()">
            <option value="">--Selectează Județ--</option>
          </select>
        </div>
        <!-- Dropdown oraș -->
        <div class="form-group">
          <label for="oras">Oraș</label>
          <select id="oras">
            <option value="">--Selectează Oraș--</option>
          </select>
        </div>
        <!-- Input pentru locul de plecare -->
        <div class="form-group">
          <label for="start">Loc plecare</label>
          <input type="text" id="start" placeholder="Ex: Piața Unirii" />
        </div>
        <!-- Input pentru destinație -->
        <div class="form-group">
          <label for="end">Destinație</label>
          <input type="text" id="end" placeholder="Ex: Gara de Nord" />
        </div>
        <!-- Dropdown pentru mijloc de transport -->
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

    <!-- Noul card de raportare -->
    
  </main>
  <div class="report-card">
      <h2>Raportează o problemă, trafic sau deranjament</h2>
      <div class="report-row">
        <!-- Dropdown pentru Județ -->
        <div>
          <label for="judetRaport">Județ</label>
          <select id="judetRaport" onchange="onJudetRaportChange()">
            <option value="">--Selectează Județ--</option>
          </select>
        </div>
        <!-- Dropdown pentru Oraș -->
        <div>
          <label for="orasRaport">Oraș</label>
          <select id="orasRaport">
            <option value="">--Selectează Oraș--</option>
          </select>
        </div>
        <!-- Dropdown pentru Tipul Raportului -->
        <div>
          <label for="tipRaport">Tip raport</label>
          <select id="tipRaport">
            <option value="problema">Problemă</option>
            <option value="trafic">Trafic</option>
            <option value="deranjament">Deranjament</option>
          </select>
        </div>
      </div>
      <!-- Textbox pentru descrierea problemei -->
      <div class="form-group" style="margin-top: 1rem;">
        <label for="descriereRaport">Descriere</label>
        <input type="text" id="descriereRaport" placeholder="Descrie problema..." />
      </div>
      <button class="btn-trimite" onclick="trimiteRaport()">TRIMITE</button>
    </div>
  <!-- FOOTER -->
  <footer>
    <p>&copy; 2025 UrbanFlow | <a href="#">Termeni și condiții</a> | <a href="#">Politica de confidențialitate</a></p>
  </footer>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    // Inițializare hartă
    const publicMap = L.map('mapid').setView([47.1585, 27.6014], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(publicMap);
  </script>

  <script>
    // Lista de județe și orașe (extinde după necesitate)
    const judeteOrase = {
      "Alba": ["Alba Iulia", "Aiud", "Blaj", "Sebeș", "Cugir"],
      "Arad": ["Arad", "Chișineu-Criș", "Lipova", "Sântana", "Ineu"],
      "Iași": ["Iași", "Pașcani", "Târgu Frumos", "Hârlău", "Podu Iloaiei"],
      "Cluj": ["Cluj-Napoca", "Turda", "Câmpia Turzii", "Dej", "Gherla"]
      // Adaugă restul județelor și orașelor după necesitate
    };

    // Populăm dropdown-ul pentru planificare (județ)
    const judetSelect = document.getElementById("judet");
    for (const judet in judeteOrase) {
      const opt = document.createElement("option");
      opt.value = judet;
      opt.textContent = judet;
      judetSelect.appendChild(opt);
    }
    // Funcție pentru popularea orașelor în planificare
    function onJudetChange() {
      const selectedJudet = judetSelect.value;
      const orasSelect = document.getElementById("oras");
      orasSelect.innerHTML = '<option value="">--Selectează Oraș--</option>';
      if (selectedJudet && judeteOrase[selectedJudet]) {
        judeteOrase[selectedJudet].forEach(oras => {
          const opt = document.createElement("option");
          opt.value = oras;
          opt.textContent = oras;
          orasSelect.appendChild(opt);
        });
      }
    }
    // Populăm dropdown-ul pentru raportare (județ)
    const judetRaportSelect = document.getElementById("judetRaport");
    for (const judet in judeteOrase) {
      const opt = document.createElement("option");
      opt.value = judet;
      opt.textContent = judet;
      judetRaportSelect.appendChild(opt);
    }
    // Funcție pentru popularea orașelor în raportare
    function onJudetRaportChange() {
      const selectedJudet = document.getElementById("judetRaport").value;
      const orasRaportSelect = document.getElementById("orasRaport");
      orasRaportSelect.innerHTML = '<option value="">--Selectează Oraș--</option>';
      if (selectedJudet && judeteOrase[selectedJudet]) {
        judeteOrase[selectedJudet].forEach(oras => {
          const opt = document.createElement("option");
          opt.value = oras;
          opt.textContent = oras;
          orasRaportSelect.appendChild(opt);
        });
      }
    }
  </script>

  <script>
    // Funcții pentru geocodificare și planificare rută (cod de exemplu)
    function haversine(lat1, lng1, lat2, lng2) {
      const R = 6371;
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLng = (lng2 - lng1) * Math.PI / 180;
      const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
                Math.sin(dLng/2) * Math.sin(dLng/2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }
    function geocodeAddress(address) {
      const url = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(address);
      return fetch(url)
        .then(response => response.json())
        .then(data => {
          if (data && data.length > 0) {
            return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
          } else {
            throw new Error("Adresa nu a putut fi geocodificată: " + address);
          }
        });
    }
    function findNearestStation(point, stations) {
      let nearest = null, minDist = Infinity;
      stations.forEach(st => {
        const d = haversine(point.lat, point.lng, parseFloat(st.lat), parseFloat(st.lng));
        if (d < minDist) {
          minDist = d;
          nearest = st;
        }
      });
      return nearest;
    }
    // Exemple de date pentru stații și rute (în practică, vei avea date reale)
    const stations = [
      { id: 1, lat: "47.1585", lng: "27.6014" },
      { id: 2, lat: "47.1641", lng: "27.5826" }
    ];
    const routes = [
      { statii: "1,2" }
    ];
    let currentRouteLayer = null;
    function planificaRuta() {
      const judet = document.getElementById("judet").value;
      const oras = document.getElementById("oras").value;
      const startAddr = document.getElementById("start").value.trim();
      const endAddr = document.getElementById("end").value.trim();
      const transport = document.getElementById("transport").value;
      if (!judet || !oras) {
        alert("Selectează județul și orașul.");
        return;
      }
      if (!startAddr || !endAddr) {
        alert("Completează adresa de plecare și destinație.");
        return;
      }
      Promise.all([geocodeAddress(startAddr), geocodeAddress(endAddr)])
        .then(results => {
          const startCoords = results[0];
          const endCoords = results[1];
          const nearestStart = findNearestStation(startCoords, stations);
          const nearestEnd = findNearestStation(endCoords, stations);
          if (!nearestStart || !nearestEnd) {
            alert("Nu s-au găsit stații suficiente pentru a planifica ruta.");
            return;
          }
          console.log("Stația de start:", nearestStart, "Stația de destinație:", nearestEnd);
          const candidateRoutes = routes.filter(route => {
            const stationList = route.statii.split(",").map(s => s.trim());
            return stationList.includes(String(nearestStart.id)) && stationList.includes(String(nearestEnd.id));
          });
          if (candidateRoutes.length === 0) {
            alert("Nu s-a găsit nicio rută care să conțină ambele stații.");
            return;
          }
          let bestRoute = candidateRoutes[0];
          let bestDiff = Infinity;
          candidateRoutes.forEach(route => {
            const stationList = route.statii.split(",").map(s => s.trim());
            const idxStart = stationList.indexOf(String(nearestStart.id));
            const idxEnd = stationList.indexOf(String(nearestEnd.id));
            const diff = Math.abs(idxEnd - idxStart);
            if (diff < bestDiff) {
              bestDiff = diff;
              bestRoute = route;
            }
          });
          console.log("Ruta selectată:", bestRoute);
          const stationIds = bestRoute.statii.split(",").map(s => s.trim());
          let routeStations = [];
          stationIds.forEach(idStr => {
            let st = stations.find(s => String(s.id) === idStr);
            if (st) {
              routeStations.push({ lat: parseFloat(st.lat), lng: parseFloat(st.lng) });
            }
          });
          if (routeStations.length < 2) {
            alert("Ruta selectată nu are suficiente stații.");
            return;
          }
          getRouteOnRoad(routeStations);
        })
        .catch(error => {
          console.error("Eroare:", error);
          alert("Eroare la geocodare: " + error.message);
        });
    }
    function getRouteOnRoad(coordPairs) {
      const url = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';
      const body = {
        coordinates: coordPairs.map(pair => [pair.lng, pair.lat])
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
        if (currentRouteLayer) {
          publicMap.removeLayer(currentRouteLayer);
        }
        currentRouteLayer = L.geoJSON(data, {
          style: { color: 'blue', weight: 5 }
        }).addTo(publicMap);
        publicMap.fitBounds(currentRouteLayer.getBounds());
      })
      .catch(error => {
        console.error("Eroare la getRouteOnRoad:", error);
        alert("Eroare la obținerea traseului: " + error.message);
      });
    }
  </script>

  <script>
    // Toggle pentru formularul de planificare pe mobil
    document.getElementById('togglePlanForm').addEventListener('click', function() {
      const planForm = document.getElementById('planForm');
      if (planForm.style.display === 'none' || planForm.style.display === '') {
        planForm.style.display = 'block';
      } else {
        planForm.style.display = 'none';
      }
    });
  </script>

  <script>
    // Meniu burger din navbar
    document.addEventListener("DOMContentLoaded", function() {
      const toggleBtn = document.getElementById('menuToggle');
      const navList = document.getElementById('navList');
      toggleBtn.addEventListener("click", function(){
        navList.classList.toggle("show");
      });
    });
  </script>

  <script>
    // Funcție de trimitere raport
    function trimiteRaport() {
      const judet = document.getElementById("judetRaport").value;
      const oras = document.getElementById("orasRaport").value;
      const tipRaport = document.getElementById("tipRaport").value;
      const descriere = document.getElementById("descriereRaport").value.trim();
      if (!judet || !oras || !descriere) {
        alert("Te rugăm să completezi toate câmpurile raportului.");
        return;
      }
      // Exemplu de trimitere - aici poți folosi fetch sau AJAX pentru a trimite datele către server
      console.log("Raport trimis:", { judet, oras, tipRaport, descriere });
      alert("Raportul a fost trimis cu succes!");
      // Resetare formular raport
      document.getElementById("judetRaport").value = "";
      document.getElementById("orasRaport").innerHTML = '<option value="">--Selectează Oraș--</option>';
      document.getElementById("tipRaport").value = "problema";
      document.getElementById("descriereRaport").value = "";
    }
  </script>
</body>
</html>
