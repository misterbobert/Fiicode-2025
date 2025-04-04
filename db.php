<?php
$servername = "localhost";
$username = "root";  // Modifică cu utilizatorul tău MySQL
$password = "";      // Modifică cu parola ta MySQL
$dbname = "transport_db";  // Numele bazei de date

// Creare conexiune
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifică conexiunea
if ($conn->connect_error) {
    die("Conexiunea a eșuat: " . $conn->connect_error);
}
?>
