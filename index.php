<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

    <section class="hero">
        <div class="hero-bg-pattern"></div>
        <div class="hero-glow hero-glow-1"></div>
        <div class="hero-glow hero-glow-2"></div>
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge"><i class="fas fa-star"></i> Lebanon's #1 Real Estate Platform</div>
                <h1 class="hero-title">Find Your Perfect <span>Home</span> in Lebanon</h1>
                <p class="hero-subtitle">Rent, buy, or invest in properties and land across Lebanon. From Beirut apartments to mountain retreats — your next home is waiting.</p>
                <div class="hero-search">
                    <div class="search-tabs">
                        <div class="search-tab active" data-type="rent">Rent</div>
                        <div class="search-tab" data-type="buy">Buy</div>
                        <div class="search-tab" data-type="land">Land</div>
                    </div>
                    <form class="search-form" action="pages/listings.php" method="GET">
                        <input type="hidden" name="type" id="searchType" value="rent">
                        <div class="search-field"><label>Location</label><select name="city"><option value="">All Cities</option><option value="beirut">Beirut</option><option value="jounieh">Jounieh</option><option value="byblos">Byblos</option><option value="tripoli">Tripoli</option><option value="sidon">Sidon</option><option value="batroun">Batroun</option><option value="zahle">Zahle</option><option value="broummana">Broummana</option></select></div>
                        <div class="search-field"><label>Min Price ($)</label><input type="number" name="min_price" placeholder="Any"></div>
                        <div class="search-field"><label>Max Price ($)</label><input type="number" name="max_price" placeholder="Any"></div>
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat"><div class="hero-stat-number">500+</div><div class="hero-stat-label">Properties Listed</div></div>
                    <div class="hero-stat"><div class="hero-stat-number">50+</div><div class="hero-stat-label">Cities Covered</div></div>
                    <div class="hero-stat"><div class="hero-stat-number">1,200+</div><div class="hero-stat-label">Happy Users</div></div>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-icons-merged">
                    <i class="fas fa-building"></i>
                    <i class="fas fa-home"></i>
                    <i class="fas fa-map"></i>
                </div>
            </div>
        </div>
    </section>

    <section class="categories">
        <div class="container">
            <div class="section-header"><div class="section-label">Explore</div><h2 class="section-title">What Are You Looking For?</h2><p class="section-subtitle">Whether you want to rent, buy, or invest in land — we've got you covered</p></div>
            <div class="categories-grid">
                <a href="pages/listings.php?type=rent" class="category-card fade-in"><div class="category-icon"><i class="fas fa-key"></i></div><h3>Rent a Place</h3><p>Find short-term and long-term rental apartments, houses, and chalets across Lebanon.</p></a>
                <a href="pages/listings.php?type=buy" class="category-card fade-in"><div class="category-icon"><i class="fas fa-building"></i></div><h3>Buy a Property</h3><p>Browse apartments and houses for sale. Find your dream home at the right price.</p></a>
                <a href="pages/listings.php?type=land" class="category-card fade-in"><div class="category-icon"><i class="fas fa-map"></i></div><h3>Buy Land</h3><p>Invest in residential, commercial, or agricultural land plots across all regions.</p></a>
            </div>
        </div>
    </section>

    <section class="featured">
        <div class="container">
            <div class="section-header"><div class="section-label">Top Picks</div><h2 class="section-title">Featured Properties</h2><p class="section-subtitle">Handpicked properties from across Lebanon</p></div>
            <div class="featured-grid">
                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=600&q=80" alt="Modern Apartment"><span class="property-badge badge-rent">For Rent</span><div class="property-views"><i class="fas fa-eye"></i> 142</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Beirut, Hamra</div><h3 class="property-title">Modern 2-Bedroom Apartment with Sea View</h3><div class="property-features"><span class="property-feature"><i class="fas fa-bed"></i> 2 Beds</span><span class="property-feature"><i class="fas fa-bath"></i> 1 Bath</span><span class="property-feature"><i class="fas fa-ruler-combined"></i> 120 m²</span></div><div class="property-footer"><div class="property-price">$75 <span>/ night</span></div><div class="property-rating"><i class="fas fa-star"></i> 4.5</div></div></div></div>

                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=600&q=80" alt="Spacious Apartment"><span class="property-badge badge-buy">For Sale</span><div class="property-views"><i class="fas fa-eye"></i> 89</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Jounieh, Kaslik</div><h3 class="property-title">Spacious 3-Bedroom Apartment Near the Beach</h3><div class="property-features"><span class="property-feature"><i class="fas fa-bed"></i> 3 Beds</span><span class="property-feature"><i class="fas fa-bath"></i> 2 Baths</span><span class="property-feature"><i class="fas fa-ruler-combined"></i> 180 m²</span></div><div class="property-footer"><div class="property-price">$185,000</div><div class="property-rating"><i class="fas fa-star"></i> 5.0</div></div></div></div>

                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=600&q=80" alt="Land Plot"><span class="property-badge badge-land">Land</span><div class="property-views"><i class="fas fa-eye"></i> 56</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Byblos, Jbeil</div><h3 class="property-title">500 m² Residential Land with Mountain View</h3><div class="property-features"><span class="property-feature"><i class="fas fa-ruler-combined"></i> 500 m²</span><span class="property-feature"><i class="fas fa-mountain"></i> Mountain View</span><span class="property-feature"><i class="fas fa-home"></i> Residential</span></div><div class="property-footer"><div class="property-price">$120,000</div><div class="property-rating"><i class="fas fa-star"></i> 5.0</div></div></div></div>

                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=600&q=80" alt="Cozy Studio"><span class="property-badge badge-rent">For Rent</span><div class="property-views"><i class="fas fa-eye"></i> 210</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Batroun</div><h3 class="property-title">Cozy Studio with Rooftop Access in Old Souk</h3><div class="property-features"><span class="property-feature"><i class="fas fa-bed"></i> 1 Bed</span><span class="property-feature"><i class="fas fa-bath"></i> 1 Bath</span><span class="property-feature"><i class="fas fa-ruler-combined"></i> 55 m²</span></div><div class="property-footer"><div class="property-price">$45 <span>/ night</span></div><div class="property-rating"><i class="fas fa-star"></i> 5.0</div></div></div></div>

                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=600&q=80" alt="Penthouse"><span class="property-badge badge-buy">For Sale</span><div class="property-views"><i class="fas fa-eye"></i> 134</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Beirut, Achrafieh</div><h3 class="property-title">Luxurious 4-Bedroom Penthouse with Pool</h3><div class="property-features"><span class="property-feature"><i class="fas fa-bed"></i> 4 Beds</span><span class="property-feature"><i class="fas fa-bath"></i> 3 Baths</span><span class="property-feature"><i class="fas fa-ruler-combined"></i> 320 m²</span></div><div class="property-footer"><div class="property-price">$450,000</div><div class="property-rating"><i class="fas fa-star"></i> 4.0</div></div></div></div>

                <div class="property-card fade-in"><div class="property-img"><img src="https://images.unsplash.com/photo-1628624747186-a941c476b7ef?w=600&q=80" alt="Agricultural Land"><span class="property-badge badge-land">Land</span><div class="property-views"><i class="fas fa-eye"></i> 78</div></div><div class="property-info"><div class="property-location"><i class="fas fa-map-marker-alt"></i> Chouf, Deir el Qamar</div><h3 class="property-title">1200 m² Agricultural Land with Water Source</h3><div class="property-features"><span class="property-feature"><i class="fas fa-ruler-combined"></i> 1,200 m²</span><span class="property-feature"><i class="fas fa-water"></i> Water Source</span><span class="property-feature"><i class="fas fa-leaf"></i> Agricultural</span></div><div class="property-footer"><div class="property-price">$95,000</div><div class="property-rating"><i class="fas fa-star"></i> 4.0</div></div></div></div>
            </div>
            <div style="text-align:center;margin-top:48px;" class="fade-in"><a href="pages/listings.php" class="btn-primary">View All Properties <i class="fas fa-arrow-right"></i></a></div>
        </div>
    </section>

    <section class="why-section">
        <div class="container">
            <div class="section-header"><div class="section-label">Why Us</div><h2 class="section-title">Why Choose Manzeli?</h2><p class="section-subtitle">The smarter way to find your home in Lebanon</p></div>
            <div class="why-grid">
                <div class="why-card fade-in"><div class="why-icon"><i class="fas fa-shield-alt"></i></div><h3>Verified Listings</h3><p>Every property is verified to ensure quality and trust for all users.</p></div>
                <div class="why-card fade-in"><div class="why-icon"><i class="fas fa-search-location"></i></div><h3>Easy Search</h3><p>Filter by city, type, price, and more to find exactly what you need.</p></div>
                <div class="why-card fade-in"><div class="why-icon"><i class="fas fa-comments"></i></div><h3>Direct Contact</h3><p>Message hosts and sellers directly through our built-in chat system.</p></div>
                <div class="why-card fade-in"><div class="why-icon"><i class="fas fa-robot"></i></div><h3>AI Assistant</h3><p>Our chatbot helps you find properties and answers your questions 24/7.</p></div>
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="cta-container fade-in">
            <h2 class="cta-title">Have a Property to List?</h2>
            <p class="cta-text">Join Manzeli as a host and reach thousands of potential renters and buyers across Lebanon.</p>
            <div class="cta-buttons"><a href="pages/register.php" class="btn-primary">Become a Host <i class="fas fa-arrow-right"></i></a><a href="pages/listings.php" class="btn-outline">Browse Listings <i class="fas fa-search"></i></a></div>
        </div>
    </section>

<script>
document.querySelectorAll('.search-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('searchType').value = tab.dataset.type;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
