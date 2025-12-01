<?php
// Database connection file
$servername = "mysql";
$username1 = "root";
$password1 = "password";

$dbname = "bowl";

$conn = mysqli_connect($servername, $username1, $password1, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>