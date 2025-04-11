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

        <!-- Județ și Oraș -->
        <div class="form-group">
          <label for="judet">Județ</label>
          <select id="judet" onchange="onJudetChange()">
            <option value="">--Selectează Județ--</option>
          </select>
        </div>
        <div class="form-group">
          <label for="oras">Oraș</label>
          <select id="oras">
            <option value="">--Selectează Oraș--</option>
          </select>
        </div>
        <!-- /Județ și Oraș -->

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

    // Ex: planificare rută
    function planificaRuta() {
      const judet = document.getElementById("judet").value;
      const oras  = document.getElementById("oras").value;
      const start = document.getElementById("start").value.trim();
      const end   = document.getElementById("end").value.trim();
      const transport = document.getElementById("transport").value;

      if (!start || !end) {
        alert("Te rugăm să completezi ambele locații (loc plecare și destinație).");
        return;
      }

      // Ex: dacă transport e "public", redirecționăm către o pagină
      if (transport === "public") {
        // Deocamdată punem coordonate de test
        const startLat = 47.164129;
        const startLng = 27.582639;
        const endLat   = 47.158481;
        const endLng   = 27.601800;

        // Poți folosi judet/ oras pentru logica ta
        console.log("Selectat județ:", judet, "oraș:", oras);

        // Redirecționez cu parametrii
        window.location.href = `afiseaza_traseu.php?startLat=${startLat}&startLng=${startLng}&endLat=${endLat}&endLng=${endLng}&judet=${encodeURIComponent(judet)}&oras=${encodeURIComponent(oras)}`;
      } else {
        alert("Funcția pentru acest mijloc de transport nu este implementată momentan.");
      }
    }
  </script>

  <!-- Adăugăm un script care inițializează județele și orașele (exemplu parțial) -->
  <script>
    // O listă parțială de județe și orașe. În realitate ai nevoie de toată lista (41 județe + București).
    const judeteOrase = {
      "Alba": ["Alba Iulia", "Aiud", "Blaj", "Sebeș", "Cugir"],
      "Arad": ["Arad", "Chișineu-Criș", "Lipova", "Sântana", "Ineu"],
      "Iași": ["Iași", "Pașcani", "Târgu Frumos", "Hârlău", "Podu Iloaiei"],
      "Cluj": ["Cluj-Napoca", "Turda", "Câmpia Turzii", "Dej", "Gherla"],
      // ... extinde cu restul județelor și localităților ...
    };

    // Populate <select id="judet"> cu județele cheie
    const judetSelect = document.getElementById("judet");
    for (const j in judeteOrase) {
      let opt = document.createElement("option");
      opt.value = j;
      opt.textContent = j;
      judetSelect.appendChild(opt);
    }

    // onJudetChange populăm <select id="oras">
    function onJudetChange() {
      const selectedJudet = judetSelect.value;
      const orasSelect = document.getElementById("oras");
      orasSelect.innerHTML = '<option value="">--Selectează Oraș--</option>'; // reset

      if (selectedJudet && judeteOrase[selectedJudet]) {
        judeteOrase[selectedJudet].forEach(oras => {
          let opt = document.createElement("option");
          opt.value = oras;
          opt.textContent = oras;
          orasSelect.appendChild(opt);
        });
      }
    }
    const map = L.map('mapid').setView([47.1585, 27.6014], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Ex: planificare rută
    function planificaRuta() {
      const judet = document.getElementById("judet").value;
      const oras  = document.getElementById("oras").value;
      const start = document.getElementById("start").value.trim();
      const end   = document.getElementById("end").value.trim();
      const transport = document.getElementById("transport").value;

      if (!start || !end) {
        alert("Te rugăm să completezi ambele locații (loc plecare și destinație).");
        return;
      }

      // Ex: dacă transport e "public", redirecționăm către o pagină
      if (transport === "public") {
        // Deocamdată punem coordonate de test
        const startLat = 47.164129;
        const startLng = 27.582639;
        const endLat   = 47.158481;
        const endLng   = 27.601800;

        // Poți folosi judet/ oras pentru logica ta
        console.log("Selectat județ:", judet, "oraș:", oras);

        // Redirecționez cu parametrii
        window.location.href = `afiseaza_traseu.php?startLat=${startLat}&startLng=${startLng}&endLat=${endLat}&endLng=${endLng}&judet=${encodeURIComponent(judet)}&oras=${encodeURIComponent(oras)}`;
      } else {
        alert("Funcția pentru acest mijloc de transport nu este implementată momentan.");
      }
    }
  </script>

  <!-- Adăugăm un script care inițializează județele și orașele (exemplu parțial) -->
  <script>
    // O listă parțială de județe și orașe. În realitate ai nevoie de toată lista (41 județe + București).
    const judeteOrase = {
      "Alba": ["Alba Iulia", "Aiud", "Blaj", "Sebeș", "Cugir"],
      "Arad": ["Arad", "Chișineu-Criș", "Lipova", "Sântana", "Ineu"],
      "Iași": ["Iași", "Pașcani", "Târgu Frumos", "Hârlău", "Podu Iloaiei"],
      "Cluj": ["Cluj-Napoca", "Turda", "Câmpia Turzii", "Dej", "Gherla"],
      // ... extinde cu restul județelor și localităților ...
    };

    // Populate <select id="judet"> cu județele cheie
    const judetSelect = document.getElementById("judet");
    for (const j in judeteOrase) {
      let opt = document.createElement("option");
      opt.value = j;
      opt.textContent = j;
      judetSelect.appendChild(opt);
    }

    // onJudetChange populăm <select id="oras">
    function onJudetChange() {
      const selectedJudet = judetSelect.value;
      const orasSelect = document.getElementById("oras");
      orasSelect.innerHTML = '<option value="">--Selectează Oraș--</option>'; // reset

      if (selectedJudet && judeteOrase[selectedJudet]) {
        judeteOrase[selectedJudet].forEach(oras => {
          let opt = document.createElement("option");
          opt.value = oras;
          opt.textContent = oras;
          orasSelect.appendChild(opt);
        });
      }
    }
 // Helper: calculează distanța haversine între două puncte (în km)
function haversine(lat1, lng1, lat2, lng2) {
  const R = 6371; // Raza Pământului în km
  const dLat = (lat2 - lat1) * Math.PI/180;
  const dLng = (lng2 - lng1) * Math.PI/180;
  const a =
    Math.sin(dLat/2) * Math.sin(dLat/2) +
    Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) *
    Math.sin(dLng/2) * Math.sin(dLng/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

/**
 * geocodeAddress - folosește serviciul Nominatim pentru a obține coordonatele unei adrese.
 * @param {string} address Adresa de geocodat.
 * @returns {Promise} care se rezolvă cu un obiect { lat, lng }.
 */
function geocodeAddress(address) {
  const url = "https://nominatim.openstreetmap.org/search?format=json&q=" + encodeURIComponent(address);
  return fetch(url)
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        // Folosim prima soluție
        return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
      } else {
        throw new Error("Adresa nu a putut fi geocodificată: " + address);
      }
    });
}

/**
 * findNearestStation - caută în array-ul de stații cea mai apropiată de un punct dat.
 * @param {Object} point Obiectul { lat, lng }.
 * @param {Array} stations Array de stații (fiecare cu proprietățile lat și lng).
 * @returns {Object} Stația cea mai apropiată (sau null dacă array-ul este gol).
 */
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

/**
 * planificaRuta - funcția principală de planificare a rutei.
 * Etapele:
 * 1. Geocodifică adresele de start și destinație.
 * 2. Găsește stația cea mai apropiată de start și cea mai apropiată de destinație.
 * 3. Caută în array-ul global de rute (routes) o rută care conține ambele stații.
 * 4. Dacă se găsește, extrage coordonatele stațiilor din ruta respectivă și apelează getRouteOnRoad.
 */
function planificaRuta() {
  const judet = document.getElementById("judet").value;
  const orasSelect = document.getElementById("oras").value;
  const startAddr = document.getElementById("start").value.trim();
  const endAddr = document.getElementById("end").value.trim();
  const transport = document.getElementById("transport").value;

  if (!judet || !orasSelect) {
    alert("Selectează județul și orașul.");
    return;
  }
  if (!startAddr || !endAddr) {
    alert("Completează adresa de plecare și destinație.");
    return;
  }
  
  // Geocode start și end adrese
  Promise.all([geocodeAddress(startAddr), geocodeAddress(endAddr)])
    .then(results => {
      const startCoords = results[0];
      const endCoords = results[1];
      // Găsește în lista globală 'stations' (din tabelul statii_<oras>) stația cea mai apropiată de fiecare coordonată
      const nearestStart = findNearestStation(startCoords, stations);
      const nearestEnd   = findNearestStation(endCoords, stations);
      
      if (!nearestStart || !nearestEnd) {
        alert("Nu s-au găsit stații suficiente pentru a planifica ruta.");
        return;
      }
      
      console.log("Stația de start identificată:", nearestStart);
      console.log("Stația de destinație identificată:", nearestEnd);
      
      // Caută în array-ul global 'routes' (din tabelul rute_<oras>) rutele care conțin ambele stații
      // Presupunem că field-ul "statii" din fiecare rută e un șir de ID-uri separate prin virgulă
      const candidateRoutes = routes.filter(route => {
        const stationList = route.statii.split(",").map(s => s.trim());
        return stationList.includes(String(nearestStart.id)) &&
               stationList.includes(String(nearestEnd.id));
      });
      
      if (candidateRoutes.length === 0) {
        alert("Nu s-a găsit nicio rută care să conțină stația de start și destinație.");
        return;
      }
      
      // Alege ruta cu diferența minimă de index între cele două stații în lista stațiilor din rută
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
      
      // Din ruta selectată, construim un array de coordonate pentru stațiile din ruta (în ordine)
      const stationIds = bestRoute.statii.split(",").map(s => s.trim());
      let routeStations = [];
      stationIds.forEach(idStr => {
        let st = stations.find(s => String(s.id) === idStr);
        if (st) {
          routeStations.push({ lat: parseFloat(st.lat), lng: parseFloat(st.lng) });
        }
      });
      
      if (routeStations.length < 2) {
        alert("Ruta selectată nu are suficiente stații pentru a trasa un traseu.");
        return;
      }
      
      // Apelăm funcția care trasează traseul pe străzi folosind OpenRouteService
      getRouteOnRoad(routeStations);
    })
    .catch(error => {
      console.error("Eroare în planificare ruta:", error);
      alert("Eroare la geocodare: " + error.message);
    });
}

/**
 * getRouteOnRoad - folosește OpenRouteService pentru a obține traseul pe străzi.
 * coordPairs este un array de obiecte cu proprietățile { lat, lng }.
 */
function getRouteOnRoad(coordPairs) {
  const url = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';
  // API-ul așteaptă coordonatele în format [lng, lat]
  const body = {
    coordinates: coordPairs.map(pair => [pair.lng, pair.lat])
  };

  fetch(url, {
    method: 'POST',
    headers: {
      'Authorization': '5b3ce3597851110001cf6248d3f47cc712ed42bdbc3b848f8854acc6', // Înlocuiește cu cheia ta validă
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  })
  .then(res => res.json())
  .then(data => {
    // Dacă există traseu vechi, îl eliminăm
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

// Variabilă globală pentru stratul traseului afișat pe harta publică
let currentRouteLayer = null;

// Atașează funcția de planificare la butonul din formularul principal (Planifică)
document.querySelector('.btn-planifica').addEventListener('click', planificaRuta);
</script>



</body>
</html>
