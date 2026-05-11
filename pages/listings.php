<?php
$pageTitle = 'Listings';
$extraCSS = '<link rel="stylesheet" href="/assets/css/listings.css">';
require_once '../includes/db.php';
require_once '../includes/header.php';

$type = $_GET['type'] ?? 'all';
$location = $_GET['location'] ?? '';
$city = $_GET['city'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$bedrooms = $_GET['bedrooms'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

// Build query
$where = ["p.status = 'active'"];
$params = [];

if ($type && $type !== 'all') {
    $where[] = "p.listing_type = ?";
    $params[] = $type;
}
if ($location) {
    $where[] = "p.location LIKE ?";
    $params[] = "%$location%";
}
if ($city) {
    $where[] = "p.location LIKE ?";
    $params[] = "%$city%";
}
if ($property_type) {
    $where[] = "p.property_type = ?";
    $params[] = $property_type;
}
if ($min_price !== '') {
    $where[] = "p.price >= ?";
    $params[] = (float)$min_price;
}
if ($max_price !== '') {
    $where[] = "p.price <= ?";
    $params[] = (float)$max_price;
}
if ($bedrooms) {
    if ($bedrooms === '4') {
        $where[] = "p.bedrooms >= 4";
    } else {
        $where[] = "p.bedrooms = ?";
        $params[] = (int)$bedrooms;
    }
}

$orderBy = match($sort) {
    'price_low'  => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'popular'    => 'p.views_count DESC',
    'rating'     => 'avg_rating DESC',
    default      => 'p.created_at DESC',
};

$whereSQL = implode(' AND ', $where);

$sql = "SELECT p.*, 
        (SELECT image_path FROM property_images WHERE property_id = p.id AND is_main = 1 LIMIT 1) AS main_image,
        (SELECT ROUND(AVG(rating),1) FROM reviews WHERE property_id = p.id) AS avg_rating
        FROM properties p
        WHERE $whereSQL
        ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();
$totalCount = count($properties);
?>

<!-- ===== LISTINGS PAGE ===== -->
<section class="listings-section">
    <div class="listings-container">

        <!-- Page Header -->
        <div class="listings-header">
            <div class="listings-header-left">
                <h1 class="listings-title">
                    <?php
                    switch($type) {
                        case 'rent': echo 'Properties for Rent'; break;
                        case 'buy': echo 'Properties for Sale'; break;
                        case 'land': echo 'Land for Sale'; break;
                        default: echo 'All Properties'; break;
                    }
                    ?>
                </h1>
                <p class="listings-count">Showing <strong><?php echo $totalCount; ?></strong> propert<?php echo $totalCount === 1 ? 'y' : 'ies'; ?> in Lebanon</p>
            </div>
            <div class="listings-header-right">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="grid" title="Grid View"><i class="fas fa-th-large"></i></button>
                    <button class="view-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                </div>
                <div class="sort-wrapper">
                    <select class="sort-select" id="sortSelect" onchange="updateSort(this.value)">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="listings-layout">

            <!-- ===== SIDEBAR FILTERS ===== -->
            <aside class="filters-sidebar" id="filtersSidebar">
                <div class="filters-top">
                    <div class="filters-header">
                        <h3><i class="fas fa-sliders-h"></i> Filters</h3>
                        <a href="listings.php" class="clear-filters">Clear All</a>
                    </div>
                    <button class="filters-close-btn" id="filtersCloseBtn"><i class="fas fa-times"></i></button>
                </div>

                <form class="filters-form" method="GET" action="listings.php">
                    <div class="filter-group">
                        <h4 class="filter-title">Listing Type</h4>
                        <div class="filter-tabs">
                            <label class="filter-tab"><input type="radio" name="type" value="all" <?php echo $type === 'all' ? 'checked' : ''; ?>><span>All</span></label>
                            <label class="filter-tab"><input type="radio" name="type" value="rent" <?php echo $type === 'rent' ? 'checked' : ''; ?>><span><i class="fas fa-key"></i> Rent</span></label>
                            <label class="filter-tab"><input type="radio" name="type" value="buy" <?php echo $type === 'buy' ? 'checked' : ''; ?>><span><i class="fas fa-building"></i> Buy</span></label>
                            <label class="filter-tab"><input type="radio" name="type" value="land" <?php echo $type === 'land' ? 'checked' : ''; ?>><span><i class="fas fa-map"></i> Land</span></label>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4 class="filter-title">Location</h4>
                        <div class="filter-input-wrapper">
                            <i class="fas fa-map-marker-alt"></i>
                            <input type="text" name="location" placeholder="City or area..." value="<?php echo htmlspecialchars($location); ?>">
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4 class="filter-title">Property Type</h4>
                        <select name="property_type" class="filter-select">
                            <option value="">Any Type</option>
                            <option value="apartment" <?php echo $property_type === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="house" <?php echo $property_type === 'house' ? 'selected' : ''; ?>>House</option>
                            <option value="villa" <?php echo $property_type === 'villa' ? 'selected' : ''; ?>>Villa</option>
                            <option value="studio" <?php echo $property_type === 'studio' ? 'selected' : ''; ?>>Studio</option>
                            <option value="chalet" <?php echo $property_type === 'chalet' ? 'selected' : ''; ?>>Chalet</option>
                            <option value="land" <?php echo $property_type === 'land' ? 'selected' : ''; ?>>Land</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <h4 class="filter-title">Price Range</h4>
                        <div class="price-range">
                            <div class="filter-input-wrapper small">
                                <span>$</span>
                                <input type="number" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($min_price); ?>">
                            </div>
                            <span class="price-separator">—</span>
                            <div class="filter-input-wrapper small">
                                <span>$</span>
                                <input type="number" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($max_price); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="filter-group">
                        <h4 class="filter-title">Bedrooms</h4>
                        <div class="filter-pills">
                            <label class="filter-pill"><input type="radio" name="bedrooms" value="" <?php echo $bedrooms === '' ? 'checked' : ''; ?>><span>Any</span></label>
                            <label class="filter-pill"><input type="radio" name="bedrooms" value="1" <?php echo $bedrooms === '1' ? 'checked' : ''; ?>><span>1</span></label>
                            <label class="filter-pill"><input type="radio" name="bedrooms" value="2" <?php echo $bedrooms === '2' ? 'checked' : ''; ?>><span>2</span></label>
                            <label class="filter-pill"><input type="radio" name="bedrooms" value="3" <?php echo $bedrooms === '3' ? 'checked' : ''; ?>><span>3</span></label>
                            <label class="filter-pill"><input type="radio" name="bedrooms" value="4" <?php echo $bedrooms === '4' ? 'checked' : ''; ?>><span>4+</span></label>
                        </div>
                    </div>

                    <button type="submit" class="filter-apply-btn">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                </form>
            </aside>

            <!-- ===== PROPERTY GRID ===== -->
            <div class="listings-grid-wrapper">

                <button class="mobile-filter-btn" id="mobileFilterBtn">
                    <i class="fas fa-sliders-h"></i> Filters
                </button>

                <div class="listings-grid" id="listingsGrid">
                    <?php if (!empty($properties)): ?>
                        <?php foreach ($properties as $p): ?>
                            <a href="property.php?id=<?php echo $p['id']; ?>" class="property-card">
                                <div class="property-img">
                                    <?php if ($p['main_image']): ?>
                                        <img src="<?php echo htmlspecialchars($p['main_image']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>">
                                    <?php else: ?>
                                        <div class="property-img-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                    <span class="property-badge badge-<?php echo $p['listing_type']; ?>">
                                        <?php echo match($p['listing_type']) { 'rent' => 'For Rent', 'buy' => 'For Sale', 'land' => 'Land' }; ?>
                                    </span>
                                    <div class="property-views"><i class="fas fa-eye"></i> <?php echo number_format($p['views_count']); ?></div>
                                </div>
                                <div class="property-info">
                                    <div class="property-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($p['location']); ?></div>
                                    <h3 class="property-title"><?php echo htmlspecialchars($p['title']); ?></h3>
                                    <div class="property-features">
                                        <?php if ($p['bedrooms']): ?>
                                            <span class="property-feature"><i class="fas fa-bed"></i> <?php echo $p['bedrooms']; ?> Bed<?php echo $p['bedrooms'] > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['bathrooms']): ?>
                                            <span class="property-feature"><i class="fas fa-bath"></i> <?php echo $p['bathrooms']; ?> Bath<?php echo $p['bathrooms'] > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                        <?php if ($p['area_sqm']): ?>
                                            <span class="property-feature"><i class="fas fa-ruler-combined"></i> <?php echo number_format($p['area_sqm']); ?> m²</span>
                                        <?php endif; ?>
                                        <?php if ($p['listing_type'] === 'land' && $p['zone_type']): ?>
                                            <span class="property-feature"><i class="fas fa-map"></i> <?php echo ucfirst($p['zone_type']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="property-footer">
                                        <div class="property-price">
                                            $<?php echo number_format($p['price']); ?>
                                            <?php if ($p['listing_type'] === 'rent'): ?>
                                                <span>/ <?php echo $p['price_period'] ?? 'night'; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="property-rating"><i class="fas fa-star"></i> <?php echo $p['avg_rating'] ?? 'New'; ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h3>No properties found</h3>
                            <p>Try adjusting your filters or search for a different location.</p>
                            <a href="listings.php" class="btn-primary" style="margin-top:16px;display:inline-flex">Clear Filters</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const mobileFilterBtn = document.getElementById('mobileFilterBtn');
const filtersCloseBtn = document.getElementById('filtersCloseBtn');
const filtersSidebar = document.getElementById('filtersSidebar');

if (mobileFilterBtn) {
    mobileFilterBtn.addEventListener('click', () => {
        filtersSidebar.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
}
if (filtersCloseBtn) {
    filtersCloseBtn.addEventListener('click', () => {
        filtersSidebar.classList.remove('show');
        document.body.style.overflow = '';
    });
}

const viewBtns = document.querySelectorAll('.view-btn');
const listingsGrid = document.getElementById('listingsGrid');
viewBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        viewBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        listingsGrid.classList.toggle('list-view', btn.dataset.view === 'list');
    });
});

function updateSort(value) {
    const url = new URL(window.location);
    url.searchParams.set('sort', value);
    window.location = url;
}
</script>

<?php require_once '../includes/footer.php'; ?>
