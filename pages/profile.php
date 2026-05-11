<?php
$pageTitle = 'Profile';
$extraCSS = '<link rel="stylesheet" href="/assets/css/host.css">';
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!$isLoggedIn) { header('Location: login.php'); exit; }

$userId = $_SESSION['user_id'];
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$userId]);
$user = $user->fetch();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?")->execute([$name, $phone, $userId]);
        $_SESSION['full_name'] = $name;

        if (!empty($newPass)) {
            if (!password_verify($currentPass, $user['password'])) {
                $error = 'Current password is incorrect.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'New passwords do not match.';
            } elseif (strlen($newPass) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($newPass, PASSWORD_DEFAULT), $userId]);
                $success = 'Profile and password updated!';
            }
        } else {
            $success = 'Profile updated!';
        }
        $user['full_name'] = $name;
        $user['phone'] = $phone;
    }
}
?>

<section class="host-section">
    <div class="host-container">
        <div class="host-page-header">
            <h1><i class="fas fa-user-circle"></i> My Profile</h1>
            <p>Update your personal information</p>
        </div>

        <?php if ($success): ?><div class="host-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="host-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-id-card"></i> Personal Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email (cannot change)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+961 XX XXX XXX">
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-lock"></i> Change Password</h2>
                <p class="form-hint">Leave blank if you don't want to change your password.</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" placeholder="Enter current password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="Min 8 characters" minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="Re-enter new password">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard.php" class="form-cancel">Cancel</a>
                <button type="submit" class="form-submit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </form>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
