<?php
session_start();
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Debug: afișează datele primite
error_log("Email primit: $email");
error_log("Parola primită: $password");

$conn = new mysqli("localhost", "root", "", "urbanflow");

if ($conn->connect_error) {
    die("Conexiune eșuată: " . $conn->connect_error);
}

// Verificăm utilizatorul după email
$stmt = $conn->prepare("SELECT id, email, institution, password FROM admin_users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
error_log("Număr de rezultate: " . $result->num_rows);
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    error_log("Row: " . print_r($row, true));
}


if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Verificăm parola hashuită
    if (password_verify($password, $row['password'])) {
        $_SESSION['admin'] = true;
        $_SESSION['admin_email'] = $row['email'];               // 👈 esențial pentru dashboard
        $_SESSION['admin_institution'] = $row['institution'];

        header("Location: dashboard.php");
        exit;
    }
}

// În caz de eroare
header("Location: admin.php?error=1");
exit;
