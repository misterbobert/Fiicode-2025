document.addEventListener("DOMContentLoaded", function () {
    // Inițializează harta centrată pe România (coordonate aproximative)
    const map = L.map("mapid").setView([45.9432, 24.9668], 7);
  
    // Adaugă stratul de hărți OpenStreetMap
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);
  
    // Referințe la elementele din formular
    const startInput = document.getElementById("start");
    const endInput = document.getElementById("end");
    const transportSelect = document.getElementById("transport");
    const planificaBtn = document.querySelector(".btn-planifica");
  
    // Funcție pentru a apela Nominatim și a obține coordonatele (lat, lon) unei adrese
    async function geocodeAddress(address) {
      const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
        address
      )}`;
      const response = await fetch(url);
      const data = await response.json();
      if (data && data.length > 0) {
        // Ia primul rezultat
        return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
      } else {
        throw new Error("Adresa nu a fost găsită: " + address);
      }
    }
  
    // Când utilizatorul face click pe "Planifică", geocodăm start și end, apoi afișăm ruta
    planificaBtn.addEventListener("click", async () => {
      const startAddress = startInput.value.trim();
      const endAddress = endInput.value.trim();
      const transport = transportSelect.value; // "auto", "bus", "bike", "walk"
  
      if (!startAddress || !endAddress) {
        alert("Te rugăm să introduci atât locul de plecare, cât și destinația.");
        return;
      }
  
      // Stabilim profilul de rutare (OSRM) pe baza selecției
      // driving, cycling, foot (posibile valori OSRM)
      let profile = "driving";
      if (transport === "bike") {
        profile = "cycling";
      } else if (transport === "walk") {
        profile = "foot";
      } 
      // (auto/bus => driving, e un simplu exemplu)
  
      try {
        // Geocodare pentru cele două adrese
        const startCoords = await geocodeAddress(startAddress);
        const endCoords = await geocodeAddress(endAddress);
  
        // Ștergem orice rută anterioară de pe hartă, dacă există
        // (dacă vrei să refolosești controlul, îl poți stoca într-o variabilă globală,
        //  dar pentru exemplu îl recreăm de fiecare dată)
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
          addWaypoints: false, // dezactivează posibilitatea de a muta manual waypoint-urile pe hartă
          routeWhileDragging: false,
        }).addTo(map);
  
      } catch (err) {
        alert(err.message);
      }
    });
  });
  