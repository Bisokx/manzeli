<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$userRole = $_SESSION['role'] ?? 'guest';
$userName = $_SESSION['full_name'] ?? '';

// Detect current page for active nav link
$currentPage = basename($_SERVER['PHP_SELF']);

// Home link destination — Dashboard if logged in, otherwise main homepage
$homeLink = $isLoggedIn ? '/pages/dashboard.php' : '/index.php';

// Count unread messages for logged in user
$unreadMsgCount = 0;
if ($isLoggedIn) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/db.php';
        }
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $unreadStmt->execute([$_SESSION['user_id']]);
        $unreadMsgCount = $unreadStmt->fetchColumn();
    } catch (Exception $e) {
        $unreadMsgCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manzeli – منزلي | <?php echo $pageTitle ?? 'Home'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700&family=Noto+Kufi+Arabic:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <?php if (isset($extraCSS)) echo $extraCSS; ?>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <!-- Logo -->
        <a href="<?php echo $homeLink; ?>" class="nav-logo">
            <span class="logo-icon"><i class="fas fa-home"></i></span>
            <span class="logo-text">Manzeli</span>
            <span class="logo-arabic">منزلي</span>
        </a>

        <!-- Nav Links -->
        <ul class="nav-links" id="navLinks">
            <li><a href="<?php echo $homeLink; ?>" class="nav-link <?php echo in_array($currentPage, ['index.php', 'dashboard.php']) ? 'active' : ''; ?>">Home</a></li>
            <li class="nav-dropdown">
                <a href="/pages/listings.php" class="nav-link <?php echo $currentPage === 'listings.php' ? 'active' : ''; ?>">
                    Explore <i class="fas fa-chevron-down"></i>
                </a>
                <div class="dropdown-menu">
                    <a href="/pages/listings.php?type=rent"><i class="fas fa-key"></i> Rent</a>
                    <a href="/pages/listings.php?type=buy"><i class="fas fa-building"></i> Buy</a>
                    <a href="/pages/listings.php?type=land"><i class="fas fa-map"></i> Land</a>
                </div>
            </li>
            <li><a href="/pages/about.php" class="nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">About</a></li>
            <li><a href="/pages/contact.php" class="nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
        </ul>

        <!-- Nav Actions -->
        <div class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <a href="/pages/dashboard.php?tab=favorites" class="nav-icon-btn" title="Saved Properties">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="/pages/dashboard.php?tab=messages" class="nav-icon-btn" title="Messages" style="position:relative;">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadMsgCount > 0): ?>
                        <span style="position:absolute;top:-4px;right:-4px;background:#e74c3c;color:#fff;font-size:.6rem;font-weight:700;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid #fff;"><?php echo $unreadMsgCount; ?></span>
                    <?php endif; ?>
                </a>
                <div class="user-menu">
                    <button class="user-menu-btn" id="userMenuBtn">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/pages/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                        <?php if ($userRole === 'host'): ?>
                            <a href="/pages/host/my-listings.php"><i class="fas fa-list"></i> My Listings</a>
                            <a href="/pages/host/add-property.php"><i class="fas fa-plus-circle"></i> Add Property</a>
                        <?php endif; ?>
                        <?php if ($userRole === 'admin'): ?>
                            <a href="/pages/admin/index.php"><i class="fas fa-cog"></i> Admin Panel</a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="/pages/profile.php"><i class="fas fa-user"></i> Profile</a>
                        <a href="/includes/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/pages/login.php" class="btn-login">Log In</a>
                <a href="/pages/register.php" class="btn-signup">Sign Up</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-toggle" id="mobileToggle">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </div>
</nav>

<!-- Main Content Wrapper -->
<main class="main-content">
