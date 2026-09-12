<?php
// config.php - shared database connection for the whole portal
$conn = new mysqli("localhost", "root", "", "tea_system");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
