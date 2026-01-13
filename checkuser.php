<?php
session_start();

if (isset($_POST["login"])){
    // Sanitize user input to prevent XSS
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; 

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Email and password are required";
        header("Location: login.php");
        exit();
    }

    require 'connect.php';

    // Try to login as medewerker first
    $stmt = $conn->prepare("SELECT medewerker_id as id, email, naam as username, wachtwoord_hash, rol as role FROM medewerker WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // If not a medewerker, try to login as klant
        $stmt = $conn->prepare("SELECT klant_id as id, email, CONCAT(voornaam, ' ', achternaam) as username, wachtwoord_hash FROM klant WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
    }

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['wachtwoord_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'klant';
            $_SESSION['is_admin'] = ($_SESSION['role'] == 'medewerker' || $_SESSION['role'] == 'admin');

            if ($_SESSION['is_admin']) {
                header("Location: medewerkers_dashboard.php");
            } else {
                header("Location: klanten_dashboard.php");
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['login_error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>