<?php
$pageTitle = 'Choose Your Role';
$extraCSS = '<link rel="stylesheet" href="/assets/css/auth.css">';
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!$isLoggedIn || !isset($_SESSION['new_google_user'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'guest';
    if (!in_array($role, ['guest', 'host'])) $role = 'guest';
    
    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $_SESSION['user_id']]);
    $_SESSION['role'] = $role;
    unset($_SESSION['new_google_user']);
    
    header('Location: dashboard.php');
    exit;
}
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-branding">
            <div class="auth-brand-content">
                <div class="auth-brand-icon"><i class="fas fa-user-cog"></i></div>
                <h2>Welcome to<br><span>Manzeli</span> منزلي</h2>
                <p>One last step — tell us how you'd like to use Manzeli.</p>
            </div>
        </div>
        <div class="auth-form-side">
            <div class="auth-form-wrapper">
                <h1 class="auth-title">How will you use Manzeli?</h1>
                <p class="auth-subtitle">You can change this later</p>
                <form method="POST" class="auth-form">
                    <div class="form-group">
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
                    <button type="submit" class="auth-btn">
                        <span>Continue</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
