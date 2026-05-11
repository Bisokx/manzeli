<?php
session_start();
require_once 'db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = (int)($_POST['property_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

// Validate
if ($propertyId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    header('Location: ../pages/property.php?id=' . $propertyId . '&error=invalid_review');
    exit;
}

// Check if user already reviewed this property
$checkStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND property_id = ?");
$checkStmt->execute([$userId, $propertyId]);
if ($checkStmt->fetch()) {
    header('Location: ../pages/property.php?id=' . $propertyId . '&error=already_reviewed');
    exit;
}

// Don't allow reviewing your own property
$propStmt = $pdo->prepare("SELECT host_id FROM properties WHERE id = ?");
$propStmt->execute([$propertyId]);
$prop = $propStmt->fetch();
if ($prop && $prop['host_id'] == $userId) {
    header('Location: ../pages/property.php?id=' . $propertyId);
    exit;
}

// Insert review
$stmt = $pdo->prepare("INSERT INTO reviews (user_id, property_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->execute([$userId, $propertyId, $rating, $comment]);

header('Location: ../pages/property.php?id=' . $propertyId . '&review=success');
exit;
