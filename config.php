<?php
session_start();

$host = 'localhost';
$dbname = 'pengundian_badminton';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function isLoggedIn() {
    return isset($_SESSION['id_pengguna']);
}

function isAdmin() {
    return isset($_SESSION['peranan']) && $_SESSION['peranan'] === 'admin';
}
?>
