<?php
$pageTitle = 'Sign Up';
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

<!-- ===== REGISTER PAGE ===== -->
<section class="auth-section">
    <div class="auth-container">
        <!-- Left Side — Branding -->
        <div class="auth-branding">
            <div class="auth-brand-content">
                <div class="auth-brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h2>Join the<br><span>Manzeli</span> منزلي<br>Community</h2>
                <p>Create your free account and start exploring properties across Lebanon. Whether you're renting, buying, or selling — we've got you covered.</p>
                <div class="auth-brand-features">
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>100% free to sign up</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Book rentals or contact sellers</span>
                    </div>
                    <div class="auth-feature">
                        <i class="fas fa-check-circle"></i>
                        <span>Become a host & list properties</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side — Form -->
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Fill in your details to get started</p>

                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="auth-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <form class="auth-form" id="registerForm" method="POST" action="../includes/auth.php">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone" placeholder="+961 XX XXX XXX">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Min 8 characters" required minlength="8">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Role Selection -->
                    <div class="form-group">
                        <label>I want to</label>
                        <div class="role-selector">
                            <label class="role-option">
                                <input type="radio" name="role" value="guest" checked>
                                <div class="role-card">
                                    <i class="fas fa-search"></i>
                                    <span class="role-title">Browse & Book</span>
                                    <span class="role-desc">I'm looking to rent or buy</span>
                                </div>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="host">
                                <div class="role-card">
                                    <i class="fas fa-plus-circle"></i>
                                    <span class="role-title">List Property</span>
                                    <span class="role-desc">I want to rent or sell</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="#">Terms of Service</a> & <a href="#">Privacy Policy</a>
                        </label>
                    </div>

                    <button type="submit" class="auth-btn">
                        <span>Create Account</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-divider">
                    <span>or</span>
                </div>

                <div class="auth-social">
                    <a href="<?php echo getGoogleLoginUrl(); ?>" class="social-btn google-btn" style="text-decoration:none;">
                        <i class="fab fa-google"></i>
                        <span>Sign up with Google</span>
                    </a>
                </div>

                <p class="auth-switch">
                    Already have an account? <a href="login.php">Log In</a>
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

// Password match validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pass = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (pass !== confirm) {
        e.preventDefault();
        const errorDiv = document.querySelector('.auth-error');
        if (!errorDiv) {
            const err = document.createElement('div');
            err.className = 'auth-error';
            err.innerHTML = '<i class="fas fa-exclamation-circle"></i><span>Passwords do not match!</span>';
            document.querySelector('.auth-subtitle').after(err);
        }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
