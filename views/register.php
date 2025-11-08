<?php
// views/register.php — Handle user registration
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        header("Location: ../index.php?error=empty_fields");
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        header("Location: ../index.php?error=email_exists");
        exit();
    }

    // Hash password before saving
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashedPassword])) {
        // Auto-login after registration
        $_SESSION['user_id'] = $conn->lastInsertId();
        $_SESSION['username'] = $username;

        // Redirect to blogs page
        header("Location: blogs.php");
        exit();
    } else {
        header("Location: ../index.php?error=failed");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
