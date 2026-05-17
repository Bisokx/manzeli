<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/smtp-mail.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$msgId = (int)($_POST['msg_id'] ?? 0);
$toEmail = trim($_POST['to_email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$replyMessage = trim($_POST['reply_message'] ?? '');

if (empty($toEmail) || empty($replyMessage)) {
    header('Location: index.php?replied=error');
    exit;
}

// Get original message info
$stmt = $pdo->prepare("SELECT name FROM contact_messages WHERE id = ?");
$stmt->execute([$msgId]);
$original = $stmt->fetch();
$recipientName = $original ? $original['name'] : 'User';

// Build HTML email
$htmlMessage = '
<html>
<body style="font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.1);">
        <div style="background:linear-gradient(135deg,#0ABAB5,#089E9A);color:#fff;padding:28px;text-align:center;">
            <h1 style="margin:0;font-size:22px;">🏠 Manzeli</h1>
            <p style="margin:5px 0 0;opacity:0.9;font-size:14px;">We received your message</p>
        </div>
        <div style="padding:28px;">
            <h2 style="color:#333;font-size:18px;margin-top:0;">Hello, ' . htmlspecialchars($recipientName) . '!</h2>
            <p style="color:#666;line-height:1.7;font-size:14px;">Thank you for reaching out to Manzeli. Here is our reply:</p>
            <div style="background:#f8f8f8;border-left:4px solid #0ABAB5;border-radius:0 8px 8px 0;padding:16px 20px;margin:20px 0;">
                <p style="color:#333;line-height:1.7;font-size:14px;margin:0;">' . nl2br(htmlspecialchars($replyMessage)) . '</p>
            </div>
            <p style="color:#666;font-size:13px;margin-top:20px;">If you have more questions, feel free to reply to this email or visit our website.</p>
            <p style="color:#666;font-size:13px;">Best regards,<br><strong>Manzeli Team</strong></p>
        </div>
        <div style="text-align:center;padding:18px;color:#999;font-size:11px;border-top:1px solid #eee;">
            &copy; ' . date('Y') . ' Manzeli – منزلي | West Bekaa, Sohmor, Lebanon<br>
            <a href="mailto:info@manzeli.com" style="color:#0ABAB5;">info@manzeli.com</a> | +961 70 322 369
        </div>
    </div>
</body>
</html>';


// Send email
$sent = manzeli_mail($toEmail, $subject, $htmlMessage, $headers);

// Mark as read
$pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$msgId]);

header('Location: index.php?replied=success');
exit;
