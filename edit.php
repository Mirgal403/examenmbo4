<?php
// User editing form (admin only)
session_start();
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET["id"])){
    $id = $_GET["id"];
    require 'connect.php';
    $select = mysqli_query($conn, "SELECT * FROM users WHERE id='$id'");
    $row = mysqli_fetch_assoc($select);
    
    // Store the original role to use in the form
    $original_role = $row['role'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 20px;
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #4CAF50;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input:focus,
        select:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3);
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        button, a {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }
        button {
            background-color: #4CAF50;
            color: white;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        a {
            background-color: #007BFF;
            color: white;
        }
        a:hover {
            background-color: #0056b3;
        }
        .role-info {
            background-color: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #2196F3;
            margin-top: 5px;
            font-size: 12px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit User</h2>
        <form action="update.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($row['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Leave empty to keep current password">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required onchange="updateRoleInfo()">
                    <option value="user" <?php echo $row['role'] == 'user' ? 'selected' : ''; ?>> Klant (User)</option>
                    <option value="medewerker" <?php echo $row['role'] == 'medewerker' ? 'selected' : ''; ?>> Medewerker</option>
                    <option value="admin" <?php echo $row['role'] == 'admin' ? 'selected' : ''; ?>> Admin</option>
                </select>
                <div id="role-info" class="role-info"></div>
            </div>

            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

            <div class="button-group">
                <button type="submit">Update User</button>
                <a href="showuser.php">← Back</a>
            </div>
        </form>
    </div>

    <script>
        function updateRoleInfo() {
            const role = document.getElementById('role').value;
            const roleInfo = document.getElementById('role-info');
            
            const roleDescriptions = {
                'user': 'Klant: Kan reserveringen maken en beheren',
                'medewerker': 'Medewerker: Kan alle reserveringen zien en beheren',
                'admin': 'Admin: Volledige toegang tot user management'
            };
            
            if (roleDescriptions[role]) {
                roleInfo.textContent = roleDescriptions[role];
            }
        }
        
        // Show initial role info
        updateRoleInfo();
    </script>
</body>
</html>