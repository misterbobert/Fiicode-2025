<?php
session_start();
include 'db.php';  // Conectează-te la baza de date

// Verifică dacă utilizatorul este logat ca admin
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Șterge ruta din baza de date
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM rute WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "Rută ștearsă cu succes!";
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Eroare: " . $conn->error;
    }
}

$conn->close();  // Închide conexiunea
?>
