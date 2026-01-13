<?php
// User listing page (admin only)
session_start();
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>users</title>
</head>
<body>
    <style>  
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        #users {
            width: 80%;
            margin: auto;
            padding-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
        }

        thead {
            background-color: #4CAF50;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e0f7fa;
        }

        th {
            font-weight: bold;
        }

        .edit-btn, .delete-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .edit-btn {
            background-color: #2196F3;
        }

        .delete-btn {
            background-color: #f44336;
        }
    </style></style>
    <div id="users" style="width: 50%;margin: auto; padding-top:15px">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1>User Management</h1>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="createadmin.php" style="margin-right: 10px; padding: 8px 16px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 4px;">Create Admin</a>
            <?php endif; ?>
            <a href="logout.php" style="padding: 8px 16px; background-color: #f44336; color: white; text-decoration: none; border-radius: 4px;">Logout</a>
        </div>
        <table class="table">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">username</th>
      <th scope="col">password</th>
      <th scope="col">email</th>
      <th scope="col">role</th>
      <th scope="col">edit </th>
      <th scope="col">delet</th>
    </tr>
  </thead>
  <tbody>
    <?php
        require 'connect.php';
        $select = "SELECT * FROM users";
        $query = mysqli_query($conn, $select);

        if (mysqli_num_rows($query) > 0) {

            while ($row = mysqli_fetch_assoc($query)) {   // Bepaal weergave op basis van rol
                $roleDisplay = $row['role'];
                
                if ($row['role'] == 'admin') {
                    $roleDisplay = '<strong style="color: red;">ADMIN</strong>';
                } elseif ($row['role'] == 'medewerker') {
                    $roleDisplay = '<strong style="color: blue;">MEDEWERKER</strong>';
                } else {
                    $roleDisplay = '<span style="color: green;">USER</span>';
                }
                
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['username'] . "</td>";
                echo "<td>********</td>"; // Wachtwoord verbergen met sterretjes
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $roleDisplay . "</td>";
                echo '<td><a href="edit.php?id=' . $row['id'] . '" class="btn btn-outline-success" role= "button" aria-pressed="true">edit</a></td>';

                echo '<td><a href="delete.php?id=' . $row['id'] . '" class="btn btn-outline-danger" role= "button" aria-pressed="true">delet</a></td>';
                echo "</tr>";
            }
        } else {
            echo "no data found";
        }

    ?>
   
  </tbody>
</table>


    </div>
    
</body>
</html>