<?php
session_start();

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Exemplu hardcoded:
if ($username === 'admin' && $password === 'admin123') {
    $_SESSION['admin'] = true;
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: admin.php?error=1");

    exit;
}
