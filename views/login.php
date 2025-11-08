<?php
// Handle user login
require_once '../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        // Redirect with error if fields are empty
        header("Location: ../index.php?error=empty_fields");
        exit();
    }

    // Check user by email
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        //  Login success
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // Remember me: set cookies for 7 days
        if ($remember) {
            setcookie("user_email", $email, time() + (7 * 24 * 60 * 60), "/");
        }

        // Redirect to blogs page
        header("Location: blogs.php");
        exit();
    } else {
        //  Invalid credentials
        header("Location: ../index.php?error=invalid");
        exit();
    }
} else {
    // If directly accessed (not POST), go back home
    header("Location: ../index.php");
    exit();
}
