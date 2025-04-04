<?php
session_start();
include 'db.php';  // Conectează-te la baza de date

// Verifică dacă utilizatorul este logat ca admin
if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

// Verifică dacă sunt trimise date prin POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $stationName = $_POST['stationName'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    // Inserăm noua stație în baza de date
    $sql = "INSERT INTO statiuni (name, latitude, longitude) VALUES ('$stationName', '$lat', '$lng')";

    if ($conn->query($sql) === TRUE) {
        echo "Stația a fost adăugată cu succes!";
    } else {
        echo "Eroare: " . $conn->error;
    }
}

$conn->close();  // Închide conexiunea
?>
