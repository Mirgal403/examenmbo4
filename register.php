<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bowling Brooklyn</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            width: 350px;
        }
        h2 { text-align: center; margin-bottom: 1.5rem; color: #333; }
        label { display: block; margin-bottom: 0.3rem; font-weight: 600; font-size: 14px; color: #555; }
        
        input[type="text"],
        input[type="password"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Important for padding */
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover { background-color: #0056b3; }
        button:disabled {
            background-color: #a0cffc;
            cursor: not-allowed;
        }
        
        .login-link { text-align: center; margin-top: 1rem; font-size: 14px; }
        .login-link a { color: #007BFF; text-decoration: none; }
        .login-link a:hover { text-decoration: underline; }

        .privacy-section {
            font-size: 13px;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
        }
        .privacy-section input {
            margin-right: 8px;
            margin-top: 3px;
        }
        .privacy-section a {
            color: #007BFF;
            text-decoration: none;
        }
        .privacy-section a:hover {
            text-decoration: underline;
        }

        /* ALERT MESSAGES STYLING */
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            text-align: center;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Account Maken</h2>
        
   
        <?php
        if(isset($_GET['error'])) {
            $err = $_GET['error'];
            $msg = "";
            
            // Switch case to show specific messages
            switch($err) {
                case 'empty_fields':
                    $msg = "Vul a.u.b. alle verplichte velden in.";
                    break;
                case 'invalid_email':
                    $msg = "Dit e-mailadres is niet geldig.";
                    break;
                case 'password_short':
                    $msg = "Wachtwoord moet minimaal 4 tekens bevatten.";
                    break;
                case 'email_taken':
                    $msg = "Dit e-mailadres is al in gebruik. Probeer in te loggen.";
                    break;
                case 'system_error':
                    $msg = "Er is een systeemfout opgetreden. Probeer het later opnieuw.";
                    break;
                default:
                    $msg = "Er is iets misgegaan. Probeer het opnieuw.";
            }
            echo '<div class="alert alert-error">'.$msg.'</div>';
        }
        ?>

        <form action="insertto.php" method="post" id="register-form">
            <label for="userType">Ik ben een:</label>
            <select id="userType" name="userType" required onchange="updateForm()">
                <option value="klant">Klant</option>
                <option value="medewerker">Medewerker</option>
            </select>
            
            <div id="klant-fields">
                <label for="voornaam">Voornaam</label>
                <input type="text" id="voornaam" name="voornaam" />
                
                <label for="achternaam">Achternaam</label>
                <input type="text" id="achternaam" name="achternaam" />
                
                <label for="telefoon">Telefoonnummer (voor calamiteiten)</label>
                <input type="text" id="telefoon" name="telefoon" />
            </div>
            
            <div id="medewerker-fields" style="display:none;">
                <label for="naam">Volledige Naam</label>
                <input type="text" id="naam" name="naam" />
            </div>
            
            <label for="email">E-mailadres</label>
            <input type="email" id="email" name="email" required />
            
            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required />

            <div class="privacy-section">
                <input type="checkbox" id="privacy-accept" name="privacy-accept" required>
                <label for="privacy-accept">
                    Ik heb de <a href="privacyvoorwarden.html" target="_blank">privacyvoorwaarden</a> gelezen en ga hiermee akkoord.
                </label>
            </div>
            
            <button type="submit" name="signup" id="signup-button" disabled>Registreer</button>
        </form>
        
        <div class="login-link">
            Heb je al een account? <a href="login.php">Log hier in</a>
        </div>
    </div>
    
    <script>
        // Initial call to set the correct form fields based on default selection
        updateForm();

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

        // Script to enable/disable the submit button
        const privacyCheckbox = document.getElementById('privacy-accept');
        const submitButton = document.getElementById('signup-button');

        privacyCheckbox.addEventListener('change', function() {
            submitButton.disabled = !this.checked;
        });
    </script>
</body>
</html>