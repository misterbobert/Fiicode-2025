<?php
session_start();
include 'db.php';  // Conectează-te la baza de date

// Verifică dacă utilizatorul este logat ca admin
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Verifică dacă sunt trimise datele prin POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $nr_transport = $_POST['nr_transport'];
    $statii = $_POST['statii'];

    // Actualizează ruta în baza de date
    $sql = "UPDATE rute SET nr_transport='$nr_transport', statii='$statii' WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "Rută modificată cu succes!";
    } else {
        echo "Eroare: " . $conn->error;
    }
}

$conn->close();  // Închide conexiunea
?>
