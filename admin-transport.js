document.addEventListener("DOMContentLoaded", function () {
  // ------------------ PARAMETRI ORS ------------------
  const ORS_API_KEY = "5b3ce3597851110001cf62484e4710e8dc324238a42767164863e57c";
  // Funcție pentru a alege profilul ORS în funcție de mijlocul de transport
  function getORSProfile(transport) {
    switch (transport) {
      case "autobuz":
      case "tramvai":
        return "driving-car";  // folosim același profil pentru vehicule
      case "walk":
        return "foot-walking";
      default:
        return "driving-car";
    }
  }

  // Funcție care face un POST către ORS Directions pentru toate stațiile (nu segmentat)
  async function routeWithORS(coordsArray, profile) {
    // coordsArray este un array de obiecte: {lat, lng}
    // Construim un array de coordonate [ [lng, lat], [lng, lat], ... ]
    const coordinates = coordsArray.map(c => [c.lng, c.lat]);
    const url = `https://api.openrouteservice.org/v2/directions/${profile}`;
    const body = {
      coordinates: coordinates,
      format: "geojson"
    };

    const response = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": ORS_API_KEY
      },
      body: JSON.stringify(body)
    });
    if (!response.ok) {
      throw new Error("Eroare la ORS Directions: " + response.statusText);
    }
    const data = await response.json();
    if (!data.features || data.features.length === 0) {
      throw new Error("Nu s-a găsit nicio rută ORS.");
    }
    return data.features[0]; // GeoJSON feature
  }

  // ------------------ HARTA PRINCIPALĂ PENTRU TRASEE ------------------
  const mainMap = L.map("mapid").setView([47.16, 27.59], 13);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(mainMap);
  // Setăm un bounding box (exemplu similar cu script.js din index)
  const iasiCityBounds = L.latLngBounds([47.10, 27.50], [47.21, 27.67]);
  mainMap.setMaxBounds(iasiCityBounds);
  mainMap.on("drag", function() {
    mainMap.panInsideBounds(iasiCityBounds, { animate: false });
  });
  let currentRoutePolyline = null;
  let currentMarkers = [];

  // ------------------ GESTIONAREA TRANSPORTURILOR ------------------
  const transportTypeInput = document.getElementById("transportType");
  const transportNumberInput = document.getElementById("transportNumber");
  const transportRouteInput = document.getElementById("transportRoute");
  const addTransportBtn = document.getElementById("addTransport");
  const transportTableBody = document.getElementById("transportTable").querySelector("tbody");

  // Dicționar pentru stațiile personalizate
  // Cheile vor fi stocate în lowercase (ex: "gara")
  const customStations = {};

  addTransportBtn.addEventListener("click", function () {
    const type = transportTypeInput.value;
    const number = transportNumberInput.value.trim();
    const route = transportRouteInput.value.trim();
    if (!number || !route) {
      alert("Completează toate câmpurile!");
      return;
    }
    addTransportToTable(type, number, route);
  });

  function addTransportToTable(type, number, route) {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${type}</td>
      <td>${number}</td>
      <td>${route}</td>
      <td><button class="show-route" data-type="${type}" data-route="${route}">Vezi Traseu</button></td>
    `;
    transportTableBody.appendChild(row);
  }

  // La click pe butonul "Vezi Traseu" se trasează ruta folosind ORS
  document.getElementById("transportTable").addEventListener("click", async function (event) {
    if (event.target.classList.contains("show-route")) {
      const routeStr = event.target.dataset.route; // de ex: "Gara, Podu Ros, Tg Cucu"
      const transportType = event.target.dataset.type; // ex: "autobuz"
      const stationNames = routeStr.split(",").map(s => s.trim());
      try {
        // Obținem coordonatele pentru fiecare stație (numele sunt case-insensitive)
        const coordsArray = stationNames.map(name => {
          const key = name.toLowerCase();
          if (!customStations[key]) {
            throw new Error(`Stația "${name}" nu a fost definită prin pin (sau scrisă diferit).`);
          }
          return customStations[key]; // {lat, lng}
        });
        // Apelăm ORS Directions
        const orsProfile = getORSProfile(transportType);
        const routeData = await routeWithORS(coordsArray, orsProfile);
        // Convertim geometria ORS (array de [lng, lat]) la [lat, lng]
        const latLngs = routeData.geometry.coordinates.map(coord => [coord[1], coord[0]]);
        // Ștergem ruta anterioară
        if (currentRoutePolyline) {
          mainMap.removeLayer(currentRoutePolyline);
        }
        currentMarkers.forEach(m => mainMap.removeLayer(m));
        currentMarkers = [];
        // Desenăm Polyline-ul
        currentRoutePolyline = L.polyline(latLngs, { color: "brown", weight: 5 }).addTo(mainMap);
        // Adăugăm markere pentru fiecare stație
        coordsArray.forEach((coord, index) => {
          const marker = L.marker([coord.lat, coord.lng]).addTo(mainMap);
          let label = index === 0 ? "Start" : (index === coordsArray.length - 1 ? "Destinație" : `Stație ${index}`);
          marker.bindPopup(label);
          currentMarkers.push(marker);
        });
        mainMap.fitBounds(currentRoutePolyline.getBounds());
      } catch (err) {
        alert("Eroare: " + err.message);
      }
    }
  });

  // ------------------ HARTA SECUNDARĂ PENTRU SELECTAREA STAȚIILOR ------------------
  const stationMap = L.map("stationMap").setView([47.16, 27.59], 13);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(stationMap);
  stationMap.setMaxBounds(iasiCityBounds);
  stationMap.on("drag", function() {
    stationMap.panInsideBounds(iasiCityBounds, { animate: false });
  });
  // Marker draggabil pentru selecția stației
  let stationMarker = L.marker([47.16, 27.59], { draggable: true }).addTo(stationMap);

  // La click pe "Salvează Stație", se salvează coordonatele stației cu numele introdus (în lowercase)
  const stationNameInput = document.getElementById("stationNameInput");
  const saveStationBtn = document.getElementById("saveStationBtn");

  saveStationBtn.addEventListener("click", function() {
    const stationNameRaw = stationNameInput.value.trim();
    if (!stationNameRaw) {
      alert("Te rog să introduci un nume pentru stație.");
      return;
    }
    const stationKey = stationNameRaw.toLowerCase();
    const latLng = stationMarker.getLatLng();
    customStations[stationKey] = { lat: latLng.lat, lng: latLng.lng };
    alert(`Stația "${stationNameRaw}" a fost salvată la coordonatele [${latLng.lat.toFixed(5)}, ${latLng.lng.toFixed(5)}].\n(Se va folosi sub numele "${stationKey}".)`);
    stationNameInput.value = "";
  });
});
