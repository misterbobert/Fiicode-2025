<?php
session_start();
include 'db.php';  // Conectează-te la baza de date

// Verifică dacă utilizatorul este logat ca admin
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Adaugă rută în baza de date
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nr_transport = $_POST['nr_transport'];
    $statii = $_POST['statii'];

    $sql = "INSERT INTO rute (nr_transport, statii) VALUES ('$nr_transport', '$statii')";

    if ($conn->query($sql) === TRUE) {
        echo "Rută adăugată cu succes!";
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Eroare: " . $conn->error;
    }
}

$conn->close();  // Închide conexiunea
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Adaugă Rută</title>
</head>
<body>

  <h2>Adaugă Rută Nouă</h2>
  <form action="add_route.php" method="POST">
    <label for="nr_transport">Nr. Transport:</label>
    <input type="text" name="nr_transport" required><br><br>
    <label for="statii">Stații (separate prin virgulă):</label><br>
    <textarea name="statii" required></textarea><br><br>
    <button type="submit">Adaugă Rută</button>
  </form>

</body>
</html>
