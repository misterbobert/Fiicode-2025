<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - UrbanFlow</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <h2>Autentificare Administrație Locală</h2>
      <form method="POST" action="admin-login.php">
        <div class="form-group">
          <label for="email">Utilizator</label>
          <input type="text" id="email" name="email" placeholder="Introdu utilizatorul" required>
        </div>
        <div class="form-group">
          <label for="password">Parolă</label>
          <input type="password" id="password" name="password" placeholder="Introdu parola" required>
        </div>
        <button type="submit" id="loginButton">Logare</button>
      </form>
      <?php if (isset($_GET['error'])): ?>
        <p class="error-msg">Utilizator sau parolă greșită.</p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
