<?php
$pageTitle = 'Property Details';
$extraCSS = '<link rel="stylesheet" href="/assets/css/property.css">
<style>
.payment-options{display:flex;gap:10px;margin-top:4px}
.payment-option{flex:1;cursor:pointer}
.payment-option input{display:none}
.payment-option-box{display:flex;flex-direction:column;align-items:center;gap:6px;padding:12px 8px;border:2px solid #e0d9cd;border-radius:10px;transition:all .2s;font-size:13px;color:#666}
.payment-option-box i{font-size:20px;color:#999;transition:color .2s}
.payment-option input:checked+.payment-option-box{border-color:var(--primary,#0ABAB5);background:rgba(10,186,181,.06);color:var(--primary,#0ABAB5)}
.payment-option input:checked+.payment-option-box i{color:var(--primary,#0ABAB5)}
.payment-option-box:hover{border-color:var(--primary,#0ABAB5)}
#creditCardFields{margin-top:8px;animation:cbA .3s ease}
.avail-calendar{background:#fff;border:1px solid #e8e4dd;border-radius:12px;padding:16px;margin-bottom:16px}
.avail-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}
.avail-month{font-weight:600;font-size:.95rem;color:var(--secondary,#1E2A3A)}
.avail-nav{background:none;border:1px solid #e0d9cd;border-radius:8px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#666;transition:all .2s}
.avail-nav:hover{border-color:var(--primary,#0ABAB5);color:var(--primary,#0ABAB5)}
.avail-days-header{display:grid;grid-template-columns:repeat(7,1fr);text-align:center;margin-bottom:6px}
.avail-days-header span{font-size:.72rem;font-weight:600;color:#999;text-transform:uppercase;padding:4px 0}
.avail-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px}
.avail-day{text-align:center;padding:7px 2px;font-size:.8rem;border-radius:6px;color:#333;position:relative}
.avail-day.empty{visibility:hidden}
.avail-day.past{color:#ccc}
.avail-day.booked{background:#ffe0e0;color:#c44b4b;font-weight:600}
.avail-day.today{border:2px solid var(--primary,#0ABAB5);font-weight:700}
.avail-day.available{background:#e8f8f0;color:#27ae60}
.avail-legend{display:flex;gap:16px;justify-content:center;margin-top:10px;font-size:.75rem;color:#888}
.avail-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:4px;vertical-align:middle}
.avail-free{background:#e8f8f0}
.avail-booked{background:#ffe0e0}
.write-review-card{background:var(--off-white,#f8fafb);border:1px solid #e8e4dd;border-radius:12px;padding:24px;margin-top:20px}
.write-review-title{font-size:1rem;font-weight:600;color:var(--secondary,#1E2A3A);margin:0 0 16px;display:flex;align-items:center;gap:8px}
.write-review-title i{color:var(--primary,#0ABAB5)}
.review-form{display:flex;flex-direction:column;gap:14px}
.star-rating-input label,.review-field label{display:block;font-size:.82rem;font-weight:600;color:var(--charcoal,#333);margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.star-select{display:flex;gap:6px}
.star-select i{font-size:1.6rem;color:#ddd;cursor:pointer;transition:color .15s}
.star-select i.selected,.star-select i.hover{color:#f5a623}
.review-field textarea{width:100%;padding:12px 14px;border:1.5px solid #e0d9cd;border-radius:8px;font-size:.9rem;font-family:inherit;background:#fff;resize:vertical;outline:none;transition:border-color .2s}
.review-field textarea:focus{border-color:var(--primary,#0ABAB5)}
.review-submit-btn{padding:12px 24px;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:transform .2s,box-shadow .2s}
.review-submit-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(10,186,181,.3)}
.login-to-review{margin-top:16px;padding:14px;text-align:center;background:var(--off-white,#f8fafb);border-radius:8px;border:1px dashed #ddd}
.login-to-review p{margin:0;color:#888;font-size:.9rem}
.login-to-review a{color:var(--primary,#0ABAB5);font-weight:600;text-decoration:none}
.login-to-review a:hover{text-decoration:underline}
</style>';
require_once '../includes/db.php';
require_once '../includes/header.php';

// Get property ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo '<div class="container" style="padding:100px 0;text-align:center;"><h2>Property not found</h2><p>The property you are looking for does not exist.</p><a href="listings.php" class="btn-primary" style="margin-top:20px;display:inline-flex;">← Back to Listings</a></div>';
    require_once '../includes/footer.php';
    exit;
}

// Increment view count
$pdo->prepare("UPDATE properties SET views_count = views_count + 1 WHERE id = ?")->execute([$id]);

// Fetch property with host info
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name AS host_name, u.email AS host_email, u.phone AS host_phone, 
           u.profile_image AS host_image, u.created_at AS host_since
    FROM properties p
    JOIN users u ON p.host_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    echo '<div class="container" style="padding:100px 0;text-align:center;"><h2>Property not found</h2><p>The property you are looking for does not exist.</p><a href="listings.php" class="btn-primary" style="margin-top:20px;display:inline-flex;">← Back to Listings</a></div>';
    require_once '../includes/footer.php';
    exit;
}

// Fetch images
$imgStmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ? ORDER BY is_main DESC, id ASC");
$imgStmt->execute([$id]);
$images = $imgStmt->fetchAll();

// Fetch amenities
$amenStmt = $pdo->prepare("
    SELECT a.name, a.icon 
    FROM property_amenities pa 
    JOIN amenities a ON pa.amenity_id = a.id 
    WHERE pa.property_id = ?
");
$amenStmt->execute([$id]);
$amenities = $amenStmt->fetchAll();

// Fetch reviews with user info
$revStmt = $pdo->prepare("
    SELECT r.*, u.full_name, u.profile_image 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.property_id = ? 
    ORDER BY r.created_at DESC
");
$revStmt->execute([$id]);
$reviews = $revStmt->fetchAll();

// Calculate average rating
$avgStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews WHERE property_id = ?");
$avgStmt->execute([$id]);
$ratingData = $avgStmt->fetch();
$avgRating = round($ratingData['avg_rating'] ?? 0, 1);
$totalReviews = $ratingData['total_reviews'] ?? 0;

// Fetch similar properties (same listing_type, excluding current)
$simStmt = $pdo->prepare("
    SELECT p.*, 
           (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS main_image,
           (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) AS avg_rating
    FROM properties p 
    WHERE p.listing_type = ? AND p.id != ? AND p.status = 'active' 
    ORDER BY p.views_count DESC 
    LIMIT 3
");
$simStmt->execute([$property['listing_type'], $id]);
$similar = $simStmt->fetchAll();

// Fetch booked dates for this property
$bookedStmt = $pdo->prepare("SELECT check_in, check_out FROM bookings WHERE property_id = ? AND status = 'confirmed'");
$bookedStmt->execute([$id]);
$bookedDates = $bookedStmt->fetchAll();

// Build array of booked date ranges for JS
$bookedRanges = [];
foreach ($bookedDates as $bd) {
    $bookedRanges[] = ['start' => $bd['check_in'], 'end' => $bd['check_out']];
}

// Badge and price display
$badgeClass = 'badge-' . $property['listing_type'];
$badgeText = match($property['listing_type']) {
    'rent' => 'For Rent',
    'buy'  => 'For Sale',
    'land' => 'Land',
};
$priceDisplay = '$' . number_format($property['price']);
if ($property['listing_type'] === 'rent') {
    $priceDisplay .= ' <span>/ ' . ($property['price_period'] ?? 'night') . '</span>';
}

// Check if user has favorited this property
$isFavorited = false;
$hasReviewed = false;
if ($isLoggedIn) {
    $favStmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND property_id = ?");
    $favStmt->execute([$_SESSION['user_id'], $id]);
    $isFavorited = $favStmt->fetch() ? true : false;
    
    $revCheckStmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND property_id = ?");
    $revCheckStmt->execute([$_SESSION['user_id'], $id]);
    $hasReviewed = $revCheckStmt->fetch() ? true : false;
}
?>

<!-- ===== PROPERTY DETAILS PAGE ===== -->
<section class="property-detail-section">
    <div class="property-detail-container">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="/index.php">Home</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/pages/listings.php?type=<?php echo $property['listing_type']; ?>">
                <?php echo ucfirst($property['listing_type'] === 'land' ? 'Land' : $property['listing_type']); ?>
            </a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($property['title']); ?></span>
        </div>

        <!-- ===== IMAGE GALLERY ===== -->
        <div class="gallery">
            <div class="gallery-main">
                <?php if (!empty($images)): ?>
                    <img src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" id="mainImage">
                <?php else: ?>
                    <div class="property-img-placeholder"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <span class="property-badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                <div class="gallery-nav">
                    <button class="gallery-nav-btn" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                    <span class="gallery-counter" id="galleryCounter">1 / <?php echo count($images); ?></span>
                    <button class="gallery-nav-btn" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="gallery-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="changeImage(<?php echo $i; ?>)">
                            <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Photo <?php echo $i + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ===== CONTENT GRID ===== -->
        <div class="detail-grid">

            <!-- LEFT COLUMN — Property Info -->
            <div class="detail-main">

                <!-- Title & Meta -->
                <div class="detail-header">
                    <div class="detail-header-top">
                        <div>
                            <h1 class="detail-title"><?php echo htmlspecialchars($property['title']); ?></h1>
                            <div class="detail-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php echo htmlspecialchars($property['location']); ?>, Lebanon
                            </div>
                        </div>
                        <div class="detail-actions">
                            <button class="action-btn <?php echo $isFavorited ? 'active' : ''; ?>" id="favBtn" title="Save to favorites">
                                <i class="<?php echo $isFavorited ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                            <button class="action-btn" title="Share" onclick="shareProperty()">
                                <i class="fas fa-share-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="detail-meta">
                        <div class="detail-price"><?php echo $priceDisplay; ?></div>
                        <div class="detail-stats">
                            <span class="detail-rating">
                                <i class="fas fa-star"></i> <?php echo $avgRating ?: 'New'; ?>
                                <span>(<?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?>)</span>
                            </span>
                            <span class="detail-views">
                                <i class="fas fa-eye"></i> <?php echo number_format($property['views_count']); ?> views
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Key Features -->
                <div class="detail-features">
                    <?php if ($property['listing_type'] !== 'land'): ?>
                        <?php if ($property['bedrooms']): ?>
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-bed"></i></div>
                                <div><strong><?php echo $property['bedrooms']; ?></strong> Bedroom<?php echo $property['bedrooms'] > 1 ? 's' : ''; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($property['bathrooms']): ?>
                            <div class="feature-item">
                                <div class="feature-icon"><i class="fas fa-bath"></i></div>
                                <div><strong><?php echo $property['bathrooms']; ?></strong> Bathroom<?php echo $property['bathrooms'] > 1 ? 's' : ''; ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($property['area_sqm']): ?>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-ruler-combined"></i></div>
                            <div><strong><?php echo number_format($property['area_sqm']); ?></strong> m²</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($property['floor_number']): ?>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-building"></i></div>
                            <div>Floor <strong><?php echo $property['floor_number']; ?></strong></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($property['building_age']): ?>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-calendar-alt"></i></div>
                            <div><strong><?php echo $property['building_age']; ?></strong> yr<?php echo $property['building_age'] > 1 ? 's' : ''; ?> old</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($property['zone_type']): ?>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-map"></i></div>
                            <div><strong><?php echo ucfirst($property['zone_type']); ?></strong> Zone</div>
                        </div>
                    <?php endif; ?>
                    <?php if ($property['property_type'] && $property['listing_type'] !== 'land'): ?>
                        <div class="feature-item">
                            <div class="feature-icon"><i class="fas fa-home"></i></div>
                            <div><strong><?php echo ucfirst($property['property_type']); ?></strong></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="detail-section">
                    <h2 class="detail-section-title">About This Property</h2>
                    <p class="detail-description"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                </div>

                <!-- Amenities -->
                <?php if (!empty($amenities)): ?>
                    <div class="detail-section">
                        <h2 class="detail-section-title">Amenities</h2>
                        <div class="amenities-grid">
                            <?php foreach ($amenities as $amenity): ?>
                                <div class="amenity-item">
                                    <i class="<?php echo htmlspecialchars($amenity['icon']); ?>"></i>
                                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews -->
                <div class="detail-section">
                    <div class="reviews-header">
                        <h2 class="detail-section-title">
                            Reviews
                            <?php if ($totalReviews > 0): ?>
                                <span class="reviews-badge"><?php echo $totalReviews; ?></span>
                            <?php endif; ?>
                        </h2>
                        <?php if ($avgRating > 0): ?>
                            <div class="reviews-avg">
                                <div class="reviews-avg-number"><?php echo $avgRating; ?></div>
                                <div class="reviews-avg-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= round($avgRating) ? '' : ' empty'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-card">
                                    <div class="review-top">
                                        <div class="review-user">
                                            <div class="review-avatar">
                                                <?php echo strtoupper(substr($review['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="review-name"><?php echo htmlspecialchars($review['full_name']); ?></div>
                                                <div class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="review-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : ' empty'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="fas fa-comment-dots"></i>
                            <p>No reviews yet. Be the first to review this property!</p>
                        </div>
                    <?php endif; ?>

                    <!-- Write Review Form -->
                    <?php if ($isLoggedIn && !$hasReviewed && $property['host_id'] != $_SESSION['user_id']): ?>
                        <?php if (isset($_GET['review']) && $_GET['review'] === 'success'): ?>
                            <div style="background:#d4edda;color:#155724;padding:12px 16px;border-radius:8px;font-size:14px;margin-top:16px;">
                                <i class="fas fa-check-circle"></i> Your review has been submitted. Thank you!
                            </div>
                        <?php else: ?>
                            <div class="write-review-card">
                                <h3 class="write-review-title"><i class="fas fa-pen"></i> Write a Review</h3>
                                <form action="/includes/submit-review.php" method="POST" class="review-form">
                                    <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                                    
                                    <div class="star-rating-input">
                                        <label>Your Rating</label>
                                        <div class="star-select" id="starSelect">
                                            <i class="fas fa-star" data-rating="1"></i>
                                            <i class="fas fa-star" data-rating="2"></i>
                                            <i class="fas fa-star" data-rating="3"></i>
                                            <i class="fas fa-star" data-rating="4"></i>
                                            <i class="fas fa-star" data-rating="5"></i>
                                        </div>
                                        <input type="hidden" name="rating" id="ratingInput" value="0" required>
                                    </div>
                                    
                                    <div class="review-field">
                                        <label>Your Review</label>
                                        <textarea name="comment" rows="4" placeholder="Share your experience with this property..." required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="review-submit-btn">
                                        <i class="fas fa-paper-plane"></i> Submit Review
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($isLoggedIn && $hasReviewed): ?>
                        <div style="background:#e8f4fd;color:#0c5460;padding:12px 16px;border-radius:8px;font-size:14px;margin-top:16px;">
                            <i class="fas fa-check-circle"></i> You have already reviewed this property.
                        </div>
                    <?php elseif (!$isLoggedIn): ?>
                        <div class="login-to-review">
                            <p><i class="fas fa-lock"></i> <a href="/pages/login.php">Log in</a> to write a review</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- RIGHT COLUMN — Sidebar -->
            <aside class="detail-sidebar">

                <!-- Booking / Contact Card -->
                <div class="sidebar-card booking-card">
                    <div class="booking-price"><?php echo $priceDisplay; ?></div>

                    <?php if (isset($_GET['error']) && $_GET['error'] === 'dates_taken'): ?>
                        <div style="background:#ffe0e0;color:#c00;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px;">
                            <i class="fas fa-exclamation-circle"></i> These dates are already booked. Please choose different dates.
                        </div>
                    <?php endif; ?>

                    <?php if ($isLoggedIn && $userRole === 'host' && $property['host_id'] == $_SESSION['user_id']): ?>
                        <!-- HOST viewing their OWN property — Show manage options -->
                        <?php if ($property['listing_type'] === 'rent'): ?>
                        <div class="avail-calendar">
                            <div class="avail-header">
                                <button type="button" class="avail-nav" id="calPrev"><i class="fas fa-chevron-left"></i></button>
                                <span class="avail-month" id="calMonth"></span>
                                <button type="button" class="avail-nav" id="calNext"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="avail-days-header">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="avail-grid" id="calGrid"></div>
                            <div class="avail-legend">
                                <span><i class="avail-dot avail-free"></i> Available</span>
                                <span><i class="avail-dot avail-booked"></i> Booked</span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="host-own-notice">
                            <div class="host-own-icon"><i class="fas fa-crown"></i></div>
                            <p>This is your listing</p>
                            <a href="/pages/host/add-property.php?edit=<?php echo $id; ?>" class="booking-btn">
                                <i class="fas fa-edit"></i> Edit Property
                            </a>
                        </div>

                    <?php elseif ($isLoggedIn && $userRole === 'host'): ?>
                        <!-- HOST viewing someone else's property — Cannot book -->
                        <div class="host-own-notice">
                            <div class="host-own-icon"><i class="fas fa-info-circle"></i></div>
                            <p>As a host, you can only publish and manage your own listings.</p>
                            <p class="host-own-hint">Booking and purchase requests are available for guest accounts only.</p>
                        </div>

                    <?php elseif ($property['listing_type'] === 'rent'): ?>
                        <!-- AVAILABILITY CALENDAR -->
                        <div class="avail-calendar">
                            <div class="avail-header">
                                <button type="button" class="avail-nav" id="calPrev"><i class="fas fa-chevron-left"></i></button>
                                <span class="avail-month" id="calMonth"></span>
                                <button type="button" class="avail-nav" id="calNext"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="avail-days-header">
                                <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                            </div>
                            <div class="avail-grid" id="calGrid"></div>
                            <div class="avail-legend">
                                <span><i class="avail-dot avail-free"></i> Available</span>
                                <span><i class="avail-dot avail-booked"></i> Booked</span>
                            </div>
                        </div>

                        <!-- RENT — Booking Form (Guests only) -->
                        <form class="booking-form" action="/includes/book.php" method="POST">
                            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                            <div class="booking-dates">
                                <div class="booking-field">
                                    <label>Check-in</label>
                                    <input type="date" name="check_in" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="booking-field">
                                    <label>Check-out</label>
                                    <input type="date" name="check_out" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>
                            <div class="booking-field">
                                <label>Guests</label>
                                <select name="guests">
                                    <option value="1">1 Guest</option>
                                    <option value="2" selected>2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5+ Guests</option>
                                </select>
                            </div>
                            <div class="booking-field">
                                <label>Payment Method</label>
                                <div class="payment-options">
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="credit_card" required>
                                        <div class="payment-option-box">
                                            <i class="fas fa-credit-card"></i>
                                            <span>Credit Card</span>
                                        </div>
                                    </label>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="pay_on_arrival" checked>
                                        <div class="payment-option-box">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Pay on Arrival</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <!-- Credit Card Fields (shown when credit card is selected) -->
                            <div id="creditCardFields" style="display:none;">
                                <div class="booking-field">
                                    <label>Card Number</label>
                                    <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                                </div>
                                <div class="booking-dates">
                                    <div class="booking-field">
                                        <label>Expiry</label>
                                        <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5">
                                    </div>
                                    <div class="booking-field">
                                        <label>CVV</label>
                                        <input type="text" name="card_cvv" placeholder="123" maxlength="3">
                                    </div>
                                </div>
                                <div class="booking-field">
                                    <label>Cardholder Name</label>
                                    <input type="text" name="card_name" placeholder="Name on card">
                                </div>
                            </div>
                            <button type="submit" class="booking-btn">
                                <i class="fas fa-calendar-check"></i> Book Now
                            </button>
                        </form>

                    <?php elseif ($property['listing_type'] === 'buy'): ?>
                        <!-- BUY — Contact Seller (Guests only) -->
                        <form class="booking-form" action="/includes/purchase-request.php" method="POST">
                            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                            <div class="booking-field">
                                <label>Your Name</label>
                                <input type="text" name="full_name" placeholder="Full name" required 
                                       value="<?php echo $isLoggedIn ? htmlspecialchars($userName) : ''; ?>">
                            </div>
                            <div class="booking-field">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="+961 XX XXX XXX" required>
                            </div>
                            <div class="booking-field">
                                <label>Message</label>
                                <textarea name="message" rows="3" placeholder="I'm interested in this property..."></textarea>
                            </div>
                            <button type="submit" class="booking-btn btn-coral">
                                <i class="fas fa-envelope"></i> Contact Seller
                            </button>
                        </form>

                    <?php else: ?>
                        <!-- LAND — Request Info (Guests only) -->
                        <form class="booking-form" action="/includes/purchase-request.php" method="POST">
                            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                            <div class="booking-field">
                                <label>Your Name</label>
                                <input type="text" name="full_name" placeholder="Full name" required
                                       value="<?php echo $isLoggedIn ? htmlspecialchars($userName) : ''; ?>">
                            </div>
                            <div class="booking-field">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="+961 XX XXX XXX" required>
                            </div>
                            <div class="booking-field">
                                <label>Message</label>
                                <textarea name="message" rows="3" placeholder="I'd like to know more about this land..."></textarea>
                            </div>
                            <button type="submit" class="booking-btn btn-lavender">
                                <i class="fas fa-info-circle"></i> Request Info
                            </button>
                        </form>
                    <?php endif; ?>

                    <p class="booking-note"><i class="fas fa-shield-alt"></i> Secure & verified listing</p>
                </div>

                <!-- Host Card -->
                <div class="sidebar-card host-card">
                    <h3 class="sidebar-card-title">Hosted by</h3>
                    <div class="host-info">
                        <div class="host-avatar">
                            <?php echo strtoupper(substr($property['host_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="host-name"><?php echo htmlspecialchars($property['host_name']); ?></div>
                            <div class="host-since">Member since <?php echo date('M Y', strtotime($property['host_since'])); ?></div>
                        </div>
                    </div>
                    <div class="host-stats">
                        <?php
                        $hostListings = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE host_id = ? AND status = 'active'");
                        $hostListings->execute([$property['host_id']]);
                        $hostCount = $hostListings->fetch()['count'];
                        ?>
                        <div class="host-stat">
                            <strong><?php echo $hostCount; ?></strong>
                            <span>Listing<?php echo $hostCount !== 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="host-stat">
                            <strong><i class="fas fa-check-circle"></i></strong>
                            <span>Verified</span>
                        </div>
                    </div>
                  
                </div>

                <!-- Message Host Form -->
                <?php if ($isLoggedIn && $property['host_id'] != $_SESSION['user_id']): ?>
                    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sent'): ?>
                        <div class="sidebar-card" style="background:#d4edda;border-color:#c3e6cb;">
                            <p style="margin:0;color:#155724;font-size:14px;"><i class="fas fa-check-circle"></i> Message sent to host!</p>
                        </div>
                    <?php endif; ?>
                    <div class="sidebar-card">
                        <h3 class="sidebar-card-title"><i class="fas fa-comment-dots"></i> Send a Message</h3>
                        <form action="/includes/send-message.php" method="POST">
                            <input type="hidden" name="receiver_id" value="<?php echo $property['host_id']; ?>">
                            <input type="hidden" name="property_id" value="<?php echo $id; ?>">
                            <input type="hidden" name="redirect" value="/pages/property.php?id=<?php echo $id; ?>">
                            <div class="booking-field">
                                <textarea name="message" rows="3" placeholder="Hi, I'm interested in this property..." required style="width:100%;padding:10px 12px;border:1.5px solid #e0d9cd;border-radius:8px;font-size:.88rem;font-family:inherit;resize:vertical;outline:none;"></textarea>
                            </div>
                            <button type="submit" style="width:100%;padding:10px;background:linear-gradient(135deg,var(--primary,#0ABAB5),var(--primary-dark,#089E9A));color:#fff;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;margin-top:8px;">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                <?php elseif (!$isLoggedIn): ?>
                    <div class="sidebar-card" style="text-align:center;border:1px dashed #ddd;">
                        <p style="margin:0;color:#888;font-size:.88rem;"><i class="fas fa-lock"></i> <a href="/pages/login.php" style="color:var(--primary);font-weight:600;">Log in</a> to message the host</p>
                    </div>
                <?php endif; ?>
            </aside>
        </div>

        <!-- ===== SIMILAR PROPERTIES ===== -->
        <?php if (!empty($similar)): ?>
            <div class="similar-section">
                <h2 class="detail-section-title">Similar Properties</h2>
                <div class="similar-grid">
                    <?php foreach ($similar as $sim): ?>
                        <a href="property.php?id=<?php echo $sim['id']; ?>" class="property-card">
                            <div class="property-img">
                                <?php if ($sim['main_image']): ?>
                                    <img src="<?php echo htmlspecialchars($sim['main_image']); ?>" alt="<?php echo htmlspecialchars($sim['title']); ?>">
                                <?php else: ?>
                                    <div class="property-img-placeholder"><i class="fas fa-image"></i></div>
                                <?php endif; ?>
                                <span class="property-badge badge-<?php echo $sim['listing_type']; ?>">
                                    <?php echo match($sim['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?>
                                </span>
                            </div>
                            <div class="property-info">
                                <div class="property-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($sim['location']); ?></div>
                                <h3 class="property-title"><?php echo htmlspecialchars($sim['title']); ?></h3>
                                <div class="property-features">
                                    <?php if ($sim['bedrooms']): ?>
                                        <span class="property-feature"><i class="fas fa-bed"></i> <?php echo $sim['bedrooms']; ?> Bed<?php echo $sim['bedrooms'] > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                    <?php if ($sim['bathrooms']): ?>
                                        <span class="property-feature"><i class="fas fa-bath"></i> <?php echo $sim['bathrooms']; ?> Bath<?php echo $sim['bathrooms'] > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                    <?php if ($sim['area_sqm']): ?>
                                        <span class="property-feature"><i class="fas fa-ruler-combined"></i> <?php echo number_format($sim['area_sqm']); ?> m²</span>
                                    <?php endif; ?>
                                </div>
                                <div class="property-footer">
                                    <div class="property-price">
                                        $<?php echo number_format($sim['price']); ?>
                                        <?php if ($sim['listing_type'] === 'rent'): ?>
                                            <span>/ <?php echo $sim['price_period'] ?? 'night'; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="property-rating">
                                        <i class="fas fa-star"></i> <?php echo $sim['avg_rating'] ?? 'New'; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</section>

<script>
// ===== IMAGE GALLERY =====
const images = <?php echo json_encode(array_column($images, 'image_path')); ?>;
let currentImg = 0;
const mainImage = document.getElementById('mainImage');
const counter = document.getElementById('galleryCounter');
const thumbs = document.querySelectorAll('.gallery-thumb');

function changeImage(index) {
    currentImg = index;
    if (mainImage) mainImage.src = images[index];
    if (counter) counter.textContent = (index + 1) + ' / ' + images.length;
    thumbs.forEach((t, i) => t.classList.toggle('active', i === index));
}

document.getElementById('prevBtn')?.addEventListener('click', () => {
    changeImage(currentImg > 0 ? currentImg - 1 : images.length - 1);
});
document.getElementById('nextBtn')?.addEventListener('click', () => {
    changeImage(currentImg < images.length - 1 ? currentImg + 1 : 0);
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') changeImage(currentImg > 0 ? currentImg - 1 : images.length - 1);
    if (e.key === 'ArrowRight') changeImage(currentImg < images.length - 1 ? currentImg + 1 : 0);
});

// ===== FAVORITE TOGGLE =====
const favBtn = document.getElementById('favBtn');
if (favBtn) {
    favBtn.addEventListener('click', async () => {
        <?php if (!$isLoggedIn): ?>
            window.location.href = '/pages/login.php';
            return;
        <?php endif; ?>

        const res = await fetch('/includes/toggle-favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'property_id=<?php echo $id; ?>'
        });
        const data = await res.json();
        if (data.success) {
            const icon = favBtn.querySelector('i');
            if (data.action === 'added') {
                favBtn.classList.add('active');
                icon.classList.replace('far', 'fas');
            } else {
                favBtn.classList.remove('active');
                icon.classList.replace('fas', 'far');
            }
        }
    });
}

// ===== PAYMENT METHOD TOGGLE =====
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const ccFields = document.getElementById('creditCardFields');
        if (ccFields) {
            ccFields.style.display = this.value === 'credit_card' ? 'block' : 'none';
            // Toggle required on credit card fields
            ccFields.querySelectorAll('input').forEach(inp => {
                inp.required = this.value === 'credit_card';
            });
        }
    });
});

// ===== DATE AVAILABILITY CHECK =====
const bookedRanges = <?php echo json_encode($bookedRanges); ?>;
const checkInInput = document.querySelector('input[name="check_in"]');
const checkOutInput = document.querySelector('input[name="check_out"]');

function isDateBooked(startStr, endStr) {
    if (!startStr || !endStr) return false;
    const start = new Date(startStr);
    const end = new Date(endStr);
    for (let i = 0; i < bookedRanges.length; i++) {
        const bStart = new Date(bookedRanges[i].start);
        const bEnd = new Date(bookedRanges[i].end);
        if (start < bEnd && end > bStart) return true;
    }
    return false;
}

if (checkInInput && checkOutInput) {
    const bookForm = checkInInput.closest('form');
    if (bookForm) {
        bookForm.addEventListener('submit', function(e) {
            if (isDateBooked(checkInInput.value, checkOutInput.value)) {
                e.preventDefault();
                alert('Sorry, these dates are already booked. Please choose different dates.');
            }
        });
    }
    checkOutInput.addEventListener('change', function() {
        if (isDateBooked(checkInInput.value, checkOutInput.value)) {
            alert('These dates overlap with an existing booking. Please choose different dates.');
            this.value = '';
        }
    });
}

// ===== STAR RATING SELECT =====
const starSelect = document.getElementById('starSelect');
const ratingInput = document.getElementById('ratingInput');
if (starSelect) {
    const stars = starSelect.querySelectorAll('i');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-rating');
            ratingInput.value = rating;
            stars.forEach((s, i) => {
                s.classList.toggle('selected', i < rating);
            });
        });
        star.addEventListener('mouseenter', function() {
            const rating = this.getAttribute('data-rating');
            stars.forEach((s, i) => {
                s.classList.toggle('hover', i < rating);
            });
        });
        star.addEventListener('mouseleave', function() {
            stars.forEach(s => s.classList.remove('hover'));
        });
    });
}

// ===== AVAILABILITY CALENDAR =====
const calGrid = document.getElementById('calGrid');
const calMonth = document.getElementById('calMonth');
const calPrev = document.getElementById('calPrev');
const calNext = document.getElementById('calNext');

if (calGrid) {
    let calDate = new Date();
    calDate.setDate(1);

    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    function isBooked(dateStr) {
        for (let i = 0; i < bookedRanges.length; i++) {
            const s = new Date(bookedRanges[i].start);
            const e = new Date(bookedRanges[i].end);
            const d = new Date(dateStr);
            if (d >= s && d < e) return true;
        }
        return false;
    }

    function renderCalendar() {
        calGrid.innerHTML = '';
        const year = calDate.getFullYear();
        const month = calDate.getMonth();
        calMonth.textContent = months[month] + ' ' + year;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today = new Date();
        today.setHours(0,0,0,0);

        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            const empty = document.createElement('div');
            empty.className = 'avail-day empty';
            calGrid.appendChild(empty);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const cell = document.createElement('div');
            cell.className = 'avail-day';
            cell.textContent = d;

            const thisDate = new Date(year, month, d);
            const dateStr = year + '-' + String(month+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');

            if (thisDate < today) {
                cell.classList.add('past');
            } else if (isBooked(dateStr)) {
                cell.classList.add('booked');
            } else {
                cell.classList.add('available');
            }

            if (thisDate.getTime() === today.getTime()) {
                cell.classList.add('today');
            }

            calGrid.appendChild(cell);
        }
    }

    calPrev.addEventListener('click', function() {
        calDate.setMonth(calDate.getMonth() - 1);
        renderCalendar();
    });

    calNext.addEventListener('click', function() {
        calDate.setMonth(calDate.getMonth() + 1);
        renderCalendar();
    });

    renderCalendar();
}

// ===== SHARE =====
function shareProperty() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($property['title']); ?>',
            text: 'Check out this property on Manzeli!',
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Link copied to clipboard!');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
