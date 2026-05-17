<?php
// ============================================================
// MANZELI — SMTP Email Helper (Gmail)
// Include this file and use manzeli_mail() instead of mail()
// ============================================================

function manzeli_mail($to, $subject, $htmlBody) {
    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;
    $smtpUser = 'manzeli.info@gmail.com';
    $smtpPass = 'cbsq pniu ppna xxgy';
    $fromName = 'Manzeli';
    $fromEmail = 'manzeli.info@gmail.com';

    // Build email headers
    $boundary = md5(time());
    $headers = "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Connect to SMTP
    $socket = @fsockopen('tls://' . $smtpHost, 465, $errno, $errstr, 30);
    if (!$socket) {
        // Try port 587 with STARTTLS
        $socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP Connection failed: {$errstr}");
            return false;
        }
        fread($socket, 1024);
        fwrite($socket, "EHLO manzeli.com\r\n"); fread($socket, 1024);
        fwrite($socket, "STARTTLS\r\n"); fread($socket, 1024);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        fwrite($socket, "EHLO manzeli.com\r\n"); fread($socket, 1024);
    } else {
        fread($socket, 1024);
        fwrite($socket, "EHLO manzeli.com\r\n"); fread($socket, 1024);
    }

    // Authenticate
    fwrite($socket, "AUTH LOGIN\r\n"); fread($socket, 1024);
    fwrite($socket, base64_encode($smtpUser) . "\r\n"); fread($socket, 1024);
    fwrite($socket, base64_encode($smtpPass) . "\r\n"); 
    $authResponse = fread($socket, 1024);
    
    if (strpos($authResponse, '235') === false) {
        error_log("SMTP Auth failed: " . $authResponse);
        fclose($socket);
        return false;
    }

    // Send email
    fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n"); fread($socket, 1024);
    fwrite($socket, "RCPT TO:<{$to}>\r\n"); fread($socket, 1024);
    fwrite($socket, "DATA\r\n"); fread($socket, 1024);

    $message = "Subject: {$subject}\r\n";
    $message .= $headers;
    $message .= "To: {$to}\r\n";
    $message .= "\r\n";
    $message .= $htmlBody;
    $message .= "\r\n.\r\n";

    fwrite($socket, $message);
    $result = fread($socket, 1024);

    fwrite($socket, "QUIT\r\n");
    fclose($socket);

    return strpos($result, '250') !== false;
}
?>
