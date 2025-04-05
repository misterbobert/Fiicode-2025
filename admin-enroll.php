<?php
// Conectare la baza de date
$host = 'localhost';
$db = 'urbanflow';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}

// Preluare date din formular
$institution = $_POST['institution'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirmPassword = $_POST['confirmPassword'];
$judet = $_POST['judet'];
$oras = $_POST['oras'];

// Validare parole
if ($password !== $confirmPassword) {
    header("Location: enroll.php?error=Parolele nu coincid");
    exit();
}

// Verificare existență în waiting_admins sau admini
$stmt = $conn->prepare("SELECT id FROM waiting_admins WHERE institution = ? OR email = ?
                        UNION
                        SELECT id FROM admini WHERE institution = ? OR email = ?");
$stmt->bind_param("ssss", $institution, $email, $institution, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: enroll.php?error=Instituția sau emailul există deja");
    exit();
}
$stmt->close();

// Criptare parolă
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Inserare în waiting_admins
$stmt = $conn->prepare("INSERT INTO waiting_admins (institution, email, password, judet, oras) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $institution, $email, $hashedPassword, $judet, $oras);

if ($stmt->execute()) {
    header("Location: enroll.php?success=1");
} else {
    header("Location: enroll.php?error=Eroare la înregistrare");
}

$stmt->close();
$conn->close();
?>
