<?php
// ============================================================
// MANZELI — SMTP Email Helper (Gmail) 
// Uses PHP streams with SSL for Gmail SMTP
// ============================================================

function manzeli_mail($to, $subject, $htmlBody) {
    $smtpUser = 'manzeli.info@gmail.com';
    $smtpPass = 'cbsq pniu ppna xxgy';
    $fromName = 'Manzeli';

    try {
        // Connect via SSL on port 465
        $ctx = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $socket = stream_socket_client(
            'ssl://smtp.gmail.com:465',
            $errno, $errstr, 30,
            STREAM_CLIENT_CONNECT,
            $ctx
        );
        
        if (!$socket) {
            error_log("SMTP Connect failed: {$errno} {$errstr}");
            return false;
        }

        // Read greeting
        $response = fgets($socket, 1024);

        // EHLO
        fwrite($socket, "EHLO manzeli.com\r\n");
        while ($line = fgets($socket, 1024)) {
            if (substr($line, 3, 1) == ' ') break;
        }

        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        fgets($socket, 1024);

        fwrite($socket, base64_encode($smtpUser) . "\r\n");
        fgets($socket, 1024);

        fwrite($socket, base64_encode($smtpPass) . "\r\n");
        $authResult = fgets($socket, 1024);

        if (strpos($authResult, '235') === false) {
            error_log("SMTP Auth failed: " . trim($authResult));
            fclose($socket);
            return false;
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$smtpUser}>\r\n");
        fgets($socket, 1024);

        // RCPT TO
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        fgets($socket, 1024);

        // DATA
        fwrite($socket, "DATA\r\n");
        fgets($socket, 1024);

        // Build message
        $msg = "Date: " . date('r') . "\r\n";
        $msg .= "From: {$fromName} <{$smtpUser}>\r\n";
        $msg .= "To: {$to}\r\n";
        $msg .= "Subject: {$subject}\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "\r\n";
        $msg .= $htmlBody;
        $msg .= "\r\n.\r\n";

        fwrite($socket, $msg);
        $result = fgets($socket, 1024);

        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        $success = strpos($result, '250') !== false;
        if (!$success) {
            error_log("SMTP Send failed: " . trim($result));
        }
        return $success;

    } catch (Exception $e) {
        error_log("SMTP Error: " . $e->getMessage());
        return false;
    }
}
?>
