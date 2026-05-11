<?php
$pageTitle = 'My Listings';
$extraCSS = '<link rel="stylesheet" href="/assets/css/host.css">';
require_once '../../includes/db.php';
require_once '../../includes/header.php';

if (!$isLoggedIn || $userRole !== 'host') {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM properties WHERE id = ? AND host_id = ?")->execute([$deleteId, $userId]);
    header('Location: my-listings.php?deleted=1');
    exit;
}

// Fetch host's listings
$stmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image,
           (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) AS avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE property_id = p.id) AS review_count,
           (SELECT COUNT(*) FROM bookings WHERE property_id = p.id) AS booking_count
    FROM properties p WHERE p.host_id = ? ORDER BY p.created_at DESC
");
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();
?>

<section class="host-section">
    <div class="host-container">
        <div class="host-page-header">
            <div>
                <h1><i class="fas fa-list"></i> My Listings</h1>
                <p>Manage your properties on Manzeli</p>
            </div>
            <a href="add-property.php" class="form-submit"><i class="fas fa-plus"></i> Add Property</a>
        </div>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="host-success"><i class="fas fa-check-circle"></i> Property deleted successfully.</div>
        <?php endif; ?>

        <?php if (!empty($listings)): ?>
            <div class="listings-list">
                <?php foreach ($listings as $listing): ?>
                    <div class="listing-row">
                        <div class="listing-row-img">
                            <?php if ($listing['image']): ?>
                                <img src="<?php echo htmlspecialchars($listing['image']); ?>" alt="">
                            <?php else: ?>
                                <div class="property-img-placeholder"><i class="fas fa-image"></i></div>
                            <?php endif; ?>
                            <span class="property-badge badge-<?php echo $listing['listing_type']; ?>">
                                <?php echo match($listing['listing_type']) { 'rent' => 'Rent', 'buy' => 'Sale', 'land' => 'Land' }; ?>
                            </span>
                        </div>
                        <div class="listing-row-info">
                            <h3><a href="../property.php?id=<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['location']); ?></p>
                            <div class="listing-row-stats">
                                <span><i class="fas fa-eye"></i> <?php echo number_format($listing['views_count']); ?> views</span>
                                <span><i class="fas fa-star"></i> <?php echo $listing['avg_rating'] ?? 'New'; ?> (<?php echo $listing['review_count']; ?>)</span>
                                <span><i class="fas fa-calendar-check"></i> <?php echo $listing['booking_count']; ?> bookings</span>
                            </div>
                        </div>
                        <div class="listing-row-price">
                            $<?php echo number_format($listing['price']); ?>
                            <?php if ($listing['listing_type'] === 'rent'): ?>
                                <span>/ <?php echo $listing['price_period'] ?? 'night'; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="listing-row-status">
                            <span class="status-badge status-<?php echo $listing['status']; ?>"><?php echo ucfirst($listing['status']); ?></span>
                        </div>
                        <div class="listing-row-actions">
                            <a href="add-property.php?edit=<?php echo $listing['id']; ?>" class="action-edit" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="my-listings.php?delete=<?php echo $listing['id']; ?>" class="action-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this listing?')"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dash-empty">
                <i class="fas fa-plus-circle"></i>
                <p>You haven't listed any properties yet</p>
                <a href="add-property.php" class="dash-empty-btn">Add Your First Property</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once '../../includes/footer.php'; ?>
