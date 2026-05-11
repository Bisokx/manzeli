<?php
$pageTitle = 'Dashboard';
$extraCSS = '<link rel="stylesheet" href="/assets/css/dashboard.css">
<style>
.msg-list{display:flex;flex-direction:column;gap:12px}
.msg-card{background:#fff;border:1px solid #e8e4dd;border-radius:12px;padding:16px;transition:all .2s}
.msg-card.msg-unread{background:#f0f9ff;border-color:#b3e0ff}
.msg-header{display:flex;align-items:center;gap:12px;margin-bottom:10px}
.msg-avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:1rem;flex-shrink:0}
.msg-info{flex:1}
.msg-name{font-weight:600;font-size:.9rem;color:var(--secondary,#1E2A3A)}
.msg-meta{font-size:.78rem;color:#999;margin-top:2px}
.msg-new-badge{background:#e74c3c;color:#fff;font-size:.65rem;font-weight:700;padding:2px 8px;border-radius:10px}
.msg-body{font-size:.88rem;color:#555;line-height:1.6;padding:10px 14px;background:#f8f7f4;border-radius:8px}
.msg-reply-btn{margin-top:10px;padding:6px 14px;background:none;border:1px solid #ddd;border-radius:6px;color:#888;font-size:.8rem;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .2s}
.msg-reply-btn:hover{border-color:var(--primary,#0ABAB5);color:var(--primary,#0ABAB5)}
</style>';
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch();

// Favorites (both roles)
$favStmt = $pdo->prepare("
    SELECT f.*, p.title, p.location, p.listing_type, p.price, p.price_period, p.bedrooms, p.bathrooms, p.area_sqm,
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image,
           (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) AS avg_rating
    FROM favorites f JOIN properties p ON f.property_id = p.id WHERE f.user_id = ? ORDER BY f.created_at DESC
");
$favStmt->execute([$userId]);
$favorites = $favStmt->fetchAll();
$totalFavorites = count($favorites);

if ($userRole === 'host') {
    $myListStmt = $pdo->prepare("SELECT p.*, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) AS avg_rating, (SELECT COUNT(*) FROM reviews WHERE property_id = p.id) AS review_count FROM properties p WHERE p.host_id = ? ORDER BY p.created_at DESC");
    $myListStmt->execute([$userId]);
    $myListings = $myListStmt->fetchAll();
    $listingCount = count($myListings);

    $recBookStmt = $pdo->prepare("SELECT b.*, p.title, p.location, p.price, p.price_period, u.full_name AS guest_name, u.email AS guest_email, u.phone AS guest_phone, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image FROM bookings b JOIN properties p ON b.property_id = p.id JOIN users u ON b.user_id = u.id WHERE p.host_id = ? ORDER BY b.created_at DESC");
    $recBookStmt->execute([$userId]);
    $receivedBookings = $recBookStmt->fetchAll();
    $totalReceived = count($receivedBookings);

    $recReqStmt = $pdo->prepare("SELECT pr.*, p.title, p.location, p.price, p.listing_type, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image FROM purchase_requests pr JOIN properties p ON pr.property_id = p.id WHERE p.host_id = ? ORDER BY pr.created_at DESC");
    $recReqStmt->execute([$userId]);
    $receivedRequests = $recReqStmt->fetchAll();
    $totalReceivedReq = count($receivedRequests);

    $viewsStmt = $pdo->prepare("SELECT COALESCE(SUM(views_count),0) as total FROM properties WHERE host_id = ?");
    $viewsStmt->execute([$userId]);
    $totalViews = $viewsStmt->fetch()['total'];
} else {
    $bookStmt = $pdo->prepare("SELECT b.*, p.title, p.location, p.listing_type, p.price, p.price_period, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image FROM bookings b JOIN properties p ON b.property_id = p.id WHERE b.user_id = ? ORDER BY b.created_at DESC");
    $bookStmt->execute([$userId]);
    $bookings = $bookStmt->fetchAll();
    $totalBookings = count($bookings);
}

$tab = $_GET['tab'] ?? 'overview';

// Messages for all users
$msgStmt = $pdo->prepare("
    SELECT m.*, 
           u_sender.full_name AS sender_name, 
           u_receiver.full_name AS receiver_name,
           p.title AS property_title,
           p.id AS prop_id
    FROM messages m 
    LEFT JOIN users u_sender ON m.sender_id = u_sender.id
    LEFT JOIN users u_receiver ON m.receiver_id = u_receiver.id
    LEFT JOIN properties p ON m.property_id = p.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY m.created_at DESC
");
$msgStmt->execute([$userId, $userId]);
$allMessages = $msgStmt->fetchAll();

// Count unread for this user
$unreadMsgStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$unreadMsgStmt->execute([$userId]);
$myUnreadCount = $unreadMsgStmt->fetchColumn();
?>

<section class="dashboard-section">
    <div class="dashboard-container">

        <div class="dash-header">
            <div class="dash-welcome">
                <div class="dash-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                <div>
                    <h1 class="dash-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h1>
                    <p class="dash-subtitle">
                        <span class="dash-role-badge role-<?php echo $user['role']; ?>">
                            <i class="fas fa-<?php echo $user['role'] === 'host' ? 'home' : ($user['role'] === 'admin' ? 'shield-alt' : 'user'); ?>"></i>
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                        Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                    </p>
                </div>
            </div>
            <?php if ($userRole === 'host'): ?>
                <a href="/pages/host/add-property.php" class="dash-add-btn"><i class="fas fa-plus"></i> Add Property</a>
            <?php endif; ?>
        </div>

        <?php if ($userRole === 'host'): ?>
        <!-- ===== HOST DASHBOARD ===== -->
        <div class="dash-stats">
            <div class="stat-card"><div class="stat-icon stat-icon-primary"><i class="fas fa-list"></i></div><div class="stat-info"><div class="stat-number"><?php echo $listingCount; ?></div><div class="stat-label">My Listings</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-mint"><i class="fas fa-eye"></i></div><div class="stat-info"><div class="stat-number"><?php echo number_format($totalViews); ?></div><div class="stat-label">Total Views</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-coral"><i class="fas fa-calendar-check"></i></div><div class="stat-info"><div class="stat-number"><?php echo $totalReceived; ?></div><div class="stat-label">Bookings Received</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-lavender"><i class="fas fa-envelope-open"></i></div><div class="stat-info"><div class="stat-number"><?php echo $totalReceivedReq; ?></div><div class="stat-label">Inquiries</div></div></div>
        </div>

        <div class="dash-tabs">
            <a href="?tab=overview" class="dash-tab <?php echo $tab === 'overview' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Overview</a>
            <a href="?tab=listings" class="dash-tab <?php echo $tab === 'listings' ? 'active' : ''; ?>"><i class="fas fa-list"></i> My Listings</a>
            <a href="?tab=received" class="dash-tab <?php echo $tab === 'received' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> Bookings Received</a>
            <a href="?tab=inquiries" class="dash-tab <?php echo $tab === 'inquiries' ? 'active' : ''; ?>"><i class="fas fa-envelope-open"></i> Inquiries</a>
            <a href="?tab=messages" class="dash-tab <?php echo $tab === 'messages' ? 'active' : ''; ?>"><i class="fas fa-comment-dots"></i> Messages <?php if ($myUnreadCount > 0): ?><span style="background:#e74c3c;color:#fff;font-size:.65rem;padding:2px 6px;border-radius:10px;margin-left:4px;"><?php echo $myUnreadCount; ?></span><?php endif; ?></a>
        </div>

        <div class="dash-content">
            <?php if ($tab === 'overview'): ?>
                <div class="dash-section">
                    <div class="dash-section-header">
                        <h2><i class="fas fa-list"></i> My Listings</h2>
                        <?php if ($listingCount > 0): ?><a href="?tab=listings" class="see-all-link">See All <i class="fas fa-arrow-right"></i></a><?php endif; ?>
                    </div>
                    <?php if (!empty($myListings)): ?>
                        <div class="dash-cards">
                            <?php foreach (array_slice($myListings, 0, 3) as $listing): ?>
                                <a href="property.php?id=<?php echo $listing['id']; ?>" class="dash-card">
                                    <div class="dash-card-img">
                                        <?php if ($listing['image']): ?><img src="<?php echo htmlspecialchars($listing['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?>
                                        <span class="property-badge badge-<?php echo $listing['listing_type']; ?>"><?php echo match($listing['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?></span>
                                    </div>
                                    <div class="dash-card-info">
                                        <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                                        <p class="dash-card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['location']); ?></p>
                                        <div class="dash-card-dates"><span><i class="fas fa-eye"></i> <?php echo number_format($listing['views_count']); ?> views</span><span><i class="fas fa-star"></i> <?php echo $listing['avg_rating'] ?? 'New'; ?></span></div>
                                        <div class="dash-card-price">$<?php echo number_format($listing['price']); ?><?php if ($listing['listing_type'] === 'rent'): ?> <span>/ <?php echo $listing['price_period'] ?? 'night'; ?></span><?php endif; ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-plus-circle"></i><p>You haven't listed any properties yet</p><a href="/pages/host/add-property.php" class="dash-empty-btn">Add Your First Property</a></div>
                    <?php endif; ?>
                </div>

                <div class="dash-section">
                    <div class="dash-section-header">
                        <h2><i class="fas fa-calendar-check"></i> Recent Bookings Received</h2>
                        <?php if ($totalReceived > 0): ?><a href="?tab=received" class="see-all-link">See All <i class="fas fa-arrow-right"></i></a><?php endif; ?>
                    </div>
                    <?php if (!empty($receivedBookings)): ?>
                        <div class="dash-list">
                            <?php foreach (array_slice($receivedBookings, 0, 3) as $rb): ?>
                                <div class="dash-list-item">
                                    <div class="dash-list-img"><?php if ($rb['image']): ?><img src="<?php echo htmlspecialchars($rb['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?></div>
                                    <div class="dash-list-info">
                                        <h3><?php echo htmlspecialchars($rb['title']); ?></h3>
                                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($rb['guest_name']); ?> · <?php echo htmlspecialchars($rb['guest_phone'] ?? $rb['guest_email']); ?></p>
                                        <div class="dash-list-meta"><span><i class="fas fa-sign-in-alt"></i> <?php echo date('M d', strtotime($rb['check_in'])); ?></span><span><i class="fas fa-sign-out-alt"></i> <?php echo date('M d, Y', strtotime($rb['check_out'])); ?></span><span><i class="fas fa-users"></i> <?php echo $rb['guests']; ?> guest<?php echo $rb['guests'] > 1 ? 's' : ''; ?></span></div>
                                    </div>
                                    <div class="dash-list-right"><div class="dash-list-price">$<?php echo number_format($rb['total_price']); ?></div><span class="status-badge status-<?php echo $rb['status']; ?>"><?php echo ucfirst($rb['status']); ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-calendar"></i><p>No bookings received yet. Share your listings to attract guests!</p></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'listings'): ?>
                <div class="dash-section">
                    <div class="dash-section-header"><h2 class="dash-section-title">My Listings</h2><a href="/pages/host/add-property.php" class="dash-add-btn"><i class="fas fa-plus"></i> Add Property</a></div>
                    <?php if (!empty($myListings)): ?>
                        <div class="dash-cards full-grid">
                            <?php foreach ($myListings as $listing): ?>
                                <a href="property.php?id=<?php echo $listing['id']; ?>" class="dash-card">
                                    <div class="dash-card-img"><?php if ($listing['image']): ?><img src="<?php echo htmlspecialchars($listing['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?><span class="property-badge badge-<?php echo $listing['listing_type']; ?>"><?php echo match($listing['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?></span></div>
                                    <div class="dash-card-info">
                                        <h3><?php echo htmlspecialchars($listing['title']); ?></h3>
                                        <p class="dash-card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($listing['location']); ?></p>
                                        <div class="dash-card-dates"><span><i class="fas fa-eye"></i> <?php echo number_format($listing['views_count']); ?> views</span><span><i class="fas fa-star"></i> <?php echo $listing['avg_rating'] ?? 'New'; ?> (<?php echo $listing['review_count']; ?>)</span></div>
                                        <div class="dash-card-price">$<?php echo number_format($listing['price']); ?><?php if ($listing['listing_type'] === 'rent'): ?> <span>/ <?php echo $listing['price_period'] ?? 'night'; ?></span><?php endif; ?></div>
                                        <span class="status-badge status-<?php echo $listing['status']; ?>"><?php echo ucfirst($listing['status']); ?></span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-plus-circle"></i><p>You haven't listed any properties yet</p><a href="/pages/host/add-property.php" class="dash-empty-btn">Add Your First Property</a></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'received'): ?>
                <div class="dash-section">
                    <h2 class="dash-section-title">Bookings Received</h2>
                    <?php if (!empty($receivedBookings)): ?>
                        <div class="dash-list">
                            <?php foreach ($receivedBookings as $rb): ?>
                                <div class="dash-list-item">
                                    <div class="dash-list-img"><?php if ($rb['image']): ?><img src="<?php echo htmlspecialchars($rb['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?></div>
                                    <div class="dash-list-info">
                                        <h3><?php echo htmlspecialchars($rb['title']); ?></h3>
                                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($rb['guest_name']); ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($rb['guest_phone'] ?? 'N/A'); ?> · <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($rb['guest_email']); ?></p>
                                        <div class="dash-list-meta"><span><i class="fas fa-sign-in-alt"></i> <?php echo date('M d, Y', strtotime($rb['check_in'])); ?></span><span><i class="fas fa-sign-out-alt"></i> <?php echo date('M d, Y', strtotime($rb['check_out'])); ?></span><span><i class="fas fa-users"></i> <?php echo $rb['guests']; ?> guest<?php echo $rb['guests'] > 1 ? 's' : ''; ?></span></div>
                                    </div>
                                    <div class="dash-list-right"><div class="dash-list-price">$<?php echo number_format($rb['total_price']); ?></div><span class="status-badge status-<?php echo $rb['status']; ?>"><?php echo ucfirst($rb['status']); ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-calendar"></i><p>No bookings received yet</p></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'inquiries'): ?>
                <div class="dash-section">
                    <h2 class="dash-section-title">Purchase / Info Inquiries</h2>
                    <?php if (!empty($receivedRequests)): ?>
                        <div class="dash-list">
                            <?php foreach ($receivedRequests as $req): ?>
                                <div class="dash-list-item">
                                    <div class="dash-list-img"><?php if ($req['image']): ?><img src="<?php echo htmlspecialchars($req['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?></div>
                                    <div class="dash-list-info">
                                        <h3><?php echo htmlspecialchars($req['title']); ?></h3>
                                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($req['full_name']); ?> · <i class="fas fa-phone"></i> <?php echo htmlspecialchars($req['phone']); ?></p>
                                        <p class="dash-list-msg"><i class="fas fa-comment"></i> <?php echo htmlspecialchars($req['message'] ?? 'No message'); ?></p>
                                    </div>
                                    <div class="dash-list-right"><div class="dash-list-price">$<?php echo number_format($req['price']); ?></div><span class="status-badge status-<?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-envelope-open"></i><p>No inquiries received yet</p></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'messages'): ?>
                <?php
                $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0")->execute([$userId]);
                ?>
                <div class="dash-section">
                    <h2 class="dash-section-title"><i class="fas fa-comment-dots"></i> Messages</h2>
                    <?php if (!empty($allMessages)): ?>
                        <div class="msg-list">
                            <?php foreach ($allMessages as $msg): ?>
                                <div class="msg-card <?php echo (!$msg['is_read'] && $msg['receiver_id'] == $userId) ? 'msg-unread' : ''; ?>">
                                    <div class="msg-header">
                                        <div class="msg-avatar">
                                            <?php echo strtoupper(substr($msg['sender_id'] == $userId ? $msg['receiver_name'] : $msg['sender_name'], 0, 1)); ?>
                                        </div>
                                        <div class="msg-info">
                                            <div class="msg-name">
                                                <?php if ($msg['sender_id'] == $userId): ?>
                                                    <span style="color:#999;font-size:.8rem;">You →</span> <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($msg['sender_name']); ?> <span style="color:#999;font-size:.8rem;">→ You</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="msg-meta">
                                                <?php echo date('M d, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                                <?php if ($msg['property_title']): ?>
                                                    • <a href="/pages/property.php?id=<?php echo $msg['prop_id']; ?>" style="color:var(--primary);text-decoration:none;"><?php echo htmlspecialchars(substr($msg['property_title'], 0, 30)); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!$msg['is_read'] && $msg['receiver_id'] == $userId): ?>
                                            <span class="msg-new-badge">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="msg-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    
                                    <?php if ($msg['sender_id'] != $userId): ?>
                                        <div class="msg-reply-toggle">
                                            <button type="button" class="msg-reply-btn" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'; this.style.display='none';">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                            <form action="/includes/send-message.php" method="POST" style="display:none;">
                                                <input type="hidden" name="receiver_id" value="<?php echo $msg['sender_id']; ?>">
                                                <input type="hidden" name="property_id" value="<?php echo $msg['property_id'] ?: 0; ?>">
                                                <input type="hidden" name="redirect" value="/pages/dashboard.php?tab=messages">
                                                <div style="display:flex;gap:8px;margin-top:8px;">
                                                    <textarea name="message" rows="2" placeholder="Type your reply..." required style="flex:1;padding:8px 12px;border:1.5px solid #e0d9cd;border-radius:8px;font-size:.85rem;font-family:inherit;outline:none;resize:none;"></textarea>
                                                    <button type="submit" style="padding:8px 14px;background:var(--primary,#0ABAB5);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.85rem;white-space:nowrap;"><i class="fas fa-paper-plane"></i></button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-comment-dots"></i><p>No messages yet</p></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <?php else: ?>
        <!-- ===== GUEST DASHBOARD ===== -->
        <div class="dash-stats">
            <div class="stat-card"><div class="stat-icon stat-icon-primary"><i class="fas fa-calendar-check"></i></div><div class="stat-info"><div class="stat-number"><?php echo $totalBookings; ?></div><div class="stat-label">Bookings</div></div></div>
            <div class="stat-card"><div class="stat-icon stat-icon-coral"><i class="fas fa-heart"></i></div><div class="stat-info"><div class="stat-number"><?php echo $totalFavorites; ?></div><div class="stat-label">Saved</div></div></div>
        </div>

        <div class="dash-tabs">
            <a href="?tab=overview" class="dash-tab <?php echo $tab === 'overview' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Overview</a>
            <a href="?tab=bookings" class="dash-tab <?php echo $tab === 'bookings' ? 'active' : ''; ?>"><i class="fas fa-calendar-check"></i> My Bookings</a>
            <a href="?tab=favorites" class="dash-tab <?php echo $tab === 'favorites' ? 'active' : ''; ?>"><i class="fas fa-heart"></i> Saved</a>
            <a href="?tab=messages" class="dash-tab <?php echo $tab === 'messages' ? 'active' : ''; ?>"><i class="fas fa-comment-dots"></i> Messages <?php if ($myUnreadCount > 0): ?><span style="background:#e74c3c;color:#fff;font-size:.65rem;padding:2px 6px;border-radius:10px;margin-left:4px;"><?php echo $myUnreadCount; ?></span><?php endif; ?></a>
        </div>

        <div class="dash-content">
            <?php if ($tab === 'overview'): ?>
                <div class="dash-section">
                    <div class="dash-section-header"><h2><i class="fas fa-calendar-check"></i> Recent Bookings</h2><?php if ($totalBookings > 0): ?><a href="?tab=bookings" class="see-all-link">See All <i class="fas fa-arrow-right"></i></a><?php endif; ?></div>
                    <?php if (!empty($bookings)): ?>
                        <div class="dash-cards">
                            <?php foreach (array_slice($bookings, 0, 3) as $booking): ?>
                                <a href="property.php?id=<?php echo $booking['property_id']; ?>" class="dash-card">
                                    <div class="dash-card-img"><?php if ($booking['image']): ?><img src="<?php echo htmlspecialchars($booking['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></div>
                                    <div class="dash-card-info">
                                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                        <p class="dash-card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?></p>
                                        <div class="dash-card-dates"><span><i class="fas fa-sign-in-alt"></i> <?php echo date('M d', strtotime($booking['check_in'])); ?></span><span><i class="fas fa-sign-out-alt"></i> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></span></div>
                                        <div class="dash-card-price">$<?php echo number_format($booking['total_price']); ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-calendar"></i><p>No bookings yet</p><a href="/pages/listings.php?type=rent" class="dash-empty-btn">Browse Rentals</a></div>
                    <?php endif; ?>
                </div>
                <div class="dash-section">
                    <div class="dash-section-header"><h2><i class="fas fa-heart"></i> Saved Properties</h2><?php if ($totalFavorites > 0): ?><a href="?tab=favorites" class="see-all-link">See All <i class="fas fa-arrow-right"></i></a><?php endif; ?></div>
                    <?php if (!empty($favorites)): ?>
                        <div class="dash-cards">
                            <?php foreach (array_slice($favorites, 0, 3) as $fav): ?>
                                <a href="property.php?id=<?php echo $fav['property_id']; ?>" class="dash-card">
                                    <div class="dash-card-img"><?php if ($fav['image']): ?><img src="<?php echo htmlspecialchars($fav['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?><span class="property-badge badge-<?php echo $fav['listing_type']; ?>"><?php echo match($fav['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?></span></div>
                                    <div class="dash-card-info">
                                        <h3><?php echo htmlspecialchars($fav['title']); ?></h3>
                                        <p class="dash-card-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fav['location']); ?></p>
                                        <div class="dash-card-price">$<?php echo number_format($fav['price']); ?><?php if ($fav['listing_type'] === 'rent'): ?> <span>/ <?php echo $fav['price_period'] ?? 'night'; ?></span><?php endif; ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-heart"></i><p>No saved properties yet</p><a href="/pages/listings.php" class="dash-empty-btn">Explore Properties</a></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'bookings'): ?>
                <div class="dash-section">
                    <h2 class="dash-section-title">My Bookings</h2>
                    <?php if (!empty($bookings)): ?>
                        <div class="dash-list">
                            <?php foreach ($bookings as $booking): ?>
                                <a href="property.php?id=<?php echo $booking['property_id']; ?>" class="dash-list-item">
                                    <div class="dash-list-img"><?php if ($booking['image']): ?><img src="<?php echo htmlspecialchars($booking['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?></div>
                                    <div class="dash-list-info">
                                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?></p>
                                        <div class="dash-list-meta"><span><i class="fas fa-sign-in-alt"></i> <?php echo date('M d, Y', strtotime($booking['check_in'])); ?></span><span><i class="fas fa-sign-out-alt"></i> <?php echo date('M d, Y', strtotime($booking['check_out'])); ?></span><span><i class="fas fa-users"></i> <?php echo $booking['guests']; ?> guest<?php echo $booking['guests'] > 1 ? 's' : ''; ?></span></div>
                                    </div>
                                    <div class="dash-list-right"><div class="dash-list-price">$<?php echo number_format($booking['total_price']); ?></div><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-calendar"></i><p>You haven't made any bookings yet</p><a href="/pages/listings.php?type=rent" class="dash-empty-btn">Browse Rentals</a></div>
                    <?php endif; ?>
                </div>

            <?php elseif ($tab === 'favorites'): ?>
                <div class="dash-section">
                    <h2 class="dash-section-title">Saved Properties</h2>
                    <?php if (!empty($favorites)): ?>
                        <div class="dash-cards full-grid">
                            <?php foreach ($favorites as $fav): ?>
                                <a href="property.php?id=<?php echo $fav['property_id']; ?>" class="property-card">
                                    <div class="property-img"><?php if ($fav['image']): ?><img src="<?php echo htmlspecialchars($fav['image']); ?>" alt=""><?php else: ?><div class="property-img-placeholder"><i class="fas fa-image"></i></div><?php endif; ?><span class="property-badge badge-<?php echo $fav['listing_type']; ?>"><?php echo match($fav['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?></span></div>
                                    <div class="property-info">
                                        <div class="property-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fav['location']); ?></div>
                                        <h3 class="property-title"><?php echo htmlspecialchars($fav['title']); ?></h3>
                                        <div class="property-features"><?php if ($fav['bedrooms']): ?><span class="property-feature"><i class="fas fa-bed"></i> <?php echo $fav['bedrooms']; ?> Bed<?php echo $fav['bedrooms'] > 1 ? 's' : ''; ?></span><?php endif; ?><?php if ($fav['bathrooms']): ?><span class="property-feature"><i class="fas fa-bath"></i> <?php echo $fav['bathrooms']; ?> Bath<?php echo $fav['bathrooms'] > 1 ? 's' : ''; ?></span><?php endif; ?><?php if ($fav['area_sqm']): ?><span class="property-feature"><i class="fas fa-ruler-combined"></i> <?php echo number_format($fav['area_sqm']); ?> m²</span><?php endif; ?></div>
                                        <div class="property-footer"><div class="property-price">$<?php echo number_format($fav['price']); ?><?php if ($fav['listing_type'] === 'rent'): ?> <span>/ <?php echo $fav['price_period'] ?? 'night'; ?></span><?php endif; ?></div><div class="property-rating"><i class="fas fa-star"></i> <?php echo $fav['avg_rating'] ?? 'New'; ?></div></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-heart"></i><p>No saved properties yet. Click the heart icon on any listing to save it!</p><a href="/pages/listings.php" class="dash-empty-btn">Explore Properties</a></div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

            <?php if ($tab === 'messages'): ?>
                <?php
                // Mark messages as read when viewing
                $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND is_read = 0")->execute([$userId]);
                ?>
                <div class="dash-section">
                    <h2 class="dash-section-title"><i class="fas fa-comment-dots"></i> Messages</h2>
                    <?php if (!empty($allMessages)): ?>
                        <div class="msg-list">
                            <?php foreach ($allMessages as $msg): ?>
                                <div class="msg-card <?php echo (!$msg['is_read'] && $msg['receiver_id'] == $userId) ? 'msg-unread' : ''; ?>">
                                    <div class="msg-header">
                                        <div class="msg-avatar">
                                            <?php echo strtoupper(substr($msg['sender_id'] == $userId ? $msg['receiver_name'] : $msg['sender_name'], 0, 1)); ?>
                                        </div>
                                        <div class="msg-info">
                                            <div class="msg-name">
                                                <?php if ($msg['sender_id'] == $userId): ?>
                                                    <span style="color:#999;font-size:.8rem;">You →</span> <?php echo htmlspecialchars($msg['receiver_name']); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($msg['sender_name']); ?> <span style="color:#999;font-size:.8rem;">→ You</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="msg-meta">
                                                <?php echo date('M d, Y \a\t g:i A', strtotime($msg['created_at'])); ?>
                                                <?php if ($msg['property_title']): ?>
                                                    • <a href="/pages/property.php?id=<?php echo $msg['prop_id']; ?>" style="color:var(--primary);text-decoration:none;"><?php echo htmlspecialchars(substr($msg['property_title'], 0, 30)); ?></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!$msg['is_read'] && $msg['receiver_id'] == $userId): ?>
                                            <span class="msg-new-badge">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="msg-body"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    
                                    <?php if ($msg['sender_id'] != $userId): ?>
                                        <!-- Reply form -->
                                        <div class="msg-reply-toggle">
                                            <button type="button" class="msg-reply-btn" onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'; this.style.display='none';">
                                                <i class="fas fa-reply"></i> Reply
                                            </button>
                                            <form action="/includes/send-message.php" method="POST" style="display:none;">
                                                <input type="hidden" name="receiver_id" value="<?php echo $msg['sender_id']; ?>">
                                                <input type="hidden" name="property_id" value="<?php echo $msg['property_id'] ?: 0; ?>">
                                                <input type="hidden" name="redirect" value="/pages/dashboard.php?tab=messages">
                                                <div style="display:flex;gap:8px;margin-top:8px;">
                                                    <textarea name="message" rows="2" placeholder="Type your reply..." required style="flex:1;padding:8px 12px;border:1.5px solid #e0d9cd;border-radius:8px;font-size:.85rem;font-family:inherit;outline:none;resize:none;"></textarea>
                                                    <button type="submit" style="padding:8px 14px;background:var(--primary,#0ABAB5);color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:.85rem;white-space:nowrap;"><i class="fas fa-paper-plane"></i></button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="dash-empty"><i class="fas fa-comment-dots"></i><p>No messages yet</p></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
