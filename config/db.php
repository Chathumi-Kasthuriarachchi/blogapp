<?php
// Include env.php to load .env values
require_once __DIR__ . '/env.php';

// Get values from $_ENV
$host     = $_ENV['DB_HOST'] ?? 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? '';
$username = $_ENV['DB_USER'] ?? '';
$password = $_ENV['DB_PASS'] ?? '';

// ✅ Create PDO connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
