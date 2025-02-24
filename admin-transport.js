document.addEventListener("DOMContentLoaded", function() {
    const transportForm = document.getElementById("transportForm");
    const transportTypeInput = document.getElementById("transportType");
    const transportNumberInput = document.getElementById("transportNumber");
    const transportRouteInput = document.getElementById("transportRoute");
    const addBtn = document.getElementById("addTransport");
    const modifyBtn = document.getElementById("modifyTransport");
    const deleteBtn = document.getElementById("deleteTransport");
    const transportTableBody = document.querySelector("#transportTable tbody");
  
    // Vom stoca transporturile într-un array (în practică, datele vin dintr-o bază de date)
    let transports = [];
    let selectedIndex = -1;
  
    function renderTable() {
      transportTableBody.innerHTML = "";
      transports.forEach((transport, index) => {
        // Rândul principal
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${transport.type}</td>
          <td>${transport.number}</td>
          <td>${transport.route}</td>
          <td><button class="accordion-button" data-index="${index}">Vezi stații</button></td>
        `;
        tr.addEventListener("click", function(e) {
          // Nu setăm formularul dacă s-a făcut click pe butonul de acordeon
          if (e.target.classList.contains("accordion-button")) return;
          selectedIndex = index;
          // Pentru tipul de transport, setăm valoarea în formular
          // Presupunem că valorile din select sunt "autobuz" și "tramvai"
          transportTypeInput.value = transport.type.toLowerCase() === "autobuz" ? "autobuz" : "tramvai";
          transportNumberInput.value = transport.number;
          transportRouteInput.value = transport.route;
        });
        transportTableBody.appendChild(tr);
  
        // Rândul pentru acordeon (accordion)
        const accordionTr = document.createElement("tr");
        accordionTr.classList.add("accordion-content");
        accordionTr.dataset.index = index;
        // Inițial, ascundem acordéonul (prin CSS ar trebui să fie "display: none")
        accordionTr.style.display = "none";
        const td = document.createElement("td");
        td.colSpan = 4;
        // Descompunem traseul (stringul) într-un array de stații
        const stations = transport.route.split(",").map(s => s.trim()).filter(s => s !== "");
        td.innerHTML = `<strong>Stații:</strong><br>${stations.join("<br>")}`;
        accordionTr.appendChild(td);
        transportTableBody.appendChild(accordionTr);
      });
  
      // Adăugăm eveniment pentru butoanele de acordeon
      const accordionButtons = document.querySelectorAll(".accordion-button");
      accordionButtons.forEach(button => {
        button.addEventListener("click", function(e) {
          e.stopPropagation();
          const index = this.dataset.index;
          const accordionRow = document.querySelector(`tr.accordion-content[data-index="${index}"]`);
          // Folosim window.getComputedStyle pentru a determina starea curentă
          if (window.getComputedStyle(accordionRow).display === "none") {
            accordionRow.style.display = "table-row";
            this.textContent = "Ascunde stații";
          } else {
            accordionRow.style.display = "none";
            this.textContent = "Vezi stații";
          }
        });
      });
      console.log("Tabel actualizat:", transports);
    }
  
    addBtn.addEventListener("click", function() {
      const type = transportTypeInput.value;
      const number = transportNumberInput.value.trim();
      const route = transportRouteInput.value.trim();
  
      if (!number || !route) {
        alert("Te rog completează toate câmpurile.");
        return;
      }
  
      // Salvăm datele. Valorile din select sunt "autobuz" sau "tramvai"
      transports.push({ type: type === "autobuz" ? "Autobuz" : "Tramvai", number, route });
      renderTable();
      transportForm.reset();
      selectedIndex = -1;
    });
  
    modifyBtn.addEventListener("click", function() {
      if (selectedIndex === -1) {
        alert("Selectează un transport din listă pentru modificare.");
        return;
      }
      const type = transportTypeInput.value;
      const number = transportNumberInput.value.trim();
      const route = transportRouteInput.value.trim();
  
      if (!number || !route) {
        alert("Te rog completează toate câmpurile.");
        return;
      }
  
      transports[selectedIndex] = { type: type === "autobuz" ? "Autobuz" : "Tramvai", number, route };
      renderTable();
      transportForm.reset();
      selectedIndex = -1;
    });
  
    deleteBtn.addEventListener("click", function() {
      if (selectedIndex === -1) {
        alert("Selectează un transport din listă pentru ștergere.");
        return;
      }
      transports.splice(selectedIndex, 1);
      renderTable();
      transportForm.reset();
      selectedIndex = -1;
    });
  });
  