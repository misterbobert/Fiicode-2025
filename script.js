document.addEventListener("DOMContentLoaded", function () {
    // ------------------ INIȚIALIZARE HARTĂ ------------------
    const map = L.map("mapid").setView([45.9432, 24.9668], 7);
  
    // Stratul de hărți OpenStreetMap
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);
  
    // ------------------ REFERINȚE FORMULAR ------------------
    const startInput = document.getElementById("start");
    const endInput = document.getElementById("end");
    const transportSelect = document.getElementById("transport");
    const planificaBtn = document.querySelector(".btn-planifica");
  
    // Div-uri unde afișăm sugestiile
    const startSuggestions = document.getElementById("start-suggestions");
    const endSuggestions = document.getElementById("end-suggestions");
  
    // ------------------ AUTOCOMPLETE ------------------
    // Funcție care face request la Nominatim pentru sugestii (limit=5)
    async function fetchSuggestions(query) {
      const url = `https://nominatim.openstreetmap.org/search?format=json&limit=5&addressdetails=1&q=${encodeURIComponent(
        query
      )}`;
      const response = await fetch(url);
      const data = await response.json();
      return data; // array cu rezultate
    }
  
    // Afișează rezultatele într-un container
    function showSuggestions(container, data) {
      container.innerHTML = "";
      data.forEach((item) => {
        const div = document.createElement("div");
        div.classList.add("autocomplete-suggestion");
        div.textContent = item.display_name;
        div.addEventListener("click", () => {
          // Când se face click, completăm input-ul părinte
          const parentInput =
            container.id === "start-suggestions" ? startInput : endInput;
          parentInput.value = item.display_name;
          // Golim sugestiile
          container.innerHTML = "";
        });
        container.appendChild(div);
      });
    }
  
    // Funcție generică pentru a atașa un eveniment de input + container
    function attachAutocomplete(input, container) {
      input.addEventListener("input", async () => {
        const query = input.value.trim();
        // Fă request doar dacă sunt minim 3 caractere
        if (query.length < 3) {
          container.innerHTML = "";
          return;
        }
        try {
          const results = await fetchSuggestions(query);
          if (results && results.length > 0) {
            showSuggestions(container, results);
          } else {
            container.innerHTML = "";
          }
        } catch (err) {
          console.error("Eroare la autocomplete:", err);
        }
      });
  
      // Închidem sugestiile când se face click în afara inputului
      document.addEventListener("click", (e) => {
        if (!container.contains(e.target) && e.target !== input) {
          container.innerHTML = "";
        }
      });
    }
  
    // Activăm autocomplete pentru cele două câmpuri
    attachAutocomplete(startInput, startSuggestions);
    attachAutocomplete(endInput, endSuggestions);
  
    // ------------------ RUTARE CU LEAFLET ROUTING MACHINE ------------------
    // Funcție pentru a obține coordonatele (lat, lon) de la Nominatim
    async function geocodeAddress(address) {
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
        address
      )}`;
      const response = await fetch(url);
      const data = await response.json();
      if (data && data.length > 0) {
        return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
      } else {
        throw new Error("Adresa nu a fost găsită: " + address);
      }
    }
  
    // Buton "Planifică"
    planificaBtn.addEventListener("click", async () => {
      const startAddress = startInput.value.trim();
      const endAddress = endInput.value.trim();
      const transport = transportSelect.value; // "auto", "bus", "bike", "walk"
  
      if (!startAddress || !endAddress) {
        alert("Te rugăm să introduci atât locul de plecare, cât și destinația.");
        return;
      }
  
      // Stabilim profilul de rutare (OSRM) pe baza selecției
      let profile = "driving"; // default
      if (transport === "bike") {
        profile = "cycling";
      } else if (transport === "walk") {
        profile = "foot";
      }
      // (auto/bus => driving, pentru exemplu simplu)
  
      try {
        // Geocodare pentru cele două adrese
        const startCoords = await geocodeAddress(startAddress);
        const endCoords = await geocodeAddress(endAddress);
  
        // Ștergem orice rută anterioară
        if (window.currentRouteControl) {
          map.removeControl(window.currentRouteControl);
        }
  
        // Cream un nou control de rutare cu OSRM, folosind profilul selectat
        window.currentRouteControl = L.Routing.control({
          waypoints: [
            L.latLng(startCoords[0], startCoords[1]),
            L.latLng(endCoords[0], endCoords[1]),
          ],
          router: L.Routing.osrmv1({
            serviceUrl: "https://router.project-osrm.org/route/v1",
            profile: profile,
          }),
          lineOptions: {
            styles: [{ color: "blue", opacity: 0.7, weight: 5 }],
          },
          showAlternatives: false,
          addWaypoints: false,
          routeWhileDragging: false,
        }).addTo(map);
  
        // Centrarea hărții pe prima coordonată, de exemplu
        map.setView(startCoords, 13);
  
      } catch (err) {
        alert(err.message);
      }
    });
  });
  