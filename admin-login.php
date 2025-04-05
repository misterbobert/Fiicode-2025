<?php
session_start();

$utilizator = $_POST['username'] ?? '';
$parola     = $_POST['password'] ?? '';

$conn = new mysqli("localhost", "root", "", "urbanflow");

if ($conn->connect_error) {
    die("Conexiune eșuată: " . $conn->connect_error);
}

// Caută în tabela admini (sau cum se numește tabela cu administrații aprobate)
$stmt = $conn->prepare("SELECT id, nume_institutie, parola FROM admini WHERE nume_institutie = ?");
$stmt->bind_param("s", $utilizator);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();

    if ($parola === $row['parola']) { // Dacă nu e hashuită parola
        $_SESSION['admin'] = true;
        $_SESSION['admin_institution'] = $row['nume_institutie'];

        header("Location: dashboard.php");
        exit;
    }
}

header("Location: admin.php?error=1");
exit;
