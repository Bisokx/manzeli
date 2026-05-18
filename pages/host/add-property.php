
<?php
$pageTitle = 'Add Property';
$extraCSS = '<link rel="stylesheet" href="/assets/css/host.css">';
require_once '../../includes/db.php';
require_once '../../includes/header.php';

if (!$isLoggedIn || $userRole !== 'host') {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$editing = false;
$property = null;

// Check if editing
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND host_id = ?");
    $stmt->execute([$editId, $userId]);
    $property = $stmt->fetch();
    if ($property) $editing = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $listingType = $_POST['listing_type'] ?? 'rent';
    $propertyType = $_POST['property_type'] ?? null;
    $price = (float)($_POST['price'] ?? 0);
    $pricePeriod = $_POST['price_period'] ?? 'total';
    $location = trim($_POST['location'] ?? '');
    $areaSqm = (int)($_POST['area_sqm'] ?? 0) ?: null;
    $bedrooms = (int)($_POST['bedrooms'] ?? 0) ?: null;
    $bathrooms = (int)($_POST['bathrooms'] ?? 0) ?: null;
    $floorNumber = (int)($_POST['floor_number'] ?? 0) ?: null;
    $buildingAge = (int)($_POST['building_age'] ?? 0) ?: null;
    $zoneType = $_POST['zone_type'] ?? null;
    $amenityIds = $_POST['amenities'] ?? [];
    $existingImages = $_POST['existing_images'] ?? [];

    if (empty($title) || empty($location) || $price <= 0) {
        $error = 'Please fill in the title, location, and price.';
    } else {
        if ($editing) {
            $stmt = $pdo->prepare("UPDATE properties SET title=?, description=?, listing_type=?, property_type=?, price=?, price_period=?, location=?, area_sqm=?, bedrooms=?, bathrooms=?, floor_number=?, building_age=?, zone_type=? WHERE id=? AND host_id=?");
            $stmt->execute([$title, $description, $listingType, $propertyType, $price, $pricePeriod, $location, $areaSqm, $bedrooms, $bathrooms, $floorNumber, $buildingAge, $zoneType, $editId, $userId]);
            $propId = $editId;

            // Clear old amenities and images
            $pdo->prepare("DELETE FROM property_amenities WHERE property_id = ?")->execute([$propId]);
            $pdo->prepare("DELETE FROM property_images WHERE property_id = ?")->execute([$propId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO properties (host_id, title, description, listing_type, property_type, price, price_period, location, area_sqm, bedrooms, bathrooms, floor_number, building_age, zone_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $title, $description, $listingType, $propertyType, $price, $pricePeriod, $location, $areaSqm, $bedrooms, $bathrooms, $floorNumber, $buildingAge, $zoneType]);
            $propId = $pdo->lastInsertId();
        }

        // Insert amenities
        foreach ($amenityIds as $amenityId) {
            $pdo->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)")->execute([$propId, (int)$amenityId]);
        }

        // Handle existing images (kept during edit)
        $imgIndex = 0;
        foreach ($existingImages as $existingPath) {
            $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_main) VALUES (?, ?, ?)")->execute([$propId, $existingPath, $imgIndex === 0 ? 1 : 0]);
            $imgIndex++;
        }

        // Handle new uploaded images
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!empty($_FILES['images']['name'][0])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
                if (!in_array($_FILES['images']['type'][$key], $allowedTypes)) continue;
                if ($_FILES['images']['size'][$key] > $maxSize) continue;

                $ext = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                $filename = 'prop_' . $propId . '_' . time() . '_' . $key . '.' . $ext;
                $destination = $uploadDir . $filename;

                if (move_uploaded_file($tmpName, $destination)) {
                    $imagePath = '/assets/images/' . $filename;
                    $isMain = ($imgIndex === 0) ? 1 : 0;
                    $pdo->prepare("INSERT INTO property_images (property_id, image_path, is_main) VALUES (?, ?, ?)")->execute([$propId, $imagePath, $isMain]);
                    $imgIndex++;
                }
            }
        }

        header('Location: ../property.php?id=' . $propId . '&success=1');
        exit;
    }
}

// Fetch amenities for checkboxes
$amenitiesList = $pdo->query("SELECT * FROM amenities ORDER BY name")->fetchAll();

// If editing, get current amenity ids and images
$currentAmenities = [];
if ($editing) {
    $ca = $pdo->prepare("SELECT amenity_id FROM property_amenities WHERE property_id = ?");
    $ca->execute([$editId]);
    $currentAmenities = array_column($ca->fetchAll(), 'amenity_id');
}
?>

<section class="host-section">
    <div class="host-container">
        <div class="host-page-header">
            <h1><i class="fas fa-<?php echo $editing ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $editing ? 'Edit Property' : 'Add New Property'; ?></h1>
            <p>Fill in the details below to <?php echo $editing ? 'update your' : 'list a new'; ?> property on Manzeli</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="host-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form class="host-form" method="POST" enctype="multipart/form-data">
            <!-- Basic Info -->
            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-info-circle"></i> Basic Information</h2>
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Property Title *</label>
                        <input type="text" name="title" placeholder="e.g. Modern 2-Bedroom Apartment with Sea View" required value="<?php echo htmlspecialchars($property['title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Listing Type *</label>
                        <select name="listing_type" id="listingType" onchange="toggleFields()">
                            <option value="rent" <?php echo ($property['listing_type'] ?? '') === 'rent' ? 'selected' : ''; ?>>For Rent</option>
                            <option value="buy" <?php echo ($property['listing_type'] ?? '') === 'buy' ? 'selected' : ''; ?>>For Sale</option>
                            <option value="land" <?php echo ($property['listing_type'] ?? '') === 'land' ? 'selected' : ''; ?>>Land</option>
                        </select>
                    </div>
                    <div class="form-group" id="propTypeGroup">
                        <label>Property Type</label>
                        <select name="property_type">
                            <option value="">Select Type</option>
                            <option value="apartment" <?php echo ($property['property_type'] ?? '') === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="house" <?php echo ($property['property_type'] ?? '') === 'house' ? 'selected' : ''; ?>>House</option>
                            <option value="villa" <?php echo ($property['property_type'] ?? '') === 'villa' ? 'selected' : ''; ?>>Villa</option>
                            <option value="studio" <?php echo ($property['property_type'] ?? '') === 'studio' ? 'selected' : ''; ?>>Studio</option>
                            <option value="chalet" <?php echo ($property['property_type'] ?? '') === 'chalet' ? 'selected' : ''; ?>>Chalet</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="description" rows="5" placeholder="Describe your property..."><?php echo htmlspecialchars($property['description'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Location & Price -->
            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-map-marker-alt"></i> Location & Pricing</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" name="location" placeholder="e.g. Beirut, Hamra" required value="<?php echo htmlspecialchars($property['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Price (USD) *</label>
                        <input type="number" name="price" step="0.01" min="1" placeholder="e.g. 75" required value="<?php echo $property['price'] ?? ''; ?>">
                    </div>
                    <div class="form-group" id="pricePeriodGroup">
                        <label>Price Period</label>
                        <select name="price_period">
                            <option value="night" <?php echo ($property['price_period'] ?? '') === 'night' ? 'selected' : ''; ?>>Per Night</option>
                            <option value="month" <?php echo ($property['price_period'] ?? '') === 'month' ? 'selected' : ''; ?>>Per Month</option>
                            <option value="total" <?php echo ($property['price_period'] ?? '') === 'total' ? 'selected' : ''; ?>>Total Price</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Area (m²)</label>
                        <input type="number" name="area_sqm" placeholder="e.g. 120" value="<?php echo $property['area_sqm'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="form-card" id="detailsCard">
                <h2 class="form-card-title"><i class="fas fa-th-list"></i> Property Details</h2>
                <div class="form-grid">
                    <div class="form-group" id="bedroomsGroup">
                        <label>Bedrooms</label>
                        <input type="number" name="bedrooms" min="0" placeholder="e.g. 2" value="<?php echo $property['bedrooms'] ?? ''; ?>">
                    </div>
                    <div class="form-group" id="bathroomsGroup">
                        <label>Bathrooms</label>
                        <input type="number" name="bathrooms" min="0" placeholder="e.g. 1" value="<?php echo $property['bathrooms'] ?? ''; ?>">
                    </div>
                    <div class="form-group" id="floorGroup">
                        <label>Floor Number</label>
                        <input type="number" name="floor_number" min="0" placeholder="e.g. 5" value="<?php echo $property['floor_number'] ?? ''; ?>">
                    </div>
                    <div class="form-group" id="ageGroup">
                        <label>Building Age (years)</label>
                        <input type="number" name="building_age" min="0" placeholder="e.g. 8" value="<?php echo $property['building_age'] ?? ''; ?>">
                    </div>
                    <div class="form-group" id="zoneGroup" style="display:none;">
                        <label>Zone Type</label>
                        <select name="zone_type">
                            <option value="">Select Zone</option>
                            <option value="residential" <?php echo ($property['zone_type'] ?? '') === 'residential' ? 'selected' : ''; ?>>Residential</option>
                            <option value="commercial" <?php echo ($property['zone_type'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                            <option value="agricultural" <?php echo ($property['zone_type'] ?? '') === 'agricultural' ? 'selected' : ''; ?>>Agricultural</option>
                            <option value="mixed" <?php echo ($property['zone_type'] ?? '') === 'mixed' ? 'selected' : ''; ?>>Mixed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Amenities -->
            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-check-circle"></i> Amenities</h2>
                <div class="amenities-check-grid">
                    <?php foreach ($amenitiesList as $a): ?>
                        <label class="amenity-check">
                            <input type="checkbox" name="amenities[]" value="<?php echo $a['id']; ?>"
                                <?php echo in_array($a['id'], $currentAmenities) ? 'checked' : ''; ?>>
                            <span class="amenity-check-box"><i class="<?php echo htmlspecialchars($a['icon']); ?>"></i> <?php echo htmlspecialchars($a['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Images -->
            <div class="form-card">
                <h2 class="form-card-title"><i class="fas fa-images"></i> Property Images</h2>
                <p class="form-hint">Upload images (JPG, PNG, WEBP). The first image will be the main photo. Max 5MB each.</p>
                <div class="upload-area" id="uploadArea">
                    <input type="file" name="images[]" id="imageInput" multiple accept="image/jpeg,image/png,image/webp" style="display:none;">
                    <div class="upload-placeholder" onclick="document.getElementById('imageInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click to upload or drag & drop</p>
                        <span>JPG, PNG, WEBP — Max 5MB each</span>
                    </div>
                    <div class="upload-preview" id="uploadPreview">
                        <?php if ($editing): ?>
                            <?php 
                            $ci = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? ORDER BY is_main DESC");
                            $ci->execute([$editId]);
                            $existingImages = $ci->fetchAll();
                            foreach ($existingImages as $ei): ?>
                                <div class="preview-item existing">
                                    <img src="<?php echo htmlspecialchars($ei['image_path']); ?>" alt="Existing">
                                    <input type="hidden" name="existing_images[]" value="<?php echo htmlspecialchars($ei['image_path']); ?>">
                                    <button type="button" class="preview-remove" onclick="this.parentElement.remove()">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="/pages/dashboard.php" class="form-cancel">Cancel</a>
                <button type="submit" class="form-submit">
                    <i class="fas fa-<?php echo $editing ? 'save' : 'plus'; ?>"></i>
                    <?php echo $editing ? 'Update Property' : 'Publish Property'; ?>
                </button>
            </div>
        </form>
    </div>
</section>

<style>
.upload-area{border:2px dashed #d0c9bd;border-radius:12px;padding:20px;transition:border-color .2s}
.upload-area.dragover{border-color:var(--primary,#0ABAB5);background:rgba(10,186,181,.04)}
.upload-placeholder{text-align:center;cursor:pointer;padding:30px 20px;color:#999}
.upload-placeholder i{font-size:2.5rem;color:#ccc;margin-bottom:10px;display:block}
.upload-placeholder p{margin:0;font-weight:600;font-size:.95rem;color:#666}
.upload-placeholder span{font-size:.8rem;color:#aaa}
.upload-preview{display:flex;flex-wrap:wrap;gap:10px;margin-top:12px}
.preview-item{position:relative;width:100px;height:100px;border-radius:8px;overflow:hidden;border:2px solid #e8e4dd}
.preview-item img{width:100%;height:100%;object-fit:cover}
.preview-remove{position:absolute;top:4px;right:4px;width:22px;height:22px;background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:50%;cursor:pointer;font-size:14px;line-height:1;display:flex;align-items:center;justify-content:center}
.preview-remove:hover{background:#e74c3c}
.preview-item:first-child::after{content:'Main';position:absolute;bottom:0;left:0;right:0;background:var(--primary,#0ABAB5);color:#fff;font-size:.65rem;text-align:center;padding:2px;font-weight:600}
</style>

<script>
function toggleFields() {
    const type = document.getElementById('listingType').value;
    const isLand = type === 'land';
    const isRent = type === 'rent';

    document.getElementById('propTypeGroup').style.display = isLand ? 'none' : '';
    document.getElementById('bedroomsGroup').style.display = isLand ? 'none' : '';
    document.getElementById('bathroomsGroup').style.display = isLand ? 'none' : '';
    document.getElementById('floorGroup').style.display = isLand ? 'none' : '';
    document.getElementById('ageGroup').style.display = isLand ? 'none' : '';
    document.getElementById('zoneGroup').style.display = isLand ? '' : 'none';
    document.getElementById('pricePeriodGroup').style.display = isRent ? '' : 'none';
}
toggleFields();

// Image upload preview
const imageInput = document.getElementById('imageInput');
const uploadPreview = document.getElementById('uploadPreview');
const uploadArea = document.getElementById('uploadArea');

if (imageInput) {
    imageInput.addEventListener('change', function() {
        previewFiles(this.files);
    });
}

// Drag & drop
if (uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', function() {
        this.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
        imageInput.files = e.dataTransfer.files;
        previewFiles(e.dataTransfer.files);
    });
}

function previewFiles(files) {
    for (let i = 0; i < files.length; i++) {
        if (!files[i].type.startsWith('image/')) continue;
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'preview-item';
            div.innerHTML = '<img src="' + e.target.result + '" alt="Preview"><button type="button" class="preview-remove" onclick="this.parentElement.remove()">&times;</button>';
            uploadPreview.appendChild(div);
        };
        reader.readAsDataURL(files[i]);
    }
}
</script>

<?php require_once '../../includes/footer.php'; ?>
