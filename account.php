<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Autentificare - UrbanFlow</title>
  <link rel="stylesheet" href="styles.css">
  <style>
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
  <!-- HEADER: Meniu sus -->
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
          <?php if (isset($_SESSION['email'])): ?>
            <li><a href="account.php">Contul Meu</a></li>
          <?php else: ?>
            <li><a href="register.html">Înregistrează-te</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </header>

  <!-- MAIN: Informații cont utilizator -->
  <main>
    <div class="wide-card" style="justify-content: center; align-items: center; padding: 2rem;">
      <div class="form-container">
        <h2>Profilul Meu</h2>
        <div class="form-group">
          <label>Email</label>
          <input type="text" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" readonly>
        </div>
        <div class="form-group">
          <label>Nume utilizator</label>
          <input type="text" value="<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>" readonly>
        </div>
        <p style="text-align: center; margin-top: 1.5rem;">
          <a href="logout.php">Deconectează-te</a>
        </p>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer>
    <p>&copy; 2025 UrbanFlow | <a href="#">Termeni și condiții</a> | <a href="#">Politica de confidențialitate</a></p>
  </footer>

  <script>
    document.getElementById("menuToggle").addEventListener("click", function () {
      document.getElementById("navList").classList.toggle("active");
    });
  </script>
</body>
</html>
