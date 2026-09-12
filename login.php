<?php
session_start();
require "config.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone']);
    $pin = $_POST['pin'];

    $stmt = $conn->prepare("SELECT id, name, pin FROM farmers WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();

    if ($farmer && password_verify($pin, $farmer['pin'])) {
        $_SESSION['farmer_id'] = $farmer['id'];
        $_SESSION['farmer_name'] = $farmer['name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid phone number or PIN.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Tea Farmer Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>🍃 Welcome Back</h2>
            <p class="subtitle">Log in to view your deliveries and manage your earnings.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required autofocus>
                </div>
                <div class="field">
                    <label>PIN</label>
                    <input type="password" name="pin" required>
                </div>
                <button type="submit" class="btn-primary">Log In</button>
            </form>

            <div class="switch-link">
                New farmer? <a href="register.php">Create an account</a>
            </div>
        </div>
    </div>
</body>
</html>
