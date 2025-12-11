<?php
// Create new user with any role (admin, medewerker, user/klant)
session_start();
require 'connect.php';

// Check if user is logged in as admin
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Show form if admin and not submitting
if($is_admin && !isset($_POST['create_user'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Maak Nieuwe Gebruiker</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                padding: 20px;
            }
            .form-container {
                max-width: 500px;
                margin: 0 auto;
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            h2 {
                text-align: center;
                margin-bottom: 20px;
                color: #4CAF50;
            }
            .form-group {
                margin-bottom: 15px;
            }
            label {
                display: block;
                margin-bottom: 5px;
                font-weight: bold;
                color: #333;
            }
            input[type="text"],
            input[type="password"],
            input[type="email"],
            select {
                width: 100%;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
                font-size: 14px;
            }
            input:focus,
            select:focus {
                outline: none;
                border-color: #4CAF50;
                box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
            }
            select {
                cursor: pointer;
            }
            .role-info {
                background-color: #f0f8ff;
                padding: 10px;
                border-left: 4px solid #2196F3;
                margin-top: 5px;
                font-size: 12px;
                color: #555;
                border-radius: 4px;
                display: none;
            }
            .button-group {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            .btn {
                flex: 1;
                padding: 12px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                transition: background-color 0.3s;
            }
            .btn-submit {
                background-color: #4CAF50;
                color: white;
            }
            .btn-submit:hover {
                background-color: #45a049;
            }
            .btn-back {
                background-color: #007BFF;
                color: white;
                text-decoration: none;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .btn-back:hover {
                background-color: #0056b3;
            }
            .nav-links {
                margin-top: 20px;
                text-align: center;
            }
            .nav-links a {
                color: #007BFF;
                text-decoration: none;
            }
            .nav-links a:hover {
                text-decoration: underline;
            }
        </style>
        <script>
            function updateRoleInfo() {
                const role = document.getElementById('role').value;
                const roleInfo = document.getElementById('role-info');
                
                const roleDescriptions = {
                    'admin': 'Admin: Volledige toegang tot user management en alle admin functies',
                    'medewerker': 'Medewerker: Kan alle reserveringen zien en beheren',
                    'user': 'Klant: Kan reserveringen maken en beheren'
                };
                
                if (role && roleDescriptions[role]) {
                    roleInfo.textContent = roleDescriptions[role];
                    roleInfo.style.display = 'block';
                } else {
                    roleInfo.style.display = 'none';
                }
            }
        </script>
    </head>
    <body>
        <div class="form-container">
            <h2> Maak Nieuwe Gebruiker</h2>
            <form action="createadmin.php" method="post">
                <div class="form-group">
                    <label for="username">Gebruikersnaam:</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Rol:</label>
                    <select id="role" name="role" required onchange="updateRoleInfo()">
                        <option value="">-- Kies een rol --</option>
                        <option value="admin"> Admin</option>
                        <option value="medewerker"> Medewerker</option>
                        <option value="user">Klant</option>
                    </select>
                    <div id="role-info" class="role-info"></div>
                </div>

                <div class="button-group">
                    <button type="submit" name="create_user" value="1" class="btn btn-submit">✅ Gebruiker Aanmaken</button>
                    <a href="showuser.php" class="btn btn-back">← Terug</a>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Process user creation from form
if($is_admin && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    
    // Validate role
    if (!in_array($role, ['admin', 'medewerker', 'user'])) {
        $role = 'user'; // Default to user if invalid
    }
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Use prepared statement for security
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);
    
    if($stmt->execute()) {
        echo "<div style='text-align:center; padding:30px; background-color:#f0f8ff; border-radius:8px; max-width:500px; margin:20px auto; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>";
        echo "<h2 style='color:#4CAF50; margin-bottom:20px;'>Gebruiker Aangemaakt</h2>";
        echo "<div style='text-align:left; background-color:white; padding:15px; border-radius:4px; margin-bottom:20px;'>";
        echo "<p><strong>Gebruikersnaam:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        echo "<p><strong>Rol:</strong> " . htmlspecialchars($role) . "</p>";
        echo "<p><strong>Wachtwoord:</strong> ••••••• (verborgen)</p>";
        echo "</div>";
        echo "<p><a href='showuser.php' style='color:white; background-color:#4CAF50; padding:10px 20px; text-decoration:none; border-radius:4px; display:inline-block;'>← Terug naar User Management</a></p>";
        echo "</div>";
    } else {
        echo "<div style='text-align:center; padding:30px; background-color:#fff0f0; border-radius:8px; max-width:500px; margin:20px auto; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>";
        echo "<h2 style='color:#dc3545; margin-bottom:20px;'> Fout</h2>";
        echo "<p>Kon gebruiker niet aanmaken: " . htmlspecialchars($stmt->error) . "</p>";
        echo "<p><a href='createadmin.php' style='color:white; background-color:#f44336; padding:10px 20px; text-decoration:none; border-radius:4px; display:inline-block;'>↻ Opnieuw proberen</a></p>";
        echo "</div>";
    }
    $stmt->close();
    $conn->close();
    exit();
}

// If not admin, redirect to login
if (!$is_admin) {
    header("Location: login.php");
    exit();
}

$conn->close();
?>
