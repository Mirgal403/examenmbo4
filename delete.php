<?php
session_start();
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('Only administrators can delete users');
    window.location.href='showuser.php';
    </script>";
    exit();
}

require 'connect.php';
$id = $_GET['id'];
$del= "delete from users where id='$id'";
$query = mysqli_query($conn, $del);

if ($query) {
    echo "<script>alert('Data deleted successfully');
    window.location.href='showuser.php';
    </script>";
} else {
    echo "<script>alert('Error deleting data');
    window.location.href='showuser.php';
    </script>";
}

?>