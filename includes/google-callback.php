<?php
session_start();
require_once 'db.php';
require_once 'google-config.php';

$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: ../pages/login.php?error=Google login failed.');
    exit;
}

$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);
$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenResult = json_decode($tokenResponse, true);
$accessToken = $tokenResult['access_token'] ?? '';

if (empty($accessToken)) {
    header('Location: ../pages/login.php?error=Google authentication failed.');
    exit;
}

$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
]);
$userResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userResponse, true);
$googleEmail = $googleUser['email'] ?? '';
$googleName = $googleUser['name'] ?? '';
$googlePicture = $googleUser['picture'] ?? '';

if (empty($googleEmail)) {
    header('Location: ../pages/login.php?error=Could not get Google account info.');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$googleEmail]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['full_name'] = $existingUser['full_name'];
    $_SESSION['email'] = $existingUser['email'];
    $_SESSION['role'] = $existingUser['role'];
    
    if (empty($existingUser['profile_image']) && !empty($googlePicture)) {
        $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?")->execute([$googlePicture, $existingUser['id']]);
    }
    
    header('Location: ../pages/dashboard.php');
    exit;
} else {
    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, profile_image) VALUES (?, ?, ?, 'guest', ?)");
    $stmt->execute([$googleName, $googleEmail, $randomPassword, $googlePicture]);
    
    $newUserId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['full_name'] = $googleName;
    $_SESSION['email'] = $googleEmail;
    $_SESSION['role'] = 'guest';
    $_SESSION['new_google_user'] = true;
    
    header('Location: ../pages/choose-role.php');
    exit;
}
