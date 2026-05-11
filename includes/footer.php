</main>
<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="footer-wave">
        <svg viewBox="0 0 1440 100" preserveAspectRatio="none">
            <path d="M0,40 C360,100 1080,0 1440,60 L1440,100 L0,100 Z" fill="currentColor"/>
        </svg>
    </div>
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/index.php" class="footer-logo">
                    <span class="logo-icon"><i class="fas fa-home"></i></span>
                    <span>Manzeli</span>
                    <span class="logo-arabic">منزلي</span>
                </a>
                <p class="footer-desc">Your gateway to finding the perfect home in Lebanon. Rent, buy, or find your dream land — all in one place.</p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/bassim.kazwini" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/bassimkaz" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="https://wa.me/96170322369" target="_blank"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Explore</h4>
                <ul>
                    <li><a href="/pages/listings.php?type=rent">Rent a Home</a></li>
                    <li><a href="/pages/listings.php?type=buy">Buy Property</a></li>
                    <li><a href="/pages/listings.php?type=land">Find Land</a></li>
                    <li><a href="/pages/listings.php">All Listings</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="/pages/about.php">About Us</a></li>
                    <li><a href="/pages/contact.php">Contact</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Terms of Service</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact Us</h4>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> West Bekaa, Sohmor, Lebanon</li>
                    <li><i class="fas fa-phone"></i> +961 70 322 369</li>
                    <li><i class="fas fa-envelope"></i> info@manzeli.com</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Manzeli – منزلي. All rights reserved.</p>
            <p>Senior Project by Bassim Kazouini</p>
        </div>
    </div>
</footer>

<!-- Robot button FIRST, then widget (so JS can find the button) -->
<div class="chatbot-btn" id="chatbotBtn" title="Chat with Manzeli AI">
    <i class="fas fa-robot"></i>
</div>

<?php include __DIR__ . '/chatbot-widget.php'; ?>

<script src="/assets/js/main.js"></script>
</body>
</html>
