<?php
session_start();
require_once 'db.php';

$email = $_SESSION['email'] ?? '';

// Ștergere rută favorită
if (isset($_GET['sterge'])) {
  $id = intval($_GET['sterge']);
  $stmt = $conn->prepare("DELETE FROM favorite_rute WHERE id = ? AND email = ?");
  $stmt->bind_param("is", $id, $email);
  $stmt->execute();
  header("Location: account.php");
  exit;
}

// Salvare notificări / rută favorită
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $ruta = trim($_POST['ruta'] ?? '');
  $notificari = isset($_POST['notificari']) ? 1 : 0;

  // Update notificări pentru toate rutele userului
  $stmt = $conn->prepare("UPDATE favorite_rute SET notificari = ? WHERE email = ?");
  $stmt->bind_param("is", $notificari, $email);
  $stmt->execute();

  // Adaugă ruta doar dacă e completată și nu e duplicat
  if (!empty($ruta)) {
    $stmt = $conn->prepare("SELECT id FROM favorite_rute WHERE email = ? AND ruta = ?");
    $stmt->bind_param("ss", $email, $ruta);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
      $stmt = $conn->prepare("INSERT INTO favorite_rute (email, ruta, notificari) VALUES (?, ?, ?)");
      $stmt->bind_param("ssi", $email, $ruta, $notificari);
      $stmt->execute();
    }
  }

  header("Location: account.php");
  exit;
}

// Afișare rute favorite și stare notificări
$favorites = [];
$notificari_active = 0;

$stmt = $conn->prepare("SELECT id, ruta, notificari FROM favorite_rute WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $favorites[] = $row;
  if ($row['notificari']) $notificari_active = 1;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil - UrbanFlow</title>
  <link rel="stylesheet" href="styles.css">
  <style>
    .form-group { margin-bottom: 1rem; display: flex; flex-direction: column; }
    #rutaInputContainer { display: none; margin-top: 1rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    table, th, td { border: 1px solid #ccc; }
    th, td { padding: 8px; text-align: center; }
    .menu-toggle {
      display: none;
      background-color: transparent;
      border: none;
      font-size: 1.5rem;
      color: #fff;
      cursor: pointer;
      z-index: 1001;
    }
    @media (max-width: 768px) {
      .nav-container {
        position: relative;
      }
      nav ul {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        background-color: #2ecc71;
        width: 100%;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        z-index: 1000;
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transform: translateY(-10px);
        transition: all 0.3s ease;
      }
      nav ul.active {
        max-height: 200px;
        opacity: 1;
        transform: translateY(0);
      }
      nav ul li {
        margin: 0.5rem 1rem;
      }
      .menu-toggle {
        display: block;
      }
    }
  </style>
</head>
<body>
  <!-- HEADER -->
  <header class="top-nav">
    <div class="nav-container">
      <div class="logo">
        <h1>UrbanFlow</h1>
      </div>
      <button class="menu-toggle" id="menuToggle">☰</button>
      <nav>
        <ul id="navList">
          <li><a href="index.php">Acasă</a></li>
          <li><a href="#">Rute</a></li>
          <li><a href="#">Contact</a></li>
          <?php if ($email): ?>
            <li><a href="account.php">Contul Meu</a></li>
          <?php else: ?>
            <li><a href="register.html">Înregistrează-te</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <!-- MAIN -->
  <main>
    <div class="wide-card" style="padding: 2rem;">
      <div class="form-container">
        <h2 style="color: #2ecc71;">Profilul Meu</h2>
        <div class="form-group">
          <label>Email</label>
          <input type="text" value="<?php echo htmlspecialchars($email); ?>" readonly>
        </div>
        <div class="form-group">
          <label>Nume utilizator</label>
          <input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" readonly>
        </div>

        <!-- FORMULAR -->
        <form method="POST">
          <div class="form-group">
            <button type="button" id="toggleRuta">⭐ Adaugă la rute favorite</button>
          </div>

          <div id="rutaInputContainer">
            <input type="text" name="ruta" placeholder="Numărul rutei">
            <button type="submit">Salvează ruta</button>
          </div>

         
        </form>

        <!-- TABEL -->
        <?php if (!empty($favorites)): ?>
          <h3 style="margin-top: 2rem;">Rutele Mele Favorite</h3>
          <table>
            <tr>
              <th>Număr rută</th>
              <th>Acțiune</th>
            </tr>
            <?php foreach ($favorites as $fav): ?>
              <tr>
                <td><?php echo htmlspecialchars($fav['ruta']); ?></td>
                <td>
                  <a href="account.php?sterge=<?php echo $fav['id']; ?>" onclick="return confirm('Ești sigur că vrei să ștergi această rută?')">🗑️ Șterge</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 2rem;">
          <a href="logout.php">Deconectează-te</a>
        </p>
        <div class="form-group" style="margin-top: 1rem;">
          <table>
         <tr>   <td><label style="display: inline; align-items: center; gap: 8px;">
              <input type="checkbox" name="notificari" <?php echo $notificari_active ? 'checked' : ''; ?>>
              Primește notificări 
            </label>
            </td>
            </tr>
            </table>
          </div>
      </div>
      
    </div>
    
  </main>

  <!-- SCRIPT -->
  <script>
    document.getElementById("menuToggle").addEventListener("click", function () {
      document.getElementById("navList").classList.toggle("active");
    });

    document.getElementById("toggleRuta").addEventListener("click", function () {
      const inputContainer = document.getElementById("rutaInputContainer");
      inputContainer.style.display = inputContainer.style.display === "none" ? "block" : "none";
    });
  </script>
</body>
</html>
