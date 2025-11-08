<?php
$host = 'localhost';
$dbname = 'blog_db';   // your real DB name here
$username = 'root';        // XAMPP default
$password = '';            // XAMPP default (empty)

// ✅ Create PDO connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
