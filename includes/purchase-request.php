<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = (int)($_POST['property_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($propertyId <= 0 || empty($fullName) || empty($phone)) {
    header('Location: ../pages/property.php?id=' . $propertyId . '&error=missing');
    exit;
}

// Don't allow requesting your own property
$owner = $pdo->prepare("SELECT host_id FROM properties WHERE id = ?");
$owner->execute([$propertyId]);
$prop = $owner->fetch();
if ($prop && $prop['host_id'] == $userId) {
    header('Location: ../pages/property.php?id=' . $propertyId);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO purchase_requests (user_id, property_id, full_name, phone, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
$stmt->execute([$userId, $propertyId, $fullName, $phone, $message]);

header('Location: ../pages/dashboard.php?tab=requests&success=sent');
exit;
