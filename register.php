<?php
// Registration page
?>
<?php
// Registration page - handles user registration
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
        .register-container {
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
        input[type="text"],
        input[type="password"],
        input[type="email"],
        select {
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
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        .login-link a {
            color: #007BFF;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Register</h2>
        
        <?php
        if(isset($_GET['error']) && $_GET['error'] == '1') {
            echo '<div class="error-message">Registration failed. Please try again.</div>';
        }
        ?>
        
        <form action="insertto.php" method="post">
            <label for="userType">Register as:</label>
            <select id="userType" name="userType" required onchange="updateForm()">
                <option value="klant">Klant</option>
                <option value="medewerker">Medewerker</option>
            </select>
            
            <div id="klant-fields">
                <label for="voornaam">First Name</label>
                <input type="text" id="voornaam" name="voornaam" />
                
                <label for="achternaam">Last Name</label>
                <input type="text" id="achternaam" name="achternaam" />
                
                <label for="telefoon">Phone (optional)</label>
                <input type="text" id="telefoon" name="telefoon" />
            </div>
            
            <div id="medewerker-fields" style="display:none;">
                <label for="naam">Full Name</label>
                <input type="text" id="naam" name="naam" />
            </div>
            
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required />
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required />
            
            <button type="submit" name="signup">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
    
    <script>
        function updateForm() {
            const userType = document.getElementById('userType').value;
            const klantFields = document.getElementById('klant-fields');
            const medewerkerFields = document.getElementById('medewerker-fields');
            
            if (userType === 'klant') {
                klantFields.style.display = 'block';
                medewerkerFields.style.display = 'none';
                document.getElementById('voornaam').required = true;
                document.getElementById('achternaam').required = true;
                document.getElementById('naam').required = false;
            } else {
                klantFields.style.display = 'none';
                medewerkerFields.style.display = 'block';
                document.getElementById('voornaam').required = false;
                document.getElementById('achternaam').required = false;
                document.getElementById('naam').required = true;
            }
        }
    </script>
</body>
</html>