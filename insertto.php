<?php
session_start();
require 'connect.php';

if (isset($_POST['signup'])) {
    $userType = $_POST['userType'] ?? 'klant';
    
    if (empty($_POST['email']) || empty($_POST['password'])) {
        header("Location: register.php?error=empty_fields");
        exit();
    }
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=invalid_email");
        exit();
    }

    $password = $_POST['password'];
    if (strlen($password) < 4) {
        header("Location: register.php?error=password_short");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    if ($userType === 'medewerker') {
        if (empty($_POST['naam'])) {
            header("Location: register.php?error=empty_fields");
            exit();
        }
        $naam = trim($_POST['naam']);
        
        // Check for Duplicate Email
        $stmt = $conn->prepare("SELECT medewerker_id FROM medewerker WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            header("Location: register.php?error=email_taken");
            exit();
        }
        $stmt->close();
        
        // Insert Medewerker
        $stmt = $conn->prepare("INSERT INTO medewerker (naam, email, wachtwoord_hash, rol) VALUES (?, ?, ?, 'medewerker')");
        $stmt->bind_param("sss", $naam, $email, $hashedPassword);
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            header("Location: register.php?error=system_error");
            exit();
        }
        

    // LOGIC FOR KLANT
  
    } else {
        if (empty($_POST['voornaam']) || empty($_POST['achternaam'])) {
        }
        
        $voornaam = trim($_POST['voornaam']);
        $achternaam = trim($_POST['achternaam']);
        $telefoon = !empty($_POST['telefoon']) ? trim($_POST['telefoon']) : null;
        
        // Check for Duplicate Email
        $stmt = $conn->prepare("SELECT klant_id FROM klant WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            header("Location: register.php?error=email_taken");
            exit();
        }
        $stmt->close();
        
        // Insert Klant
        $stmt = $conn->prepare("INSERT INTO klant (voornaam, achternaam, email, wachtwoord_hash, telefoon) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $voornaam, $achternaam, $email, $hashedPassword, $telefoon);
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            header("Location: register.php?error=system_error");
            exit();
        }
    }
    
    $conn->close();
} else {
    header("Location: register.php");
    exit();
}
?>