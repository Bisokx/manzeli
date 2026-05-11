<?php
$pageTitle = 'Manage Users';
$extraCSS = '<link rel="stylesheet" href="/assets/css/admin.css">';
require_once '../../includes/db.php';
require_once '../../includes/header.php';

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId !== $_SESSION['user_id']) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$deleteId]);
    }
    header('Location: users.php?deleted=1');
    exit;
}

// Handle role change
if (isset($_POST['change_role'])) {
    $uid = (int)$_POST['user_id'];
    $newRole = $_POST['new_role'];
    if (in_array($newRole, ['guest','host','admin']) && $uid !== $_SESSION['user_id']) {
        $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $uid]);
    }
    header('Location: users.php?updated=1');
    exit;
}

$filter = $_GET['role'] ?? '';
$query = "SELECT * FROM users";
if (in_array($filter, ['guest','host','admin'])) {
    $query .= " WHERE role = '$filter'";
}
$query .= " ORDER BY created_at DESC";
$users = $pdo->query($query)->fetchAll();
?>

<section class="admin-section">
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
            <div class="admin-filters">
                <a href="users.php" class="filter-btn <?php echo !$filter ? 'active' : ''; ?>">All</a>
                <a href="users.php?role=guest" class="filter-btn <?php echo $filter === 'guest' ? 'active' : ''; ?>">Guests</a>
                <a href="users.php?role=host" class="filter-btn <?php echo $filter === 'host' ? 'active' : ''; ?>">Hosts</a>
                <a href="users.php?role=admin" class="filter-btn <?php echo $filter === 'admin' ? 'active' : ''; ?>">Admins</a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="host-success"><i class="fas fa-check-circle"></i> User deleted successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="host-success"><i class="fas fa-check-circle"></i> User role updated.</div>
        <?php endif; ?>

        <div class="admin-card">
            <table class="admin-table">
                <thead><tr><th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-cell-avatar"><?php echo strtoupper(substr($u['full_name'],0,1)); ?></div>
                                    <?php echo htmlspecialchars($u['full_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="change_role" value="1">
                                        <select name="new_role" onchange="this.form.submit()" class="role-select">
                                            <option value="guest" <?php echo $u['role']==='guest'?'selected':''; ?>>Guest</option>
                                            <option value="host" <?php echo $u['role']==='host'?'selected':''; ?>>Host</option>
                                            <option value="admin" <?php echo $u['role']==='admin'?'selected':''; ?>>Admin</option>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span class="status-badge role-admin">Admin (You)</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                    <a href="users.php?delete=<?php echo $u['id']; ?>" class="action-delete" onclick="return confirm('Delete this user?')" title="Delete"><i class="fas fa-trash"></i></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once '../../includes/footer.php'; ?>
