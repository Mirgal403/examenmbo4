<?php
// Handles new user registration for both medewerker and klant
session_start();

require 'connect.php';

if (isset($_POST['signup'])) {
    $userType = $_POST['userType'] ?? 'klant'; // 'klant' or 'medewerker'
    
    // Check if email and password are set
    if (!isset($_POST['email']) || !isset($_POST['password'])) {
        header("Location: register.php?error=1");
        exit();
    }
    
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    if ($userType === 'medewerker') {
        // Check if naam is set
        if (!isset($_POST['naam'])) {
            header("Location: register.php?error=1");
            exit();
        }
        
        $naam = $_POST['naam'];
        
        // Check if email already exists in medewerker table
        $stmt = $conn->prepare("SELECT * FROM medewerker WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Email already exists
            header("Location: register.php?error=1");
            exit();
        }
        
        // Insert new medewerker with 'medewerker' role
        $stmt = $conn->prepare("INSERT INTO medewerker (naam, email, wachtwoord_hash, rol) VALUES (?, ?, ?, 'medewerker')");
        $stmt->bind_param("sss", $naam, $email, $hashedPassword);
        
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
        } else {
            header("Location: register.php?error=1");
        }
        
    } else {
        // klant registration - check if voornaam and achternaam are set
        if (!isset($_POST['voornaam']) || !isset($_POST['achternaam'])) {
            header("Location: register.php?error=1");
            exit();
        }
        
        $voornaam = $_POST['voornaam'];
        $achternaam = $_POST['achternaam'];
        $telefoon = $_POST['telefoon'] ?? null;
        
        // Check if email already exists in klant table
        $stmt = $conn->prepare("SELECT * FROM klant WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Email already exists
            header("Location: register.php?error=1");
            exit();
        }
        
        // Insert new klant
        $stmt = $conn->prepare("INSERT INTO klant (voornaam, achternaam, email, wachtwoord_hash, telefoon) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $voornaam, $achternaam, $email, $hashedPassword, $telefoon);
        
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
        } else {
            header("Location: register.php?error=1");
        }
    }
    
    $stmt->close();
}

$conn->close();
?>