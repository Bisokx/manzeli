<?php
$pageTitle = 'Manage Listings';
$extraCSS = '<link rel="stylesheet" href="/assets/css/admin.css">';
require_once '../../includes/db.php';
require_once '../../includes/header.php';

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: listings.php?deleted=1');
    exit;
}

// Handle status change
if (isset($_POST['change_status'])) {
    $pdo->prepare("UPDATE properties SET status = ? WHERE id = ?")->execute([$_POST['new_status'], (int)$_POST['property_id']]);
    header('Location: listings.php?updated=1');
    exit;
}

$filter = $_GET['type'] ?? '';
$query = "SELECT p.*, u.full_name AS host_name, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image FROM properties p JOIN users u ON p.host_id = u.id";
if (in_array($filter, ['rent','buy','land'])) {
    $query .= " WHERE p.listing_type = '$filter'";
}
$query .= " ORDER BY p.created_at DESC";
$properties = $pdo->query($query)->fetchAll();
?>

<section class="admin-section">
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-building"></i> Manage Listings</h1>
            <div class="admin-filters">
                <a href="listings.php" class="filter-btn <?php echo !$filter ? 'active' : ''; ?>">All</a>
                <a href="listings.php?type=rent" class="filter-btn <?php echo $filter === 'rent' ? 'active' : ''; ?>">Rent</a>
                <a href="listings.php?type=buy" class="filter-btn <?php echo $filter === 'buy' ? 'active' : ''; ?>">Buy</a>
                <a href="listings.php?type=land" class="filter-btn <?php echo $filter === 'land' ? 'active' : ''; ?>">Land</a>
            </div>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="host-success"><i class="fas fa-check-circle"></i> Property deleted.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="host-success"><i class="fas fa-check-circle"></i> Status updated.</div>
        <?php endif; ?>

        <div class="admin-card">
            <table class="admin-table">
                <thead><tr><th>Property</th><th>Host</th><th>Type</th><th>Price</th><th>Views</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($properties as $p): ?>
                        <tr>
                            <td><a href="../property.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars(substr($p['title'],0,35)); ?></a></td>
                            <td><?php echo htmlspecialchars($p['host_name']); ?></td>
                            <td><span class="property-badge badge-<?php echo $p['listing_type']; ?>" style="position:static;font-size:.7rem"><?php echo ucfirst($p['listing_type']); ?></span></td>
                            <td>$<?php echo number_format($p['price']); ?></td>
                            <td><?php echo number_format($p['views_count']); ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="property_id" value="<?php echo $p['id']; ?>">
                                    <input type="hidden" name="change_status" value="1">
                                    <select name="new_status" onchange="this.form.submit()" class="role-select">
                                        <option value="active" <?php echo $p['status']==='active'?'selected':''; ?>>Active</option>
                                        <option value="pending" <?php echo $p['status']==='pending'?'selected':''; ?>>Pending</option>
                                        <option value="inactive" <?php echo $p['status']==='inactive'?'selected':''; ?>>Inactive</option>
                                        <option value="sold" <?php echo $p['status']==='sold'?'selected':''; ?>>Sold</option>
                                        <option value="rented" <?php echo $p['status']==='rented'?'selected':''; ?>>Rented</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <a href="listings.php?delete=<?php echo $p['id']; ?>" class="action-delete" onclick="return confirm('Delete this listing?')" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once '../../includes/footer.php'; ?>
