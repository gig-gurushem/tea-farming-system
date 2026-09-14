<?php
session_start();
require "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$farmer_id = intval($_GET['farmer_id'] ?? $_POST['farmer_id'] ?? 0);

// handle submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $kilos = floatval($_POST['kilos']);
    $center_id = intval($_POST['collection_center_id']);

    if ($kilos <= 0) {
        $error = "Enter a valid weight in kilos.";
    } else {
        $stmt = $conn->prepare("INSERT INTO collection_records (farmer_id, collection_center_id, kilos) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $farmer_id, $center_id, $kilos);
        if ($stmt->execute()) {
            header("Location: admin_dashboard.php?msg=" . urlencode("Delivery recorded successfully."));
            exit;
        } else {
            $error = "Something went wrong: " . $conn->error;
        }
    }
}

// fetch farmer info
$stmt = $conn->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

if (!$farmer) {
    header("Location: admin_dashboard.php?msg=" . urlencode("Farmer not found."));
    exit;
}

$centers = $conn->query("SELECT id, name FROM collection_centers ORDER BY name");

// current rate, just to display
$rate_row = $conn->query("SELECT price_per_kilo FROM rates WHERE effective_date <= CURDATE() ORDER BY effective_date DESC LIMIT 1")->fetch_assoc();
$current_rate = $rate_row['price_per_kilo'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Delivery - Tea System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1><span class="leaf">🌿</span>Admin Panel</h1>
        <a href="admin_logout.php" class="logout">Log out</a>
    </div>

    <div class="dashboard-wrap" style="max-width: 500px;">
        <a href="admin_dashboard.php" class="switch-link" style="display:inline-block; margin-bottom:16px;">&larr; Back to dashboard</a>

        <div class="tab-content active">
            <h3 style="margin-bottom: 6px;">Log Delivery for <?= htmlspecialchars($farmer['name']) ?></h3>
            <p style="color:#6b8f6b; margin-bottom: 18px;">
                Current rate: <strong>KES <?= number_format($current_rate, 2) ?>/kg</strong> ·
                Balance: <strong>KES <?= number_format($farmer['account_balance'], 2) ?></strong>
            </p>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="farmer_id" value="<?= $farmer['id'] ?>">
                <div class="field">
                    <label>Kilos Delivered</label>
                    <input type="number" step="0.1" name="kilos" min="0.1" required autofocus>
                </div>
                <div class="field">
                    <label>Collection Center</label>
                    <select name="collection_center_id" required>
                        <?php while ($c = $centers->fetch_assoc()): ?>
                            <option value="<?= $c['id'] ?>" <?= $c['id'] == $farmer['collection_center_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Record Delivery</button>
            </form>
        </div>
    </div>
</body>
</html>
