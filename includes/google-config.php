<?php
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://manzeli-production.up.railway.app/includes/google-callback.php');

function getGoogleLoginUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}
