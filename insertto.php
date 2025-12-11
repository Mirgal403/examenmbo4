<?php
// Handle user registration - saves to users table
session_start();
require 'connect.php';

if (isset($_POST['signup'])) {
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        header("Location: register.php?error=1");
        exit();
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if email or username already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    if (!$stmt) {
        header("Location: register.php?error=1");
        exit();
    }
    
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Email or username already exists
        $stmt->close();
        header("Location: register.php?error=1");
        exit();
    }
    $stmt->close();
    
    // Insert new user with role 'user' (klant)
    $role = 'user';
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        header("Location: register.php?error=1");
        exit();
    }
    
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: login.php?registered=1");
        exit();
    } else {
        // Debug: show error
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        echo "Registration Error: " . htmlspecialchars($error);
        echo "<br><a href='register.php'>Back to Register</a>";
        exit();
    }
}

$conn->close();
?>
