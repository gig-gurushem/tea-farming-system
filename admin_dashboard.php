<?php
session_start();
require "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// overall stats
$totals = $conn->query("SELECT 
    COUNT(*) AS farmer_count, 
    COALESCE(SUM(account_balance),0) AS total_owed 
    FROM farmers")->fetch_assoc();

$kilos_totals = $conn->query("SELECT COALESCE(SUM(kilos),0) AS total_kilos FROM collection_records")->fetch_assoc();

// search
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $conn->prepare("SELECT f.*, c.name AS center_name FROM farmers f 
                             LEFT JOIN collection_centers c ON f.collection_center_id = c.id
                             WHERE f.name LIKE ? OR f.phone LIKE ? OR f.national_id LIKE ?
                             ORDER BY f.id DESC");
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $farmers = $stmt->get_result();
} else {
    $farmers = $conn->query("SELECT f.*, c.name AS center_name FROM farmers f 
                              LEFT JOIN collection_centers c ON f.collection_center_id = c.id
                              ORDER BY f.id DESC");
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Tea System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1><span class="leaf">🌿</span>Admin Panel</h1>
        <a href="admin_logout.php" class="logout">Log out</a>
    </div>

    <div class="dashboard-wrap">
        <h2 style="margin-bottom: 4px;">Hello, <?= htmlspecialchars($_SESSION['admin_username']) ?></h2>
        <p style="color:#6b8f6b; margin-bottom: 24px;">Manage farmers and record tea deliveries.</p>

        <?php if ($msg): ?>
            <div class="alert <?= strpos($msg, 'success') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-card">
                <div class="label">Total Farmers</div>
                <div class="value"><?= $totals['farmer_count'] ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Kilos Collected</div>
                <div class="value"><?= number_format($kilos_totals['total_kilos'], 1) ?> kg</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Owed to Farmers</div>
                <div class="value">KES <?= number_format($totals['total_owed'], 2) ?></div>
            </div>
        </div>

        <div class="tab-content active" style="margin-bottom: 20px;">
            <form method="GET" style="display:flex; gap:10px; margin-bottom: 18px;">
                <input type="text" name="search" placeholder="Search by name, phone, or national ID"
                       value="<?= htmlspecialchars($search) ?>"
                       style="flex:1; padding:12px 14px; border:1.5px solid #d7e8d7; border-radius:10px;">
                <button type="submit" class="btn-primary" style="width:auto; padding:12px 24px;">Search</button>
            </form>

            <table>
                <tr>
                    <th>Name</th><th>Phone</th><th>Region</th><th>Center</th>
                    <th>Balance</th><th>Action</th>
                </tr>
                <?php if ($farmers->num_rows === 0): ?>
                    <tr><td colspan="6" style="color:#999;">No farmers found.</td></tr>
                <?php else: while ($f = $farmers->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['name']) ?></td>
                        <td><?= htmlspecialchars($f['phone']) ?></td>
                        <td><?= htmlspecialchars($f['region']) ?></td>
                        <td><?= htmlspecialchars($f['center_name'] ?? '-') ?></td>
                        <td>KES <?= number_format($f['account_balance'], 2) ?></td>
                        <td><a href="add_collection.php?farmer_id=<?= $f['id'] ?>" class="switch-link"><strong>+ Add Delivery</strong></a></td>
                    </tr>
                <?php endwhile; endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
