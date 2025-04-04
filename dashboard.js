/* Inițializare hartă pentru "Hartă Transport Public" */
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

/* Inițializare hartă pentru secțiunea de management (adăugare/modificare stație) */
var stationMap = L.map('stationMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(stationMap);

/* Inițializare marker pentru harta din secțiunea de management */
var stationMarker = L.marker([47.25, 26.75], { draggable: true }).addTo(stationMap);
stationMarker.on('moveend', function(e) {
  var lat = e.target.getLatLng().lat;
  var lng = e.target.getLatLng().lng;
  document.getElementById('stationName').dataset.lat = lat;
  document.getElementById('stationName').dataset.lng = lng;
});

/* Funcția de a arăta secțiunea cu hartă și de a seta coordonatele marker-ului */
function showMapSection(routeId = null) {
    var mapSection = document.getElementById('mapSection');
    mapSection.style.display = 'block';
    
    // Forțează recalcularea dimensiunii hărții după ce containerul devine vizibil
    stationMap.invalidateSize();
  
    if (routeId) {
      // Exemplu: preîncarcă coordonatele rutei (înlocuiește cu datele reale din backend)
      var lat = 47.25;
      var lng = 26.75;
      stationMarker.setLatLng([lat, lng]);
      stationMap.setView([lat, lng], 13);
    } else {
      stationMarker.setLatLng([47.25, 26.75]);
      stationMap.setView([47.25, 26.75], 13);
    }
  }
  
/* Formular de adăugare a stației */
document.getElementById('addStationForm').addEventListener('submit', function(event) {
  event.preventDefault();
  
  var stationName = document.getElementById('stationName').value;
  var lat = document.getElementById('stationName').dataset.lat;
  var lng = document.getElementById('stationName').dataset.lng;

  if (!stationName || !lat || !lng) {
    alert("Te rog selectează locația și adaugă numele stației.");
    return;
  }

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "add_station.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("stationName=" + encodeURIComponent(stationName) + "&lat=" + encodeURIComponent(lat) + "&lng=" + encodeURIComponent(lng));

  xhr.onload = function() {
    if (xhr.status == 200) {
      alert("Stația a fost adăugată cu succes!");
    } else {
      alert("Eroare la adăugarea stației.");
    }
  };
});

/* Funcție de ștergere a unei rute */
function deleteRoute(routeId) {
  if (confirm("Sigur dorești să ștergi această rută?")) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "delete_route.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send("id=" + encodeURIComponent(routeId));
    
    xhr.onload = function() {
      if (xhr.status == 200) {
        alert("Ruta a fost ștearsă cu succes!");
        location.reload();
      } else {
        alert("Eroare la ștergerea rutei.");
      }
    };
  }
}
