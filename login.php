<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "urbanflow";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Conexiunea a eșuat: " . $conn->connect_error);
}

$email = $_POST['email'];
$parola = $_POST['password'];

// Caută utilizatorul după email
$sql = "SELECT username, parola FROM utilizatori WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
  $stmt->bind_result($username, $parola_hash);
  $stmt->fetch();

  if (password_verify($parola, $parola_hash)) {
    // Salvăm datele în sesiune
    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;

    // Redirecționare spre pagina de cont
    header("Location: account.php");
    exit();
  } else {
    echo "Parolă incorectă.";
  }
} else {
  echo "Utilizatorul nu a fost găsit.";
}

$stmt->close();
$conn->close();
?>
