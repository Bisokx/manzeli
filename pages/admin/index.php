<?php
$pageTitle = 'Admin Dashboard';
$extraCSS = '<link rel="stylesheet" href="/assets/css/admin.css">';
require_once '../../includes/db.php';
require_once '../../includes/header.php';

if (!$isLoggedIn || $userRole !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProperties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$totalReviews = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$totalRequests = $pdo->query("SELECT COUNT(*) FROM purchase_requests")->fetchColumn();
$totalViews = $pdo->query("SELECT COALESCE(SUM(views_count),0) FROM properties")->fetchColumn();

$guestCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='guest'")->fetchColumn();
$hostCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role='host'")->fetchColumn();

$rentCount = $pdo->query("SELECT COUNT(*) FROM properties WHERE listing_type='rent'")->fetchColumn();
$buyCount = $pdo->query("SELECT COUNT(*) FROM properties WHERE listing_type='buy'")->fetchColumn();
$landCount = $pdo->query("SELECT COUNT(*) FROM properties WHERE listing_type='land'")->fetchColumn();

$recentUsers = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentProperties = $pdo->query("SELECT p.*, u.full_name AS host_name, (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS image FROM properties p JOIN users u ON p.host_id = u.id ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

$totalMessages = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMessages = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
$recentMessages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<section class="admin-section">
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
            <p>Overview of your Manzeli platform</p>
        </div>

        <div class="admin-stats">
            <div class="admin-stat"><div class="admin-stat-icon stat-icon-primary"><i class="fas fa-users"></i></div><div><div class="admin-stat-num"><?php echo $totalUsers; ?></div><div class="admin-stat-label">Users</div></div></div>
            <div class="admin-stat"><div class="admin-stat-icon stat-icon-coral"><i class="fas fa-building"></i></div><div><div class="admin-stat-num"><?php echo $totalProperties; ?></div><div class="admin-stat-label">Properties</div></div></div>
            <div class="admin-stat"><div class="admin-stat-icon stat-icon-lavender"><i class="fas fa-calendar-check"></i></div><div><div class="admin-stat-num"><?php echo $totalBookings; ?></div><div class="admin-stat-label">Bookings</div></div></div>
            <div class="admin-stat"><div class="admin-stat-icon stat-icon-mint"><i class="fas fa-eye"></i></div><div><div class="admin-stat-num"><?php echo number_format($totalViews); ?></div><div class="admin-stat-label">Total Views</div></div></div>
            <div class="admin-stat"><div class="admin-stat-icon" style="background:rgba(251,191,36,.12);color:var(--golden)"><i class="fas fa-star"></i></div><div><div class="admin-stat-num"><?php echo $totalReviews; ?></div><div class="admin-stat-label">Reviews</div></div></div>
            <div class="admin-stat"><div class="admin-stat-icon" style="background:rgba(56,189,248,.12);color:var(--sky)"><i class="fas fa-envelope"></i></div><div><div class="admin-stat-num"><?php echo $totalMessages; ?><?php if ($unreadMessages > 0): ?> <span style="font-size:.6rem;background:#e74c3c;color:#fff;padding:2px 6px;border-radius:10px;"><?php echo $unreadMessages; ?> new</span><?php endif; ?></div><div class="admin-stat-label">Messages</div></div></div>
        </div>

        <div class="admin-grid">
            <!-- Breakdown -->
            <div class="admin-card">
                <h3 class="admin-card-title"><i class="fas fa-chart-pie"></i> Breakdown</h3>
                <div class="breakdown-list">
                    <div class="breakdown-item"><span>Guests</span><strong><?php echo $guestCount; ?></strong></div>
                    <div class="breakdown-item"><span>Hosts</span><strong><?php echo $hostCount; ?></strong></div>
                    <div class="breakdown-divider"></div>
                    <div class="breakdown-item"><span>Rent Listings</span><strong class="text-primary"><?php echo $rentCount; ?></strong></div>
                    <div class="breakdown-item"><span>Buy Listings</span><strong class="text-coral"><?php echo $buyCount; ?></strong></div>
                    <div class="breakdown-item"><span>Land Listings</span><strong class="text-lavender"><?php echo $landCount; ?></strong></div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="admin-card">
                <h3 class="admin-card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
                <div class="quick-links">
                    <a href="users.php" class="quick-link"><i class="fas fa-users"></i> Manage Users</a>
                    <a href="listings.php" class="quick-link"><i class="fas fa-building"></i> Manage Listings</a>
                    <a href="/pages/host/add-property.php" class="quick-link"><i class="fas fa-plus-circle"></i> Add Property</a>
                    <a href="/pages/listings.php" class="quick-link"><i class="fas fa-search"></i> View Site</a>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="admin-card">
            <div class="admin-card-header"><h3 class="admin-card-title"><i class="fas fa-user-clock"></i> Recent Users</h3><a href="users.php" class="see-all-link">View All <i class="fas fa-arrow-right"></i></a></div>
            <table class="admin-table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
                <tbody>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr><td><?php echo htmlspecialchars($u['full_name']); ?></td><td><?php echo htmlspecialchars($u['email']); ?></td><td><span class="status-badge role-<?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td><td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Properties -->
        <div class="admin-card">
            <div class="admin-card-header"><h3 class="admin-card-title"><i class="fas fa-clock"></i> Recent Listings</h3><a href="listings.php" class="see-all-link">View All <i class="fas fa-arrow-right"></i></a></div>
            <table class="admin-table">
                <thead><tr><th>Property</th><th>Host</th><th>Type</th><th>Price</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($recentProperties as $p): ?>
                        <tr>
                            <td><a href="../property.php?id=<?php echo $p['id']; ?>"><?php echo htmlspecialchars(substr($p['title'],0,40)); ?></a></td>
                            <td><?php echo htmlspecialchars($p['host_name']); ?></td>
                            <td><span class="property-badge badge-<?php echo $p['listing_type']; ?>" style="position:static;font-size:.7rem"><?php echo ucfirst($p['listing_type']); ?></span></td>
                            <td>$<?php echo number_format($p['price']); ?></td>
                            <td><span class="status-badge status-<?php echo $p['status']; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Recent Contact Messages -->
        <div class="admin-card">
            <div class="admin-card-header"><h3 class="admin-card-title"><i class="fas fa-envelope"></i> Recent Messages<?php if ($unreadMessages > 0): ?> <span style="font-size:.7rem;background:#e74c3c;color:#fff;padding:2px 8px;border-radius:10px;margin-left:6px;"><?php echo $unreadMessages; ?> unread</span><?php endif; ?></h3></div>
            <?php if (!empty($recentMessages)): ?>
            <table class="admin-table">
                <thead><tr><th>From</th><th>Email</th><th>Subject</th><th>Message</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($recentMessages as $msg): ?>
                        <tr style="<?php echo !$msg['is_read'] ? 'background:#f0f9ff;font-weight:500;' : ''; ?>">
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($msg['subject'] ?: '—'); ?></td>
                            <td title="<?php echo htmlspecialchars($msg['message']); ?>"><?php echo htmlspecialchars(substr($msg['message'], 0, 50)); ?><?php echo strlen($msg['message']) > 50 ? '...' : ''; ?></td>
                            <td><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></td>
                            <td>
                                <?php if ($msg['is_read']): ?>
                                    <span class="status-badge status-confirmed">Read</span>
                                <?php else: ?>
                                    <form action="mark-read.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="msg_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" class="status-badge status-pending" style="border:none;cursor:pointer;" title="Click to mark as read">Unread</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="reply-btn" onclick="openReply('<?php echo htmlspecialchars($msg['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($msg['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($msg['subject'] ?: 'Your message to Manzeli', ENT_QUOTES); ?>', <?php echo $msg['id']; ?>)" title="Reply">
                                    <i class="fas fa-reply"></i> Reply
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="padding:20px;text-align:center;color:#999;">No contact messages yet.</p>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Reply Modal -->
<div class="reply-overlay" id="replyOverlay" style="display:none;">
    <div class="reply-modal">
        <div class="reply-modal-header">
            <h3><i class="fas fa-reply"></i> Reply to Message</h3>
            <button class="reply-close" onclick="closeReply()">&times;</button>
        </div>
        <form action="reply-message.php" method="POST">
            <input type="hidden" name="msg_id" id="replyMsgId">
            <input type="hidden" name="to_email" id="replyEmail">
            <div class="reply-field">
                <label>To</label>
                <input type="text" id="replyTo" disabled>
            </div>
            <div class="reply-field">
                <label>Subject</label>
                <input type="text" name="subject" id="replySubject">
            </div>
            <div class="reply-field">
                <label>Your Reply</label>
                <textarea name="reply_message" rows="6" placeholder="Type your reply..." required></textarea>
            </div>
            <button type="submit" class="reply-send-btn"><i class="fas fa-paper-plane"></i> Send Reply</button>
        </form>
    </div>
</div>

<?php if (isset($_GET['replied']) && $_GET['replied'] === 'success'): ?>
<script>alert('Reply sent successfully!');</script>
<?php endif; ?>

<style>
.reply-btn{padding:5px 12px;background:var(--primary,#0ABAB5);color:#fff;border:none;border-radius:6px;font-size:.78rem;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all .2s}
.reply-btn:hover{opacity:.85;transform:translateY(-1px)}
.reply-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:2000;display:flex;align-items:center;justify-content:center}
.reply-modal{background:#fff;border-radius:16px;width:500px;max-width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;animation:cbA .3s ease}
.reply-modal-header{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff}
.reply-modal-header h3{margin:0;font-size:1rem;display:flex;align-items:center;gap:8px}
.reply-close{background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:0 4px;line-height:1}
.reply-field{padding:0 24px;margin-top:14px}
.reply-field label{display:block;font-size:.78rem;font-weight:600;color:#888;text-transform:uppercase;letter-spacing:.3px;margin-bottom:4px}
.reply-field input,.reply-field textarea{width:100%;padding:10px 12px;border:1.5px solid #e0d9cd;border-radius:8px;font-size:.9rem;font-family:inherit;outline:none;transition:border-color .2s;background:#fafafa}
.reply-field input:focus,.reply-field textarea:focus{border-color:var(--primary,#0ABAB5)}
.reply-field input:disabled{background:#f0f0f0;color:#666}
.reply-field textarea{resize:vertical}
.reply-send-btn{margin:18px 24px 24px;padding:12px 24px;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:transform .2s}
.reply-send-btn:hover{transform:translateY(-2px)}
@keyframes cbA{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
</style>

<script>
function openReply(email, name, subject, msgId) {
    document.getElementById('replyOverlay').style.display = 'flex';
    document.getElementById('replyMsgId').value = msgId;
    document.getElementById('replyEmail').value = email;
    document.getElementById('replyTo').value = name + ' (' + email + ')';
    document.getElementById('replySubject').value = 'Re: ' + subject;
}
function closeReply() {
    document.getElementById('replyOverlay').style.display = 'none';
}
document.getElementById('replyOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeReply();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
