<?php
// config/db.php

$host = 'localhost';
$dbname = 'library_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Enable exceptions for errors
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Start Session with extended lifetime (24 hours)
if (session_status() === PHP_SESSION_NONE) {
    // Set session lifetime to 24 hours
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params([
        'lifetime' => 86400,     // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => false,       // Set to true if using HTTPS
        'httponly' => true,      // Prevent JavaScript access
        'samesite' => 'Lax'      // CSRF protection
    ]);
    session_start();
}

/**
 * Helper to return JSON response and exit
 */
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
