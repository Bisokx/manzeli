<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = (int)($_POST['property_id'] ?? 0);

if ($propertyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid property']);
    exit;
}

// Check if already favorited
$stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
$stmt->execute([$userId, $propertyId]);

if ($stmt->fetch()) {
    // Remove favorite
    $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND property_id = ?")->execute([$userId, $propertyId]);
    echo json_encode(['success' => true, 'action' => 'removed']);
} else {
    // Add favorite
    $pdo->prepare("INSERT INTO favorites (user_id, property_id) VALUES (?, ?)")->execute([$userId, $propertyId]);
    echo json_encode(['success' => true, 'action' => 'added']);
}
