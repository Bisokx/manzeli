<?php
session_start();
require_once 'db.php';
require_once 'google-config.php';

// Check for authorization code
$code = $_GET['code'] ?? '';
if (empty($code)) {
    header('Location: ../pages/login.php?error=Google login failed. Please try again.');
    exit;
}

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($tokenData),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
]);
$tokenResponse = curl_exec($ch);
$tokenError = curl_error($ch);
curl_close($ch);

if ($tokenError) {
    header('Location: ../pages/login.php?error=Connection error. Please try again.');
    exit;
}

$tokenResult = json_decode($tokenResponse, true);
$accessToken = $tokenResult['access_token'] ?? '';

if (empty($accessToken)) {
    header('Location: ../pages/login.php?error=Google authentication failed.');
    exit;
}

// Get user info from Google
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
    header('Location: ../pages/login.php?error=Could not get your Google account info.');
    exit;
}

// Check if user already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$googleEmail]);
$existingUser = $stmt->fetch();

if ($existingUser) {
    // User exists — log them in
    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['full_name'] = $existingUser['full_name'];
    $_SESSION['email'] = $existingUser['email'];
    $_SESSION['role'] = $existingUser['role'];
    
    // Update profile image if they don't have one
    if (empty($existingUser['profile_image']) && !empty($googlePicture)) {
        $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?")->execute([$googlePicture, $existingUser['id']]);
    }
    
    if ($existingUser['role'] === 'admin') {
        header('Location: ../pages/admin/index.php');
    } else {
        header('Location: ../pages/dashboard.php');
    }
    exit;
} else {
    // New user — create account with guest role
    $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, profile_image) VALUES (?, ?, ?, 'guest', ?)");
    $stmt->execute([$googleName, $googleEmail, $randomPassword, $googlePicture]);
    
    $newUserId = $pdo->lastInsertId();
    
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['full_name'] = $googleName;
    $_SESSION['email'] = $googleEmail;
    $_SESSION['role'] = 'guest';
    
    header('Location: ../pages/dashboard.php');
    exit;
}
