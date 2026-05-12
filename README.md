# 🏠 Manzeli – منزلي

**Your gateway to finding the perfect home in Lebanon**

Manzeli (منزلي — "My Home" in Arabic) is a full-stack real estate platform for renting, buying, and investing in properties and land across Lebanon. Built as a Senior Project for the 2024–2025 academic year.

🌐 **Live Demo:** [manzeli-production.up.railway.app](https://manzeli-production.up.railway.app)

---

## ✨ Features

### For Guests
- 🔍 Browse & filter properties by type, location, price, bedrooms
- 📅 Book rental properties with date selection & availability calendar
- 💳 Payment options: Credit Card or Pay on Arrival
- 📧 Email confirmation on booking
- ⭐ Write reviews with star ratings (1-5)
- ❤️ Save properties to favorites
- 💬 Message hosts directly from property pages
- 🔔 Notification bell for unread messages
- 🔐 Google Login / Register support
- 🔑 Forgot password with email reset link

### For Hosts
- 🏡 List unlimited properties for free (Rent / Buy / Land)
- 📸 Upload property images
- ✅ 15 amenities to choose from (WiFi, Pool, A/C, Generator, Sea View, etc.)
- 📊 Dashboard with stats: listings, views, bookings, inquiries
- 📅 Availability calendar showing booked dates
- 💬 Receive & reply to guest messages
- 📋 Manage bookings and purchase inquiries

### For Admins
- 📊 Dashboard with 6 stat cards (users, properties, bookings, views, reviews, messages)
- 👥 User management (change roles, delete users)
- 🏢 Listing management (change status, delete listings)
- 📧 Contact messages inbox with reply-to-email functionality
- 📈 Platform breakdown (guests/hosts, rent/buy/land counts)

### AI Chatbot 🤖
- Powered by **Groq AI** (Llama 3.3 70B)
- Connected to the database — searches properties in real-time
- Answers questions about the platform
- Smart keyword detection for property searches
- Property cards with images, prices, and direct links
- Conversation memory within session

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 8.2 |
| Database | MySQL |
| AI | Groq API (Llama 3.3 70B) |
| Auth | PHP Sessions + Google OAuth 2.0 |
| Hosting | Railway |
| Fonts | Playfair Display, DM Sans, Noto Kufi Arabic |
| Icons | Font Awesome 6.5 |

---

## 📁 Project Structure

```
manzeli/
├── index.php                    # Homepage
├── Dockerfile                   # Railway deployment config
├── assets/
│   ├── css/
│   │   ├── style.css            # Main styles + responsive
│   │   ├── auth.css             # Login/Register pages
│   │   ├── listings.css         # Listings page
│   │   ├── property.css         # Property details page
│   │   ├── dashboard.css        # Dashboard styles
│   │   ├── host.css             # Host panel styles
│   │   └── admin.css            # Admin panel styles
│   ├── js/
│   │   └── main.js              # Main JavaScript
│   └── images/                  # Property images
├── includes/
│   ├── db.php                   # Database connection
│   ├── header.php               # Shared header/navbar
│   ├── footer.php               # Shared footer
│   ├── auth.php                 # Login/Register handler
│   ├── logout.php               # Session destroy
│   ├── book.php                 # Booking handler + email
│   ├── chatbot-widget.php       # AI Chatbot (Groq + DB)
│   ├── google-config.php        # Google OAuth config
│   ├── google-callback.php      # Google OAuth callback
│   ├── toggle-favorite.php      # AJAX favorites toggle
│   ├── send-message.php         # Messaging handler
│   ├── submit-review.php        # Review submission
│   ├── contact-submit.php       # Contact form handler
│   └── purchase-request.php     # Buy/Land inquiry handler
├── pages/
│   ├── login.php                # Login page
│   ├── register.php             # Registration page
│   ├── forgot-password.php      # Password reset request
│   ├── reset-password.php       # Password reset form
│   ├── choose-role.php          # Google signup role selection
│   ├── listings.php             # All listings + filters
│   ├── property.php             # Property details
│   ├── dashboard.php            # Guest/Host dashboard
│   ├── profile.php              # Edit profile
│   ├── about.php                # About page
│   ├── contact.php              # Contact page
│   ├── host/
│   │   ├── add-property.php     # Add/Edit property form
│   │   └── my-listings.php      # Host's listings
│   └── admin/
│       ├── index.php            # Admin dashboard
│       ├── users.php            # Manage users
│       ├── listings.php         # Manage listings
│       ├── mark-read.php        # Mark messages read
│       └── reply-message.php    # Reply to contact messages
```

---

## 🗄️ Database Schema

**13 Tables:**

| Table | Purpose |
|-------|---------|
| `users` | User accounts (guest, host, admin) |
| `properties` | Property listings |
| `property_images` | Multiple images per property |
| `amenities` | 15 predefined amenities |
| `property_amenities` | Property-amenity links (many-to-many) |
| `bookings` | Rental bookings with payment method |
| `purchase_requests` | Buy/Land inquiries |
| `reviews` | Star ratings and comments |
| `availability` | Property date availability |
| `messages` | Guest-Host messaging |
| `favorites` | Saved/wishlisted properties |
| `chatbot_logs` | AI chatbot conversation history |
| `contact_messages` | Contact form submissions |

---

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- XAMPP (for local development)


## 📱 Responsive Design

Fully responsive across all devices:
- 🖥️ Desktop (1024px+)
- 📱 Tablet (768px - 1024px)
- 📱 Mobile (480px - 768px)
- 📱 Small Mobile (360px - 480px)

---

## 📞 Contact

- **Email:** info@manzeli.com
- **Phone:** +961 70 322 369
- **Location:** West Bekaa, Sohmor, Lebanon
- **Instagram:** [@bassimkaz](https://instagram.com/bassimkaz)
- **Facebook:** [Bassim Kazwini](https://facebook.com/bassim.kazwini)
- **WhatsApp:** [+961 70 322 369](https://wa.me/96170322369)

---

## 👨‍💻 Author

**Bassim Kazouini**  
Senior Project 2024–2025

---

*Built with ❤️ in Lebanon*
