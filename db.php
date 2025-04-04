<?php
// Activăm afișarea erorilor – foarte important pentru debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "urbanflow"; // Baza de date dorită

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Eroare conexiune DB: " . $conn->connect_error);
}
?>
