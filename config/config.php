<?php

return [
    'app_url' => getenv('APP_URL') ?: 'https://717b936562b1.ngrok-free.app', // Set the webhook URL on the channel page in the dashboard or use the setWebHook() function (not used now)
    'whatsapp_token' => 'Q0MpTRcITQaVA6ZClKeQSFXS38IVkcax', // Use the Token API from the channel page in the dashboard https://panel.whapi.cloud/dashboard
    // Optional: Absolute path to CA certificates bundle (cacert.pem). Needed on Windows to avoid cURL error 60.
    // Example: __DIR__ . '/cacert.pem'
    'ca_bundle' => file_exists(__DIR__ . '/cacert.pem') ? (__DIR__ . '/cacert.pem') : null,
];