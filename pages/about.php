<?php
$pageTitle = 'About Us';
require_once '../includes/header.php';
?>

<section class="about-section" style="padding:60px 0 80px">
    <div class="container">
        <div class="section-header" style="margin-bottom:40px">
            <div class="section-label">About</div>
            <h2 class="section-title">About Manzeli</h2>
            <p class="section-subtitle">Your trusted real estate platform in Lebanon</p>
        </div>

        <div style="max-width:800px;margin:0 auto">
            <div style="background:var(--white);padding:40px;border-radius:var(--border-radius-lg);box-shadow:var(--shadow-sm);margin-bottom:32px">
                <h3 style="font-family:var(--font-heading);font-size:1.4rem;color:var(--secondary);margin-bottom:16px">Our Mission</h3>
                <p style="color:var(--charcoal);line-height:1.8;font-size:.95rem;margin-bottom:20px">Manzeli (منزلي — "My Home" in Arabic) was created with a simple goal: make finding a home in Lebanon easier, faster, and more transparent. Whether you're looking to rent an apartment in Beirut, buy a villa in Jounieh, or invest in land in the Chouf mountains — we bring everything together in one modern platform.</p>
                <p style="color:var(--charcoal);line-height:1.8;font-size:.95rem">We believe everyone deserves to find their perfect home without the hassle of outdated listings, unreliable information, or complicated processes.</p>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:32px">
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm);text-align:center">
                    <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(10,186,181,.12),rgba(110,231,183,.12));color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto 14px"><i class="fas fa-eye"></i></div>
                    <h4 style="font-family:var(--font-heading);color:var(--secondary);margin-bottom:8px">Our Vision</h4>
                    <p style="color:var(--dark-gray);font-size:.88rem;line-height:1.6">To be Lebanon's #1 real estate platform, connecting people with their dream homes through technology and trust.</p>
                </div>
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm);text-align:center">
                    <div style="width:56px;height:56px;border-radius:14px;background:linear-gradient(135deg,rgba(255,107,107,.12),rgba(251,191,36,.12));color:var(--coral);display:flex;align-items:center;justify-content:center;font-size:1.3rem;margin:0 auto 14px"><i class="fas fa-heart"></i></div>
                    <h4 style="font-family:var(--font-heading);color:var(--secondary);margin-bottom:8px">Our Values</h4>
                    <p style="color:var(--dark-gray);font-size:.88rem;line-height:1.6">Transparency, trust, and user-first design. We verify listings and provide tools that put you in control.</p>
                </div>
            </div>

            <div style="background:linear-gradient(135deg,var(--secondary),var(--secondary-dark));padding:40px;border-radius:var(--border-radius-lg);text-align:center;color:var(--white)">
                <h3 style="font-family:var(--font-heading);font-size:1.3rem;margin-bottom:10px">Senior Project 2024–2025</h3>
                <p style="color:rgba(255,255,255,.6);font-size:.92rem;margin-bottom:6px">Developed by <strong style="color:var(--mint)">Bassim Kazouini</strong></p>
                <p style="color:rgba(255,255,255,.4);font-size:.82rem">Built with PHP, MySQL, HTML, CSS & JavaScript</p>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
