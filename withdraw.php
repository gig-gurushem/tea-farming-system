<?php
session_start();
require "config.php";

if (!isset($_SESSION['farmer_id'])) {
    header("Location: login.php");
    exit;
}

$farmer_id = $_SESSION['farmer_id'];
$amount = floatval($_POST['amount'] ?? 0);
$method = $_POST['method'] ?? 'mobile_money';

if ($amount <= 0) {
    header("Location: dashboard.php?msg=" . urlencode("Invalid withdrawal amount."));
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT account_balance FROM farmers WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $farmer_id);
    $stmt->execute();
    $farmer = $stmt->get_result()->fetch_assoc();

    if (!$farmer) {
        throw new Exception("Farmer not found.");
    }

    if ($farmer['account_balance'] < $amount) {
        throw new Exception("Insufficient balance.");
    }

    $stmt = $conn->prepare("UPDATE farmers SET account_balance = account_balance - ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $farmer_id);
    $stmt->execute();

    $stmt = $conn->prepare("INSERT INTO payments (farmer_id, amount, method, status, processed_at) 
                             VALUES (?, ?, ?, 'approved', NOW())");
    $stmt->bind_param("ids", $farmer_id, $amount, $method);
    $stmt->execute();

    $conn->commit();
    header("Location: dashboard.php?msg=" . urlencode("Withdrawal successful."));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: dashboard.php?msg=" . urlencode("Withdrawal failed: " . $e->getMessage()));
    exit;
}
?>
