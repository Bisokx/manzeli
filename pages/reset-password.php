<?php
$pageTitle = 'Reset Password';
$extraCSS = '<link rel="stylesheet" href="/assets/css/auth.css">';
require_once '../includes/db.php';
require_once '../includes/header.php';

if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$validToken = false;

// Verify token
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $validToken = true;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        $success = true;
    }
}
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-branding">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h2>Set Your<br><span>New Password</span></h2>
                <p>Choose a strong password with at least 8 characters.</p>
            </div>
        </div>

        <div class="auth-form-side">
            <div class="auth-form-wrapper">

                <?php if ($success): ?>
                    <h1 class="auth-title">Password Reset!</h1>
                    <div style="background:#d4edda;color:#155724;padding:14px 18px;border-radius:10px;font-size:14px;margin:20px 0;">
                        <i class="fas fa-check-circle"></i> Your password has been successfully reset.
                    </div>
                    <a href="login.php" class="auth-btn" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;margin-top:16px;">
                        <span>Go to Login</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>

                <?php elseif (!$validToken): ?>
                    <h1 class="auth-title">Invalid Link</h1>
                    <div style="background:#ffe0e0;color:#c00;padding:14px 18px;border-radius:10px;font-size:14px;margin:20px 0;">
                        <i class="fas fa-exclamation-circle"></i> This reset link is invalid or has expired. Please request a new one.
                    </div>
                    <a href="forgot-password.php" class="auth-btn" style="display:flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;margin-top:16px;">
                        <span>Request New Link</span>
                        <i class="fas fa-redo"></i>
                    </a>

                <?php else: ?>
                    <h1 class="auth-title">New Password</h1>
                    <p class="auth-subtitle">Enter your new password below</p>

                    <?php if ($error): ?>
                        <div class="auth-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    <?php endif; ?>

                    <form class="auth-form" method="POST">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" placeholder="Minimum 8 characters" minlength="8" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" minlength="8" required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="auth-btn">
                            <span>Reset Password</span>
                            <i class="fas fa-check"></i>
                        </button>
                    </form>
                <?php endif; ?>

                <p class="auth-switch">
                    <a href="login.php">← Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</section>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
