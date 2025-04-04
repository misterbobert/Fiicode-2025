// 1) HARTA PUBLICĂ
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

// 2) Rute
document.getElementById('addRouteBtn').addEventListener('click', function() {
  alert("Funcție add rută neimplementată.");
});

function editRoute(routeId) {
  alert("Funcție edit rută neimplementată. ID = " + routeId);
}

function deleteRoute(routeId) {
  if (!confirm("Sigur dorești să ștergi ruta ID " + routeId + "?")) return;
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "delete_route.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "id=" + encodeURIComponent(routeId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Ruta a fost ștearsă!");
          location.reload();
        } else {
          alert("Eroare: " + resp.error);
        }
      } catch (e) {
        alert("Eroare parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la ștergerea rutei.");
    }
  };
}

// 3) Stații
var stationMap = L.map('stationMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(stationMap);

var stationMarker = L.marker([47.25, 26.75], { draggable: true }).addTo(stationMap);

// Submit formular -> add/edit
document.getElementById('stationForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var stationId   = document.getElementById('stationId').value;
  var stationName = document.getElementById('stationName').value.trim();

  if (!stationName) {
    alert("Te rog introdu numele stației.");
    return;
  }

  var latLng = stationMarker.getLatLng();
  var lat = latLng.lat.toFixed(6);
  var lng = latLng.lng.toFixed(6);

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "stations_manager.php", true); // <-- un singur fișier
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  var action = stationId ? "edit_station" : "add_station";
  var params = "action=" + action +
               "&nume=" + encodeURIComponent(stationName) +
               "&lat="  + encodeURIComponent(lat) +
               "&lng="  + encodeURIComponent(lng);
  if (stationId) {
    params += "&id=" + encodeURIComponent(stationId);
  }

  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Stația a fost salvată cu succes!");
          if (!stationId) {
            // ADĂUGARE
            addStationRow(resp.station);
          } else {
            // EDITARE
            updateStationRow(resp.station);
          }
          document.getElementById('stationForm').reset();
          document.getElementById('stationId').value = "";
          stationMarker.setLatLng([47.25, 26.75]);
          stationMap.setView([47.25, 26.75], 13);
        } else {
          alert("Eroare la salvare: " + resp.error);
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la salvarea stației.");
    }
  };
});

// Adaugă rând nou
function addStationRow(station) {
  var tbody = document.getElementById('stationsTableBody');
  var noData = tbody.querySelector('td[colspan="3"]');
  if (noData) {
    tbody.innerHTML = "";
  }
  var tr = document.createElement('tr');
  tr.setAttribute('data-id', station.id);

  var tdName = document.createElement('td');
  tdName.className = 'station-name';
  tdName.textContent = station.nume;

  var tdCoords = document.createElement('td');
  tdCoords.className = 'station-coords';
  tdCoords.textContent = station.lat + ", " + station.lng;

  var tdActions = document.createElement('td');
  var btnEdit = document.createElement('button');
  btnEdit.className = 'edit-station-btn';
  btnEdit.textContent = 'Modifică';
  btnEdit.onclick = function() { editStation(station.id); };

  var btnDel = document.createElement('button');
  btnDel.className = 'delete-station-btn';
  btnDel.textContent = 'Șterge';
  btnDel.onclick = function() { deleteStation(station.id); };

  tdActions.appendChild(btnEdit);
  tdActions.appendChild(btnDel);

  tr.appendChild(tdName);
  tr.appendChild(tdCoords);
  tr.appendChild(tdActions);

  tbody.appendChild(tr);
}

// Actualizează rând
function updateStationRow(station) {
  var row = document.querySelector('tr[data-id="'+station.id+'"]');
  if (row) {
    row.querySelector('.station-name').textContent = station.nume;
    row.querySelector('.station-coords').textContent = station.lat + ", " + station.lng;
  }
}

// Editare (get_station)
function editStation(stationId) {
  var xhr = new XMLHttpRequest();
  xhr.open("GET", "stations_manager.php?action=get_station&id=" + encodeURIComponent(stationId), true);
  xhr.send();

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var st = JSON.parse(xhr.responseText);
        if (st.id) {
          document.getElementById('stationId').value = st.id;
          document.getElementById('stationName').value = st.nume;
          stationMarker.setLatLng([parseFloat(st.lat), parseFloat(st.lng)]);
          stationMap.setView([parseFloat(st.lat), parseFloat(st.lng)], 13);
          stationMap.invalidateSize();
        } else {
          alert("Stația nu a fost găsită.");
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la get_station.");
    }
  };
}

// Ștergere (delete_station)
function deleteStation(stationId) {
  if (!confirm("Sigur vrei să ștergi stația ID " + stationId + "?")) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "stations_manager.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=delete_station&id=" + encodeURIComponent(stationId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Stația a fost ștearsă!");
          var row = document.querySelector('tr[data-id="'+ stationId +'"]');
          if (row) row.remove();
        } else {
          alert("Eroare la ștergere: " + resp.error);
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la ștergere stație.");
    }
  };
}
