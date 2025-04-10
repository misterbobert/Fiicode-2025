<?php
session_start();

// Preluăm datele din formular folosind câmpurile "email" și "password"
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Debug: afișăm datele primite în log (doar pentru dezvoltare)
error_log("Post data: " . print_r($_POST, true));

// Verificăm dacă emailul și parola nu sunt goale
if (empty($email) || empty($password)) {
    error_log("Email sau parolă lipsă.");
    header("Location: admin.php?error=1");
    exit;
}

// Conectare la baza de date
$conn = new mysqli("localhost", "root", "", "urbanflow");
if ($conn->connect_error) {
    error_log("Conexiune eșuată: " . $conn->connect_error);
    header("Location: admin.php?error=1");
    exit;
}

// Pregătim interogarea pentru a selecta utilizatorul folosind coloana email
$stmt = $conn->prepare("SELECT id, email, institution, password FROM admin_users WHERE email = ?");
if (!$stmt) {
    error_log("Eroare pregătire interogare: " . $conn->error);
    header("Location: admin.php?error=1");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    error_log("Utilizator găsit: " . print_r($row, true));
    
    // Verificăm parola folosind password_verify() pentru a compara cu hash-ul stocat
    if (password_verify($password, $row['password'])) {
        // Setăm variabilele de sesiune pentru utilizator
        $_SESSION['admin'] = true;
        $_SESSION['admin_email'] = $row['email'];
        $_SESSION['admin_institution'] = $row['institution'];
        
        header("Location: dashboard.php");
        exit;
    } else {
        error_log("Parola introdusă nu corespunde pentru email-ul: $email");
    }
} else {
    error_log("Niciun utilizator găsit pentru email: $email");
}

$stmt->close();
$conn->close();

// În caz de eșec, redirecționează către pagina de login cu eroare
header("Location: admin.php?error=1");
exit;
?>
