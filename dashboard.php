<?php
session_start();
require "config.php";

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit;
}

$farmer_id = $_SESSION['farmer_id'];

// fetch farmer info
$stmt = $conn->prepare("SELECT f.*, c.name AS center_name FROM farmers f 
                         LEFT JOIN collection_centers c ON f.collection_center_id = c.id 
                         WHERE f.id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// total kilos delivered
$stmt = $conn->prepare("SELECT COALESCE(SUM(kilos),0) AS total_kilos, COUNT(*) AS trip_count 
                         FROM collection_records WHERE farmer_id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

// recent collection records
$stmt = $conn->prepare("SELECT kilos, rate_applied, amount_earned, date_time FROM collection_records 
                         WHERE farmer_id = ? ORDER BY date_time DESC LIMIT 10");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$records = $stmt->get_result();

// recent payments
$stmt = $conn->prepare("SELECT amount, method, status, requested_at FROM payments 
                         WHERE farmer_id = ? ORDER BY requested_at DESC LIMIT 10");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$payments = $stmt->get_result();

$withdraw_message = $_GET['msg'] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Tea Farmer Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="topbar">
        <h1><span class="leaf">🍃</span>Tea Farmer Portal</h1>
        <a href="logout.php" class="logout">Log out</a>
    </div>

    <div class="dashboard-wrap">
        <h2 style="margin-bottom: 4px;">Karibu, <?= htmlspecialchars($farmer['name']) ?> 👋</h2>
        <p style="color:#6b8f6b; margin-bottom: 24px;"><?= htmlspecialchars($farmer['center_name'] ?? 'No center assigned') ?> · <?= htmlspecialchars($farmer['region']) ?></p>

        <div class="stats-row">
            <div class="stat-card">
                <div class="label">Current Balance</div>
                <div class="value">KES <?= number_format($farmer['account_balance'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Kilos Delivered</div>
                <div class="value"><?= number_format($totals['total_kilos'], 1) ?> kg</div>
            </div>
            <div class="stat-card">
                <div class="label">Delivery Trips</div>
                <div class="value"><?= $totals['trip_count'] ?></div>
            </div>
        </div>

        <?php if ($withdraw_message): ?>
            <div class="alert <?= strpos($withdraw_message, 'success') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($withdraw_message) ?>
            </div>
        <?php endif; ?>

        <div class="tab-bar">
            <button class="tab-btn active" onclick="showTab('overview', this)">Overview</button>
            <button class="tab-btn" onclick="showTab('withdraw', this)">Withdraw</button>
            <button class="tab-btn" onclick="showTab('history', this)">History</button>
        </div>

        <div id="overview" class="tab-content active">
            <h3 style="margin-bottom: 14px;">Recent Deliveries</h3>
            <table>
                <tr><th>Date</th><th>Kilos</th><th>Rate</th><th>Earned</th></tr>
                <?php if ($records->num_rows === 0): ?>
                    <tr><td colspan="4" style="color:#999;">No deliveries recorded yet.</td></tr>
                <?php else: while ($r = $records->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d M Y, H:i", strtotime($r['date_time'])) ?></td>
                        <td><?= number_format($r['kilos'], 1) ?> kg</td>
                        <td>KES <?= number_format($r['rate_applied'], 2) ?></td>
                        <td>KES <?= number_format($r['amount_earned'], 2) ?></td>
                    </tr>
                <?php endwhile; endif; ?>
            </table>
        </div>

        <div id="withdraw" class="tab-content">
            <h3 style="margin-bottom: 14px;">Request a Withdrawal</h3>
            <p style="color:#6b8f6b; margin-bottom: 18px;">Available balance: <strong>KES <?= number_format($farmer['account_balance'], 2) ?></strong></p>
            <form class="withdraw-form" method="POST" action="withdraw.php">
                <div class="field">
                    <label>Amount (KES)</label>
                    <input type="number" step="0.01" name="amount" min="1" required>
                </div>
                <div class="field">
                    <label>Method</label>
                    <select name="method">
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank">Bank</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Withdraw</button>
            </form>
        </div>

        <div id="history" class="tab-content">
            <h3 style="margin-bottom: 14px;">Withdrawal History</h3>
            <table>
                <tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                <?php if ($payments->num_rows === 0): ?>
                    <tr><td colspan="4" style="color:#999;">No withdrawals yet.</td></tr>
                <?php else: while ($p = $payments->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d M Y, H:i", strtotime($p['requested_at'])) ?></td>
                        <td>KES <?= number_format($p['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($p['method']) ?></td>
                        <td><?= htmlspecialchars($p['status']) ?></td>
                    </tr>
                <?php endwhile; endif; ?>
            </table>
        </div>
    </div>

    <script>
        function showTab(id, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById(id).classList.add('active');
            btn.classList.add('active');
        }
    </script>
</body>
</html>
