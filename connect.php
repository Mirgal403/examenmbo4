<?php
// Database connection file
$servername = "sql305.infinityfree.com";
$username1 = "if0_40576696";
$password1 = "3HNSgnTKq91NDp";

$dbname = "if0_40576696_examenprojectmbo";

$conn = mysqli_connect($servername, $username1, $password1, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>