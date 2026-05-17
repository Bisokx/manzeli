<?php
session_start();
require_once 'db.php';
require_once 'smtp-mail.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$propertyId = (int)($_POST['property_id'] ?? 0);
$checkIn = $_POST['check_in'] ?? '';
$checkOut = $_POST['check_out'] ?? '';
$guests = (int)($_POST['guests'] ?? 1);
$paymentMethod = $_POST['payment_method'] ?? 'pay_on_arrival';

if ($propertyId <= 0 || empty($checkIn) || empty($checkOut)) {
    header('Location: ../pages/property.php?id=' . $propertyId . '&error=missing');
    exit;
}

// Get property info
$stmt = $pdo->prepare("SELECT price, price_period, listing_type, host_id, title, location FROM properties WHERE id = ?");
$stmt->execute([$propertyId]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: ../pages/listings.php');
    exit;
}

// Don't allow booking your own property
if ($property['host_id'] == $userId) {
    header('Location: ../pages/property.php?id=' . $propertyId);
    exit;
}

// Calculate total price
$days = max(1, (strtotime($checkOut) - strtotime($checkIn)) / 86400);
$pricePeriod = $property['price_period'] ?? 'night';

if ($pricePeriod === 'month') {
    $totalPrice = $property['price'] * ($days / 30);
} elseif ($pricePeriod === 'total') {
    $totalPrice = $property['price'];
} else {
    $totalPrice = $property['price'] * $days;
}

$totalPrice = round($totalPrice, 2);

// Check if dates are already booked
$availStmt = $pdo->prepare("
    SELECT id FROM bookings 
    WHERE property_id = ? 
    AND status IN ('confirmed') 
    AND check_in < ? AND check_out > ?
");
$availStmt->execute([$propertyId, $checkOut, $checkIn]);
if ($availStmt->fetch()) {
    header('Location: ../pages/property.php?id=' . $propertyId . '&error=dates_taken');
    exit;
}

// All bookings are automatically confirmed
$status = 'confirmed';

// Insert booking with payment method
$stmt = $pdo->prepare("INSERT INTO bookings (user_id, property_id, check_in, check_out, guests, total_price, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([$userId, $propertyId, $checkIn, $checkOut, $guests, $totalPrice, $paymentMethod, $status]);

$bookingId = $pdo->lastInsertId();

// Send email confirmation for all bookings
{
    $userEmail = $_SESSION['email'] ?? '';
    $userName = $_SESSION['full_name'] ?? 'Guest';
    
    if (!empty($userEmail)) {
        $subject = "Manzeli - Booking Confirmation #" . $bookingId;
        
        $htmlMessage = '
<html>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
        <div style="background:linear-gradient(135deg,#0ABAB5,#089E9A);color:#fff;padding:30px;text-align:center;">
            <h1 style="margin:0;font-size:24px;">🏠 Manzeli</h1>
            <p style="margin:5px 0 0;opacity:0.9;">Booking Confirmation</p>
        </div>
        <div style="padding:30px;">
            <h2 style="color:#333;font-size:20px;margin-top:0;">Thank you, ' . htmlspecialchars($userName) . '!</h2>
            <p style="color:#666;line-height:1.6;">Your booking has been confirmed and paid via credit card. Here are your details:</p>
            
            <div style="background:#f8f8f8;border-radius:8px;padding:20px;margin:20px 0;">
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:8px 0;color:#888;width:140px;">Booking ID</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">#' . $bookingId . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Property</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">' . htmlspecialchars($property['title']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Location</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">' . htmlspecialchars($property['location']) . ', Lebanon</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Check-in</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">' . date('M d, Y', strtotime($checkIn)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Check-out</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">' . date('M d, Y', strtotime($checkOut)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Guests</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">' . $guests . '</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Payment</td>
                        <td style="padding:8px 0;color:#333;font-weight:600;">💳 Credit Card</td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;color:#888;">Status</td>
                        <td style="padding:8px 0;color:#155724;font-weight:600;">✓ Confirmed</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0 8px;color:#888;font-size:16px;border-top:2px solid #ddd;">Total Paid</td>
                        <td style="padding:12px 0 8px;color:#0ABAB5;font-weight:700;font-size:18px;border-top:2px solid #ddd;">$' . number_format($totalPrice, 2) . '</td>
                    </tr>
                </table>
            </div>
            
            <p style="color:#666;font-size:14px;line-height:1.6;">View your booking anytime in your <b>Dashboard → My Bookings</b>.</p>
            <p style="color:#666;font-size:14px;">Questions? Email <b>info@manzeli.com</b> or call <b>+961 70 322 369</b></p>
        </div>
        <div style="text-align:center;padding:20px;color:#999;font-size:12px;border-top:1px solid #eee;">
            &copy; ' . date('Y') . ' Manzeli – منزلي | West Bekaa, Sohmor, Lebanon
        </div>
    </div>
</body>
</html>';
        
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Manzeli <noreply@manzeli.com>\r\n";
        $headers .= "Reply-To: info@manzeli.com\r\n";
        
        @mail($userEmail, $subject, $htmlMessage, $headers);
    }
}

header('Location: ../pages/dashboard.php?tab=bookings&success=booked');
exit;
