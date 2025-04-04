document.addEventListener("DOMContentLoaded", function () {
  // ------------------ INIȚIALIZARE HARTĂ ------------------
  const map = L.map("mapid").setView([47.16, 27.59], 13);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
    maxZoom: 19,
  }).addTo(map);

  // Limite aproximative pentru orașul Iași
  const iasiCityBounds = L.latLngBounds(
    [47.10, 27.50], // colțul sud-vest
    [47.21, 27.67]  // colțul nord-est
  );
  map.setMaxBounds(iasiCityBounds);
  map.on("drag", function() {
    map.panInsideBounds(iasiCityBounds, { animate: false });
  });

  // ------------------ CHEIA ORS + BOUNDING BOX ------------------
  const ORS_API_KEY = "5b3ce3597851110001cf62484e4710e8dc324238a42767164863e57c"; // cheia ta ORS
  // Parametri bounding box pentru Iași
  const boundingBox = "&boundary.rect.min_lon=27.30&boundary.rect.min_lat=47.00&boundary.rect.max_lon=27.80&boundary.rect.max_lat=47.30";

  // ------------------ REFERINȚE FORMULAR ------------------
  const startInput = document.getElementById("start");
  const endInput = document.getElementById("end");
  const transportSelect = document.getElementById("transport");
  const planificaBtn = document.querySelector(".btn-planifica");

  // Containere pentru sugestii autocomplete
  const startSuggestions = document.getElementById("start-suggestions");
  const endSuggestions = document.getElementById("end-suggestions");

  // ------------------ AUTOCOMPLETE CU ORS ------------------
  async function fetchSuggestions(query) {
    const url = `https://api.openrouteservice.org/geocode/autocomplete?api_key=${ORS_API_KEY}&text=${encodeURIComponent(query)}${boundingBox}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error("Eroare la ORS Autocomplete: " + response.statusText);
    }
    const data = await response.json();
    return data.features;
  }

  function showSuggestions(container, features) {
    container.innerHTML = "";
    features.forEach((feature) => {
      const div = document.createElement("div");
      div.classList.add("autocomplete-suggestion");
      div.textContent = feature.properties.label;
      div.addEventListener("click", () => {
        const parentInput = container.id === "start-suggestions" ? startInput : endInput;
        parentInput.value = feature.properties.label;
        container.innerHTML = "";
      });
      container.appendChild(div);
    });
  }

  function attachAutocomplete(input, container) {
    input.addEventListener("input", async () => {
      const query = input.value.trim();
      if (query.length < 3) {
        container.innerHTML = "";
        return;
      }
      try {
        const features = await fetchSuggestions(query);
        if (features && features.length > 0) {
          showSuggestions(container, features);
        } else {
          container.innerHTML = "";
        }
      } catch (err) {
        console.error("Eroare la autocomplete ORS:", err);
      }
    });

    document.addEventListener("click", (e) => {
      if (!container.contains(e.target) && e.target !== input) {
        container.innerHTML = "";
      }
    });
  }

  attachAutocomplete(startInput, startSuggestions);
  attachAutocomplete(endInput, endSuggestions);

  // ------------------ GEOCODARE CU ORS ------------------
  async function geocodeAddressORS(address) {
    const url = `https://api.openrouteservice.org/geocode/search?api_key=${ORS_API_KEY}&text=${encodeURIComponent(address)}${boundingBox}`;
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error("Eroare la ORS Geocode: " + response.statusText);
    }
    const data = await response.json();
    if (data.features && data.features.length > 0) {
      const coord = data.features[0].geometry.coordinates;
      return [coord[1], coord[0]];
    } else {
      throw new Error("Adresa nu a fost găsită în ORS: " + address);
    }
  }

  // ------------------ PROFILURI ORS ------------------
  // Acum avem trei opțiuni: "auto", "public" și "walk"
  function getORSProfile(transport) {
    switch (transport) {
      case "auto":
        return "driving-car";
      case "public":
        return "driving-car"; // folosim același profil, dar vom afișa o culoare diferită
      case "walk":
        return "foot-walking";
      default:
        return "driving-car";
    }
  }

  // ------------------ RUTARE ORS PENTRU ORICE MOD ------------------
  let currentRouteLayer = null;
  let destinationMarker = null;

  // Am adăugat și parametrul "transport" pentru a diferenția culoarea traseului
  async function routeWithORS(startCoords, endCoords, profile, transport) {
    // Ștergem ruta și markerul anterior
    if (currentRouteLayer) {
      map.removeLayer(currentRouteLayer);
      currentRouteLayer = null;
    }
    if (destinationMarker) {
      map.removeLayer(destinationMarker);
      destinationMarker = null;
    }

    const [startLat, startLon] = startCoords;
    const [endLat, endLon] = endCoords;
    const url = `https://api.openrouteservice.org/v2/directions/${profile}?api_key=${ORS_API_KEY}&start=${startLon},${startLat}&end=${endLon},${endLat}`;

    const response = await fetch(url);
    if (!response.ok) {
      throw new Error("Eroare la OpenRouteService (" + profile + "): " + response.statusText);
    }
    const data = await response.json();

    const route = data.features[0];
    const geometry = route.geometry.coordinates;
    const distanceMeters = route.properties.summary.distance;
    const durationSeconds = route.properties.summary.duration;

    const latLngs = geometry.map(coord => [coord[1], coord[0]]);

    // Alege culoarea traseului în funcție de transport
    let color = "blue";
    if (profile === "foot-walking") {
      color = "green";
    } else if (transport === "public") {
      color = "brown"; // culoare diferită pentru Transport în comun
    } else if (transport === "auto") {
      color = "orange";
    }

    currentRouteLayer = L.polyline(latLngs, {
      color: color,
      weight: 5,
      opacity: 0.7
    }).addTo(map);

    // Adăugăm un marker la destinație
    destinationMarker = L.marker([endLat, endLon]).addTo(map);
    destinationMarker.bindPopup("Destinație");

    map.fitBounds(currentRouteLayer.getBounds());

    return { distance: distanceMeters, duration: durationSeconds };
  }

  // ------------------ AFIȘARE REZULTAT ÎN OVERLAY ------------------
  function showResultOverlay(distanceMeters, durationSeconds) {
    const km = (distanceMeters / 1000).toFixed(2);
    const minutes = Math.round(durationSeconds / 60);

    const overlay = document.createElement("div");
    overlay.id = "overlay";
    Object.assign(overlay.style, {
      position: "fixed",
      top: "0",
      left: "0",
      width: "100%",
      height: "100%",
      backgroundColor: "rgba(0,0,0,0.5)",
      display: "flex",
      alignItems: "center",
      justifyContent: "center",
      zIndex: "10000",
    });

    const card = document.createElement("div");
    card.id = "infoCard";
    Object.assign(card.style, {
      backgroundColor: "#fff",
      padding: "20px",
      borderRadius: "8px",
      boxShadow: "0 2px 8px rgba(0,0,0,0.3)",
      textAlign: "center",
      maxWidth: "300px"
    });

    card.innerHTML = `
      <h2>Informații Rută</h2>
      <p>Distanță: ${km} km</p>
      <p>Timp estimat: ${minutes} minute</p>
      <button id="closeCard">Închide</button>
    `;

    overlay.appendChild(card);
    document.body.appendChild(overlay);

    document.getElementById("closeCard").addEventListener("click", function() {
      document.body.removeChild(overlay);
    });
  }

  // ------------------ PLANIFICĂ RUTA ------------------
  planificaBtn.addEventListener("click", async () => {
    const startAddress = startInput.value.trim();
    const endAddress = endInput.value.trim();
    const transport = transportSelect.value; // Valorile posibile: "auto", "public", "walk"

    if (!startAddress || !endAddress) {
      alert("Te rugăm să introduci atât locul de plecare, cât și destinația.");
      return;
    }

    try {
      const startCoords = await geocodeAddressORS(startAddress);
      const endCoords = await geocodeAddressORS(endAddress);

      const orsProfile = getORSProfile(transport);

      const result = await routeWithORS(startCoords, endCoords, orsProfile, transport);
      showResultOverlay(result.distance, result.duration);

    } catch (err) {
      alert("Eroare: " + err.message);
    }
  });
});
