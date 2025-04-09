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


// ============= HARTA PUBLICĂ =============
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

// ============= MANAGEMENT Rute ============
document.getElementById('addRouteBtn').addEventListener('click', function() {
  document.getElementById('addRouteForm').style.display = 'block';
});


function editRoute(routeId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("action=get_route&id=" + encodeURIComponent(routeId));

  xhr.onload = function () {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp.id) {
        document.getElementById('routeNr').value = resp.nr_transport;
        document.getElementById('vehicleId').value = resp.id_vehicul;
        document.getElementById('routeStations').value = resp.statii;
        document.getElementById('addRouteForm').style.display = 'block';
        document.getElementById('addRouteForm').setAttribute('data-id', resp.id);
      } else {
        alert("Eroare: " + (resp.error || "Ruta nu a fost găsită."));
      }
    }
  };
}
let currentPolyline = null;

function viewRoute(routeId) {
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.send("action=get_route&id=" + encodeURIComponent(routeId));

  xhr.onload = function () {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp && resp.statii) {
        const coordList = resp.statii.split(',').map(s => s.trim()).filter(Boolean);
        const coordPairs = [];

        for (let i = 0; i < coordList.length; i++) {
          const stationName = coordList[i];
          const match = stations.find(s => s.nume === stationName);
          if (match) {
            coordPairs.push([parseFloat(match.lat), parseFloat(match.lng)]);
          }
        }

        if (currentPolyline) {
          publicMap.removeLayer(currentPolyline);
        }

        currentPolyline = getRouteOnRoad(coordPairs);

        publicMap.fitBounds(currentPolyline.getBounds());
      } else {
        alert("Ruta nu are stații valide sau nu a fost găsită.");
      }
    }
  };
}


function deleteRoute(routeId) {
  if (!confirm("Sigur ștergi ruta cu ID " + routeId + "?")) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=delete_route&id=" + encodeURIComponent(routeId);
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
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare la cererea HTTP pentru ștergere rută.");
    }
  };
}

// =========== MANAGEMENT STAȚII ===========

// 1) Harta + Marker
var stationMap = L.map('stationMap').setView([47.25, 26.75], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(stationMap);

var stationMarker = L.marker([47.25, 26.75], { draggable: true }).addTo(stationMap);

stationMarker.on('moveend', function(e) {
  console.log("Marker moved to:", e.target.getLatLng());
});

// 2) Submit formular => add/edit stație
document.getElementById('stationForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var stationId = document.getElementById('stationId').value;
  var stationName = document.getElementById('stationName').value.trim();
  var stationType = document.getElementById('stationType').value;

  if (!stationName || !stationType) {
    alert("Te rog introdu numele stației și selectează tipul transportului.");
    return;
  }

  var latLng = stationMarker.getLatLng();
  var lat = latLng.lat.toFixed(6);
  var lng = latLng.lng.toFixed(6);

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  var action = stationId ? "edit_station" : "add_station";
  var params = "action=" + action +
               "&nume=" + encodeURIComponent(stationName) +
               "&lat=" + encodeURIComponent(lat) +
               "&lng=" + encodeURIComponent(lng) +
               "&tip_transport=" + encodeURIComponent(stationType);
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
            addStationRow(resp.station);
          } else {
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

// 3) Funcții add/update row
function addStationRow(station) {
  var tbody = document.getElementById('stationsTableBody');
  var emptyRow = tbody.querySelector('td[colspan="4"]');
  if (emptyRow) {
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

  var tdType = document.createElement('td');
  tdType.className = 'station-type';
  tdType.textContent = station.tip_transport;

  var tdAct = document.createElement('td');
  var btnEdit = document.createElement('button');
  btnEdit.className = 'edit-station-btn';
  btnEdit.textContent = 'Modifică';
  btnEdit.onclick = function() { editStation(station.id); };

  var btnDel = document.createElement('button');
  btnDel.className = 'delete-station-btn';
  btnDel.textContent = 'Șterge';
  btnDel.onclick = function() { deleteStation(station.id); };

  tdAct.appendChild(btnEdit);
  tdAct.appendChild(btnDel);

  tr.appendChild(tdName);
  tr.appendChild(tdCoords);
  tr.appendChild(tdType);
  tr.appendChild(tdAct);
  tbody.appendChild(tr);
}

function updateStationRow(station) {
  var row = document.querySelector('tr[data-id="'+station.id+'"]');
  if (row) {
    row.querySelector('.station-name').textContent = station.nume;
    row.querySelector('.station-coords').textContent = station.lat + ", " + station.lng;
    row.querySelector('.station-type').textContent = station.tip_transport;
  }
}

// 4) Editare stație => get_station
function editStation(stationId) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=get_station&id=" + encodeURIComponent(stationId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var st = JSON.parse(xhr.responseText);
        if (st.id) {
          document.getElementById('stationId').value = st.id;
          document.getElementById('stationName').value = st.nume;
          document.getElementById('stationType').value = st.tip_transport;
          stationMarker.setLatLng([parseFloat(st.lat), parseFloat(st.lng)]);
          stationMap.setView([parseFloat(st.lat), parseFloat(st.lng)], 13);
          stationMap.invalidateSize();
        } else {
          alert("Eroare: Stația nu a fost găsită.");
        }
      } catch(e) {
        alert("Eroare la parse JSON: " + e);
      }
    } else {
      alert("Eroare HTTP la get_station.");
    }
  };
}

// 5) Ștergere stație
function deleteStation(stationId) {
  if (!confirm("Sigur dorești să ștergi stația ID " + stationId + "?")) return;

  var xhr = new XMLHttpRequest();
  xhr.open("POST", "?ajax=1", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  var params = "action=delete_station&id=" + encodeURIComponent(stationId);
  xhr.send(params);

  xhr.onload = function() {
    if (xhr.status === 200) {
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success) {
          alert("Stația a fost ștearsă!");
          var row = document.querySelector('tr[data-id="'+stationId+'"]');
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
}document.getElementById('addRouteBtn').addEventListener('click', function() {
  document.getElementById('addRouteForm').style.display = 'block';
});
document.getElementById('addRouteForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const nr = document.getElementById('routeNr').value.trim();
  const vehicul = document.getElementById('vehicleId').value.trim();
  const statii = document.getElementById('routeStations').value.trim();

  if (!nr || !vehicul || !statii) {
    alert("Completează toate câmpurile!");
    return;
  }

  const xhr = new XMLHttpRequest();
xhr.open("POST", "?ajax=1", true);
xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

const routeId = document.getElementById('addRouteForm').getAttribute('data-id');
const action = routeId ? 'edit_route' : 'add_route';

let params = "action=" + action +
             "&nr_transport=" + encodeURIComponent(nr) +
             "&id_vehicul=" + encodeURIComponent(vehicul) +
             "&statii=" + encodeURIComponent(statii);

if (routeId) {
  params += "&id=" + encodeURIComponent(routeId);
}


  xhr.onload = function() {
    if (xhr.status === 200) {
      const resp = JSON.parse(xhr.responseText);
      if (resp.success) {
        alert("Rută salvată!");
        addRouteRow(resp.route);
        document.getElementById('addRouteForm').reset();
        document.getElementById('addRouteForm').style.display = 'none';
      } else {
        alert("Eroare: " + resp.error);
      }if (routeId) {
  alert("Rută actualizată!");
  location.reload();
}

    }
  };

  xhr.send(params);
});

function getRouteOnRoad(coordPairs) {
  const url = 'https://api.openrouteservice.org/v2/directions/driving-car/geojson';

  const body = {
    coordinates: coordPairs.map(pair => [pair[1], pair[0]]) // lng, lat
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
    if (currentPolyline) publicMap.removeLayer(currentPolyline);
    currentPolyline = L.geoJSON(data, {
      style: { color: 'blue', weight: 4 }
    }).addTo(publicMap);
    publicMap.fitBounds(currentPolyline.getBounds());
  });
}

function addRouteRow(route) {
  const tbody = document.getElementById('routesTableBody');
  const tr = document.createElement('tr');

  tr.setAttribute('data-id', route.id);
  tr.innerHTML = `
    <td>${route.nr_transport}</td>
    <td>${route.id_vehicul}</td>
    <td>${route.statii}</td>
    <td>
      <button class="edit-btn" onclick="editRoute(${route.id})">Modifică</button>
      <button class="delete-btn" onclick="deleteRoute(${route.id})">Șterge</button>
      <button class="edit-btn" onclick="viewRoute(${route.id})">Vizualizează</button>
    </td>
  `;

  tbody.appendChild(tr);
}


document.getElementById('addRouteForm').reset();
document.getElementById('addRouteForm').style.display = 'none';
document.getElementById('addRouteForm').removeAttribute('data-id');
var stationMap = L.map('stationMap').setView([47.25, 26.75], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(stationMap);
var publicMap = L.map('publicMap').setView([47.25, 26.75], 13); // lat, lng, zoom

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '© OpenStreetMap'
}).addTo(publicMap);

