<?php
session_start();
require_once 'db.php';

$action = $_POST['action'] ?? '';

// =============================================
// REGISTER
// =============================================
if ($action === 'register') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'guest';

    // Validation
    if (empty($full_name) || empty($email) || empty($password)) {
        header('Location: ../pages/register.php?error=' . urlencode('Please fill in all required fields.'));
        exit;
    }
    if (strlen($password) < 8) {
        header('Location: ../pages/register.php?error=' . urlencode('Password must be at least 8 characters.'));
        exit;
    }
    if ($password !== $confirm) {
        header('Location: ../pages/register.php?error=' . urlencode('Passwords do not match.'));
        exit;
    }
    if (!in_array($role, ['guest', 'host'])) {
        $role = 'guest';
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        header('Location: ../pages/register.php?error=' . urlencode('An account with this email already exists.'));
        exit;
    }

    // Hash password and insert
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$full_name, $email, $phone, $hashedPassword, $role]);

    // Auto-login after registration
    $userId = $pdo->lastInsertId();
    $_SESSION['user_id'] = $userId;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;

    header('Location: ../pages/dashboard.php');
    exit;
}

// =============================================
// LOGIN
// =============================================
if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        header('Location: ../pages/login.php?error=' . urlencode('Please enter your email and password.'));
        exit;
    }

    // Find user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        header('Location: ../pages/login.php?error=' . urlencode('Invalid email or password.'));
        exit;
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header('Location: ../pages/admin/index.php');
    } else {
        header('Location: ../pages/dashboard.php');
    }
    exit;
}

// Invalid action
header('Location: ../pages/login.php');
exit;
