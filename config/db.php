<?php
// Prevent direct access to config file
if (count(get_included_files()) === 1) {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// Database Configuration Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'luxe_commerce');

// Initialize Session globally if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Establish PDO Connection with secure attributes
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    // In production, log error and show friendly message.
    // For development, we show the message directly.
    die("Database Connection Failed: " . $e->getMessage());
}

// Helper function to sanitize user inputs
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>
