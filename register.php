<?php
require "config.php";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name']);
    $national_id = trim($_POST['national_id']);
    $phone = trim($_POST['phone']);
    $pin = $_POST['pin'];
    $confirm_pin = $_POST['confirm_pin'];
    $region = trim($_POST['region']);
    $center_id = intval($_POST['collection_center_id']);

    if ($pin !== $confirm_pin) {
        $error = "PINs do not match.";
    } else {
        // check phone/national_id not already used
        $check = $conn->prepare("SELECT id FROM farmers WHERE phone = ? OR national_id = ?");
        $check->bind_param("ss", $phone, $national_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "An account with this phone or national ID already exists.";
        } else {
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO farmers (national_id, name, phone, pin, region, collection_center_id, account_balance) 
                                     VALUES (?, ?, ?, ?, ?, ?, 0.00)");
            $stmt->bind_param("sssssi", $national_id, $name, $phone, $hashed_pin, $region, $center_id);

            if ($stmt->execute()) {
                $success = "Account created successfully. You can now log in.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

// fetch collection centers for the dropdown
$centers = $conn->query("SELECT id, name FROM collection_centers ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Tea Farmer Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h2>🌱 Create Account</h2>
            <p class="subtitle">Join the tea farmer portal to track your deliveries and earnings.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="field">
                    <label>Full Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="field">
                    <label>National ID</label>
                    <input type="text" name="national_id" required>
                </div>
                <div class="field">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required>
                </div>
                <div class="field">
                    <label>Region</label>
                    <input type="text" name="region" placeholder="e.g. Kisii" required>
                </div>
                <div class="field">
                    <label>Collection Center</label>
                    <select name="collection_center_id" required>
                        <option value="">-- Select --</option>
                        <?php while ($row = $centers->fetch_assoc()): ?>
                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="field">
                    <label>PIN (4-6 digits)</label>
                    <input type="password" name="pin" required>
                </div>
                <div class="field">
                    <label>Confirm PIN</label>
                    <input type="password" name="confirm_pin" required>
                </div>
                <button type="submit" class="btn-primary">Create Account</button>
            </form>

            <div class="switch-link">
                Already have an account? <a href="login.php">Log in</a>
            </div>
        </div>
    </div>
</body>
</html>
