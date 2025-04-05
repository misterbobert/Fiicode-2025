<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Înregistrare Administrație - UrbanFlow</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h2>Înregistrare Administrație Locală</h2>

      <form method="POST" action="admin-enroll.php">
        <div class="form-group">
          <label for="institution">Nume instituție</label>
          <input type="text" id="institution" name="institution" placeholder="Ex: Primăria Pașcani" required>
        </div> 
        <div class="form-group">
  <label for="judet">Județ</label>
  <select id="judet" name="judet" required>
    <option value="">Alege un județ</option>
    <option>Alba</option>
    <option>Arad</option>
    <option>Argeș</option>
    <option>Bacău</option>
    <option>Bihor</option>
    <option>Bistrița-Năsăud</option>
    <option>Botoșani</option>
    <option>Brașov</option>
    <option>Brăila</option>
    <option>București</option>
    <option>Buzău</option>
    <option>Caraș-Severin</option>
    <option>Călărași</option>
    <option>Cluj</option>
    <option>Constanța</option>
    <option>Covasna</option>
    <option>Dâmbovița</option>
    <option>Dolj</option>
    <option>Galați</option>
    <option>Giurgiu</option>
    <option>Gorj</option>
    <option>Harghita</option>
    <option>Hunedoara</option>
    <option>Ialomița</option>
    <option>Iași</option>
    <option>Ilfov</option>
    <option>Maramureș</option>
    <option>Mehedinți</option>
    <option>Mureș</option>
    <option>Neamț</option>
    <option>Olt</option>
    <option>Prahova</option>
    <option>Sălaj</option>
    <option>Satu Mare</option>
    <option>Sibiu</option>
    <option>Suceava</option>
    <option>Teleorman</option>
    <option>Timiș</option>
    <option>Tulcea</option>
    <option>Vâlcea</option>
    <option>Vaslui</option>
    <option>Vrancea</option>
  </select>
</div>


<div class="form-group">
  <label for="oras">Oraș</label>
  <select id="oras" name="oras" required>
    <option value="">Alege mai întâi un județ</option>
  </select>
</div>


        <div class="form-group">
          <label for="email">Email oficial</label>
          <input type="email" id="email" name="email" placeholder="contact@primarie.ro" required>
        </div>

        <div class="form-group">
          <label for="password">Parolă</label>
          <input type="password" id="password" name="password" placeholder="Introdu o parolă" required>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirmă parola</label>
          <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Reintrodu parola" required>
        </div>

        <button type="submit" id="registerButton">Înregistrează administrația</button>
      </form>

      <?php if (isset($_GET['error'])): ?>
        <p class="error-msg">Eroare: <?php echo htmlspecialchars($_GET['error']); ?></p>
      <?php endif; ?>

      <?php if (isset($_GET['success'])): ?>
        <p class="success-msg">Înregistrare reușită! Puteți <a href="admin.php">loga aici</a>.</p>
      <?php endif; ?>
    </div>
  </div>

  <script>
  const orase = {
    "Alba": ["Alba Iulia", "Aiud", "Blaj", "Cugir", "Ocna Mureș", "Sebeș", "Zlatna"],
    "Arad": ["Arad", "Chișineu-Criș", "Curtici", "Ineu", "Lipova", "Nădlac", "Pâncota", "Pecica", "Sântana"],
    "Argeș": ["Pitești", "Câmpulung", "Curtea de Argeș", "Mioveni", "Topoloveni", "Costești"],
    "Bacău": ["Bacău", "Moinești", "Onești", "Comănești", "Dărmănești", "Slănic-Moldova", "Târgu Ocna"],
    "Bihor": ["Oradea", "Aleșd", "Beiuș", "Marghita", "Salonta", "Săcueni", "Valea lui Mihai"],
    "Bistrița-Năsăud": ["Bistrița", "Beclean", "Năsăud", "Sângeorz-Băi"],
    "Botoșani": ["Botoșani", "Dorohoi", "Darabani", "Săveni"],
    "Brașov": ["Brașov", "Codlea", "Făgăraș", "Predeal", "Râșnov", "Săcele", "Victoria", "Zărnești"],
    "Brăila": ["Brăila", "Ianca", "Însurăței", "Făurei"],
    "București": ["București"],
    "Buzău": ["Buzău", "Nehoiu", "Pătârlagele", "Pogoanele", "Râmnicu Sărat"],
    "Caraș-Severin": ["Reșița", "Caransebeș", "Anina", "Bocșa", "Moldova Nouă", "Oravița", "Oțelu Roșu"],
    "Călărași": ["Călărași", "Oltenița"],
    "Cluj": ["Cluj-Napoca", "Turda", "Câmpia Turzii", "Dej", "Gherla", "Huedin"],
    "Constanța": ["Constanța", "Mangalia", "Medgidia", "Năvodari", "Ovidiu", "Eforie", "Cernavodă", "Hârșova"],
    "Covasna": ["Sfântu Gheorghe", "Târgu Secuiesc", "Covasna"],
    "Dâmbovița": ["Târgoviște", "Fieni", "Găești", "Moreni", "Pucioasa", "Titu"],
    "Dolj": ["Craiova", "Băilești", "Bechet", "Calafat", "Filiași", "Segarcea"],
    "Galați": ["Galați", "Tecuci", "Târgu Bujor", "Berești"],
    "Giurgiu": ["Giurgiu", "Bolintin-Vale", "Mihăilești"],
    "Gorj": ["Târgu Jiu", "Motru", "Novaci", "Rovinari", "Tismana", "Țicleni", "Turceni"],
    "Harghita": ["Miercurea Ciuc", "Gheorgheni", "Odorheiu Secuiesc", "Toplița", "Cristuru Secuiesc"],
    "Hunedoara": ["Deva", "Hunedoara", "Brad", "Lupeni", "Orăștie", "Petroșani", "Simeria", "Vulcan"],
    "Ialomița": ["Slobozia", "Fetești", "Urziceni", "Amara", "Căzănești"],
    "Iași": ["Iași", "Pașcani", "Târgu Frumos", "Hârlău", "Podu Iloaiei"],
    "Ilfov": ["Buftea", "Pantelimon", "Popești-Leordeni", "Măgurele", "Voluntari", "Chitila"],
    "Maramureș": ["Baia Mare", "Sighetu Marmației", "Borșa", "Cavnic", "Dragomirești", "Seini", "Târgu Lăpuș"],
    "Mehedinți": ["Drobeta-Turnu Severin", "Orșova", "Strehaia", "Vânju Mare"],
    "Mureș": ["Târgu Mureș", "Reghin", "Sighișoara", "Luduș", "Iernut", "Sovata"],
    "Neamț": ["Piatra Neamț", "Roman", "Târgu Neamț", "Bicaz"],
    "Olt": ["Slatina", "Balș", "Caracal", "Corabia", "Drăgănești-Olt", "Piatra-Olt", "Scornicești"],
    "Prahova": ["Ploiești", "Câmpina", "Băicoi", "Boldești-Scăeni", "Mizil", "Sinaia", "Vălenii de Munte"],
    "Sălaj": ["Zalău", "Șimleu Silvaniei", "Jibou"],
    "Satu Mare": ["Satu Mare", "Carei", "Ardud", "Negrești-Oaș", "Tășnad"],
    "Sibiu": ["Sibiu", "Agnita", "Avrig", "Cisnădie", "Copșa Mică", "Dumbrăveni", "Mediaș", "Tălmaciu"],
    "Suceava": ["Suceava", "Fălticeni", "Gura Humorului", "Câmpulung Moldovenesc", "Rădăuți", "Siret", "Vatra Dornei"],
    "Teleorman": ["Alexandria", "Roșiorii de Vede", "Turnu Măgurele", "Videle", "Zimnicea"],
    "Timiș": ["Timișoara", "Lugoj", "Sânnicolau Mare", "Jimbolia", "Buziaș", "Deta", "Făget", "Recaș"],
    "Tulcea": ["Tulcea", "Babadag", "Isaccea", "Măcin", "Sulina"],
    "Vâlcea": ["Râmnicu Vâlcea", "Băbeni", "Bălcești", "Brezoi", "Călimănești", "Drăgășani", "Horezu"],
    "Vaslui": ["Vaslui", "Bârlad", "Huși", "Murgeni"],
    "Vrancea": ["Focșani", "Adjud", "Odobești", "Mărășești", "Panciu"]
  };

  document.getElementById("judet").addEventListener("change", function() {
    const orasSelect = document.getElementById("oras");
    const selectedJudet = this.value;

    orasSelect.innerHTML = '<option value="">Alege un oraș</option>';

    if (orase[selectedJudet]) {
      orase[selectedJudet].forEach(function(oras) {
        const opt = document.createElement("option");
        opt.value = oras;
        opt.textContent = oras;
        orasSelect.appendChild(opt);
      });
    }
  });
</script>


</body>

</html>
