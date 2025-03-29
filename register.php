<?php
$servername = "localhost";     // sau 127.0.0.1
$username = "root";           // utilizatorul MySQL implicit
$password = "";               // lasă gol dacă nu ai parolă setată
$dbname = "urbanflow";        // numele bazei de date

// Creează conexiunea
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifică conexiunea
if ($conn->connect_error) {
  die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Preia datele din formular
$email = $_POST['email'];
$user = $_POST['username'];
$pass = $_POST['password'];
$confirm = $_POST['confirmPassword'];

// Validare simplă: verifică dacă parolele coincid
if ($pass !== $confirm) {
  die("Parolele nu se potrivesc!");
}

// Hash parolă (recomandat pentru securitate)
$hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

// Introducere în baza de date
$sql = "INSERT INTO utilizatori (email, username, parola) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $email, $user, $hashed_pass);

if ($stmt->execute()) {
  echo "Înregistrare reușită!";
} else {
  echo "Eroare: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
