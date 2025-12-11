<?php
// User authentication handler
session_start();

if (isset($_POST["login"])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!$username || !$password) {
        header("Location: login.php?error=1");
        exit();
    }

    require 'connect.php';

    // Check users table
    $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check password - support both hashed and plaintext passwords
        $passwordValid = false;
        
        // Try password_verify first (for bcrypt hashes)
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        } 
        // Also support plaintext comparison for testing
        elseif ($password === $user['password']) {
            $passwordValid = true;
        }
        
        if ($passwordValid) {
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_admin'] = ($user['role'] == 'admin') ? true : false;
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: showuser.php");
            } elseif ($user['role'] == 'medewerker') {
                header("Location: medewerker.php");
            } else {
                header("Location: homep.php");
            }
            exit();
        } else {
            // Password doesn't match
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // User not found
        header("Location: login.php?error=1");
        exit();
    }
    
    $stmt->close();
    $conn->close();
}
?>