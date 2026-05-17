<?php
// ============================================================
// MANZELI — Email Helper (Resend API)
// Uses HTTP API — no SMTP ports needed
// ============================================================

function manzeli_mail($to, $subject, $htmlBody) {
    $apiKey = 're_E7fneEWi_KV1Mu89zWMTBsUwnzt1WAi5n';
    
    $data = [
        'from' => 'Manzeli <onboarding@resend.dev>',
        'to' => [$to],
        'subject' => $subject,
        'html' => $htmlBody
    ];
    
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 15
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log("Resend error: " . $curlError);
        return false;
    }
    
    if ($httpCode === 200 || $httpCode === 201) {
        return true;
    }
    
    error_log("Resend API error ({$httpCode}): " . $response);
    return false;
}
?>
