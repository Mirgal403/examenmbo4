<?php
// Start session before any output
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: bold;
        }
        input[type="email"], /* Changed to email */
        input[type="password"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 0.6rem;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .error-message {
            color: red;
            margin-bottom: 1rem;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
        .register-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php
        if(isset($_GET['error']) && $_GET['error'] == '1') {
            echo '<div class="error-message">Invalid email or password</div>';
        }
        if(isset($_GET['registered']) && $_GET['registered'] == '1') {
            echo '<div class="success-message" style="color: green; text-align: center; margin-bottom: 1rem;">Registration successful! Please login.</div>';
        }
        ?>
        
        <form action="checkuser.php" method="post">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required placeholder="name@example.com" />
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />
            
            <button type="submit" name="login">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>