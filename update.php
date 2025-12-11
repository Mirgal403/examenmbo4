<?php
// Handle user updates
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['id'])) {
    require 'connect.php';
    
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    // Start building the update query
    $updates = array();
    $updates[] = "username = '" . mysqli_real_escape_string($conn, $username) . "'";
    $updates[] = "email = '" . mysqli_real_escape_string($conn, $email) . "'";
    
    // Only update password if a new one was provided
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password = '" . $hashedPassword . "'";
    }
    
    // Only admin can change roles
    if($_SESSION['role'] == 'admin') {
        $updates[] = "role = '" . mysqli_real_escape_string($conn, $role) . "'";
    }
    
    $updateQuery = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = " . intval($id);
    
    if(mysqli_query($conn, $updateQuery)) {
        echo "<script>alert('User updated successfully');
        window.location.href='showuser.php';
        </script>";
    } else {
        echo "<script>alert('Error updating user: " . mysqli_error($conn) . "');
        window.location.href='edit.php?id=" . $id . "';
        </script>";
    }
    
    mysqli_close($conn);
} else {
    header("Location: showuser.php");
    exit();
}
?>
