<?php
// User authentication handler
session_start();
if (isset($_POST["login"])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!$username || !$password) {
        $_SESSION['login_error'] = "Username and password are required";
        header("Location: login.php?error=1");
        exit();
    }

    require 'connect.php';

    // Try to login as medewerker first (using naam as username)
    $stmt = $conn->prepare("SELECT medewerker_id as id, email, naam as username, wachtwoord_hash, rol as role FROM medewerker WHERE naam = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Try to login as klant (search by voornaam + achternaam)
        // Split username by space to get voornaam and achternaam
        $parts = explode(' ', $username, 2);
        if (count($parts) == 2) {
            $voornaam = $parts[0];
            $achternaam = $parts[1];
            $stmt = $conn->prepare("SELECT klant_id as id, email, CONCAT(voornaam, ' ', achternaam) as username, wachtwoord_hash FROM klant WHERE voornaam = ? AND achternaam = ?");
            $stmt->bind_param("ss", $voornaam, $achternaam);
        } else {
            // Single name - try to find klant by voornaam only
            $stmt = $conn->prepare("SELECT klant_id as id, email, CONCAT(voornaam, ' ', achternaam) as username, wachtwoord_hash FROM klant WHERE voornaam = ?");
            $stmt->bind_param("s", $username);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['wachtwoord_hash'])) {
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'klant';
            $_SESSION['is_admin'] = ($user['role'] ?? 'klant') == 'medewerker' ? true : false;
            
            // Redirect to appropriate page based on role
            if ($_SESSION['is_admin']) {
                header("Location: showuser.php");
            } else {
                header("Location: homep.php");
            }
            exit();
        } else {
            // Password doesn't match
            $_SESSION['login_error'] = "Invalid username or password";
            header("Location: login.php?error=1");
            exit();
        }
    } else {
        // User not found
        $_SESSION['login_error'] = "Invalid username or password";
        header("Location: login.php?error=1");
        exit();
    }
    
    $stmt->close();
}
?>