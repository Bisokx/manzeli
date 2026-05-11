<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$senderId = $_SESSION['user_id'];
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$propertyId = (int)($_POST['property_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

if ($receiverId <= 0 || empty($message)) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
    exit;
}

// Don't message yourself
if ($receiverId == $senderId) {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php'));
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, property_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
$stmt->execute([$senderId, $receiverId, $propertyId ?: null, $message]);

// Redirect back
$referer = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? '../pages/dashboard.php');
header('Location: ' . $referer . (strpos($referer, '?') !== false ? '&' : '?') . 'msg=sent');
exit;
