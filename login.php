<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bowling Brooklyn</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f4f4f4;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            width: 100%;
            background: white;
            border-bottom: 1px solid #dddddd;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 40px;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            color: #111111;
        }

        .btn-register {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 500;
            border-radius: 2px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-register:hover {
            background: #005fd1;
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #111;
        }

        label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.6rem;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 500;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .register-link {
            text-align: center;
            margin-top: 1rem;
            color: #555;
        }

        .register-link a {
            color: #007BFF;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            header {
                padding: 15px 20px;
            }

            .navbar-brand {
                font-size: 18px;
            }

            .login-container {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="navbar-brand">Bowling Brooklyn</div>
        <a href="register.php" class="btn-register">Registreren</a>
    </header>

    <div class="main-content">
        <div class="login-container">
            <h2>Login</h2>
            <?php
            if(isset($_GET['error']) && $_GET['error'] == '1') {
                echo '<div class="error-message">Ongeldige gebruikersnaam of wachtwoord</div>';
            }
            if(isset($_GET['registered']) && $_GET['registered'] == '1') {
                echo '<div class="success-message">Registratie succesvol! Je kunt nu inloggen.</div>';
            }
            ?>
            
            <form action="checkuser.php" method="post">
                <label for="username">Email</label>
                <input type="text" id="username" name="email" required />
                
                <label for="password">Wachtwoord</label>
                <input type="password" id="password" name="password" required />
                
                <button type="submit" name="login">Login</button>
            </form>
            
            <div class="register-link">
                Geen account? <a href="register.php">Registreer hier</a>
            </div>
        </div>
    </div>
</body>
</html>