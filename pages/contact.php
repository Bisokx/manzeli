<?php
$pageTitle = 'Contact Us';
require_once '../includes/header.php';
?>

<section style="padding:60px 0 80px;background:var(--off-white)">
    <div class="container">
        <div class="section-header" style="margin-bottom:40px">
            <div class="section-label">Get in Touch</div>
            <h2 class="section-title">Contact Us</h2>
            <p class="section-subtitle">Have a question? We'd love to hear from you</p>
        </div>

        <div style="max-width:900px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:32px">
            <!-- Contact Form -->
            <div style="background:var(--white);padding:32px;border-radius:var(--border-radius-lg);box-shadow:var(--shadow-sm)">
                <h3 style="font-family:var(--font-heading);font-size:1.2rem;color:var(--secondary);margin-bottom:20px">Send a Message</h3>
                <?php if (isset($_GET['success']) && $_GET['success'] === 'sent'): ?>
                    <div style="background:#d4edda;color:#155724;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:16px;">
                        <i class="fas fa-check-circle"></i> Your message has been sent successfully! We'll get back to you soon.
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'missing'): ?>
                    <div style="background:#ffe0e0;color:#c00;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:16px;">
                        <i class="fas fa-exclamation-circle"></i> Please fill in all required fields.
                    </div>
                <?php endif; ?>
                <form action="/includes/contact-submit.php" method="POST" style="display:flex;flex-direction:column;gap:16px">
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;color:var(--charcoal);margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px">Your Name</label>
                        <input type="text" name="name" placeholder="Enter your name" required style="width:100%;padding:12px 14px;border:1.5px solid var(--light-gray);border-radius:var(--border-radius-sm);font-size:.9rem;background:var(--off-white)">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;color:var(--charcoal);margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px">Email</label>
                        <input type="email" name="email" placeholder="you@example.com" required style="width:100%;padding:12px 14px;border:1.5px solid var(--light-gray);border-radius:var(--border-radius-sm);font-size:.9rem;background:var(--off-white)">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;color:var(--charcoal);margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px">Subject</label>
                        <input type="text" name="subject" placeholder="How can we help?" style="width:100%;padding:12px 14px;border:1.5px solid var(--light-gray);border-radius:var(--border-radius-sm);font-size:.9rem;background:var(--off-white)">
                    </div>
                    <div>
                        <label style="display:block;font-size:.82rem;font-weight:600;color:var(--charcoal);margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px">Message</label>
                        <textarea name="message" rows="5" placeholder="Your message..." required style="width:100%;padding:12px 14px;border:1.5px solid var(--light-gray);border-radius:var(--border-radius-sm);font-size:.9rem;background:var(--off-white);resize:vertical"></textarea>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%;justify-content:center">Send Message <i class="fas fa-paper-plane"></i></button>
                </form>
            </div>

            <!-- Contact Info -->
            <div>
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm);margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px">
                        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(10,186,181,.12),rgba(110,231,183,.12));color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="fas fa-map-marker-alt"></i></div>
                        <div><strong style="color:var(--secondary);font-size:.95rem">Address</strong><p style="color:var(--dark-gray);font-size:.88rem">West Bekaa, Sohmor, Lebanon</p></div>
                    </div>
                </div>
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm);margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px">
                        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(255,107,107,.12),rgba(251,191,36,.12));color:var(--coral);display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="fas fa-phone"></i></div>
                        <div><strong style="color:var(--secondary);font-size:.95rem">Phone</strong><p style="color:var(--dark-gray);font-size:.88rem">+961 70 322 369</p></div>
                    </div>
                </div>
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm);margin-bottom:16px">
                    <div style="display:flex;align-items:center;gap:14px;margin-bottom:10px">
                        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(167,139,250,.12),rgba(56,189,248,.12));color:var(--lavender);display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="fas fa-envelope"></i></div>
                        <div><strong style="color:var(--secondary);font-size:.95rem">Email</strong><p style="color:var(--dark-gray);font-size:.88rem">info@manzeli.com</p></div>
                    </div>
                </div>
                <div style="background:var(--white);padding:28px;border-radius:var(--border-radius);box-shadow:var(--shadow-sm)">
                    <div style="display:flex;align-items:center;gap:14px">
                        <div style="width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,rgba(110,231,183,.12),rgba(10,186,181,.12));color:var(--success);display:flex;align-items:center;justify-content:center;font-size:1rem"><i class="fas fa-clock"></i></div>
                        <div><strong style="color:var(--secondary);font-size:.95rem">Hours</strong><p style="color:var(--dark-gray);font-size:.88rem">Mon - Fri: 9AM - 6PM</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>
