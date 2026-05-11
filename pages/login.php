<?php
$pageTitle = 'Login';
$extraCSS = '<link rel="stylesheet" href="/assets/css/auth.css">';
require_once '../includes/google-config.php';
require_once '../includes/header.php';

// Redirect if already logged in
if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit;
}

$error = $_GET['error'] ?? '';
?>

<!-- ===== LOGIN PAGE ===== -->
<section class="auth-section">
    <div class="auth-container">
        <!-- Left Side — Branding -->
        <div class="auth-branding">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">
                    <i class="fas fa-home"></i>
                </div>
                <h2>Welcome Back to<br><span>Manzeli</span> منزلي</h2>
                <p>Your gateway to finding the perfect home in Lebanon. Login to access your dashboard, bookings, and saved properties.</p>
                <div class="auth-brand-features">
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Access your bookings & favorites</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Message hosts & sellers directly</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>List your property as a host</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side — Form -->
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <h1 class="auth-title">Log In</h1>
                <p class="auth-subtitle">Enter your credentials to continue</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form" id="loginForm" method="POST" action="../includes/auth.php">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                    </div>

                    <button type="submit" class="auth-btn">
                        <span>Log In</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-social">
                    <a href="<?php echo getGoogleLoginUrl(); ?>" class="social-btn google-btn" style="text-decoration:none;">
                        <i class="fab fa-google"></i>
                        <span>Continue with Google</span>
                    </a>
                </div>

                <p class="auth-switch">
                    Don't have an account? <a href="register.php">Sign Up</a>
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
