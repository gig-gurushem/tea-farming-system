<?php
// create_admin.php
// Run this ONCE in the browser to create your first admin account, then DELETE this file.
require "config.php";

$username = "admin";
$password = "admin123";   // change this before running, or change it after logging in
$role = "admin";

$hashed = password_hash($password, PASSWORD_DEFAULT);

$check = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "An account with username '$username' already exists. Nothing was created.";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed, $role);
    if ($stmt->execute()) {
        echo "Admin account created! Username: $username / Password: $password<br>";
        echo "<strong>Now delete this file (create_admin.php) for security.</strong>";
    } else {
        echo "Something went wrong: " . $conn->error;
    }
}
?>
