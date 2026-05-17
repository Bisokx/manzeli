<?php
$pageTitle = 'Forgot Password';
$extraCSS = '<link rel="stylesheet" href="/assets/css/auth.css">';
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/smtp-mail.php';

if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token to database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Build reset link
            $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . '/pages/reset-password.php?token=' . $token;
            
            // Send email
            $subject = "Manzeli - Reset Your Password";
            $htmlMessage = '
<html>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
        <div style="background:linear-gradient(135deg,#0ABAB5,#089E9A);color:#fff;padding:28px;text-align:center;">
            <h1 style="margin:0;font-size:22px;">🏠 Manzeli</h1>
            <p style="margin:5px 0 0;opacity:0.9;font-size:14px;">Password Reset</p>
        </div>
        <div style="padding:28px;">
            <h2 style="color:#333;font-size:18px;margin-top:0;">Hello, ' . htmlspecialchars($user['full_name']) . '!</h2>
            <p style="color:#666;line-height:1.7;font-size:14px;">We received a request to reset your password. Click the button below to set a new password:</p>
            <div style="text-align:center;margin:30px 0;">
                <a href="' . $resetLink . '" style="display:inline-block;padding:14px 32px;background:linear-gradient(135deg,#0ABAB5,#089E9A);color:#fff;text-decoration:none;border-radius:8px;font-size:16px;font-weight:600;">Reset My Password</a>
            </div>
            <p style="color:#999;font-size:12px;">This link expires in 1 hour. If you didn\'t request this, you can safely ignore this email.</p>
            <p style="color:#999;font-size:12px;">Or copy this link: <br><a href="' . $resetLink . '" style="color:#0ABAB5;word-break:break-all;">' . $resetLink . '</a></p>
        </div>
        <div style="text-align:center;padding:18px;color:#999;font-size:11px;border-top:1px solid #eee;">
            &copy; ' . date('Y') . ' Manzeli – منزلي | West Bekaa, Sohmor, Lebanon
        </div>
    </div>
</body>
</html>';
            
         
            
           manzeli_mail($email, $subject, $htmlMessage); 
            
            header('Location: forgot-password.php?success=sent');
            exit;
        } else {
            // Don't reveal if email exists or not (security)
            header('Location: forgot-password.php?success=sent');
            exit;
        }
    }
}
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-branding">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Forgot Your<br><span>Password?</span></h2>
                <p>No worries! Enter your email and we'll send you a link to reset your password.</p>
                <div class="auth-brand-features">
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Check your email for the reset link</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Link expires in 1 hour</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Set a new secure password</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <h1 class="auth-title">Reset Password</h1>
                <p class="auth-subtitle">Enter your email to receive a reset link</p>

                <?php if ($success === 'sent'): ?>
                    <div style="background:#d4edda;color:#155724;padding:14px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;">
                        <i class="fas fa-check-circle"></i> If an account with that email exists, we've sent a password reset link. Check your inbox!
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn">
                        <span>Send Reset Link</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>

                <p class="auth-switch">
                    Remember your password? <a href="login.php">Log In</a>
                </p>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
