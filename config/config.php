<?php

return [
    'app_url' => getenv('APP_URL') ?: 'https://a31e7935dec1.ngrok-free.app', // The webhook URL is set automatically. You can check the channel settings on the channel page in the control panel.
    'whatsapp_token' => 'Q0MpTRcITQaVA6ZClKeQSFXS38IVkcax', // Use the Token API from the channel page in the dashboard https://panel.whapi.cloud/dashboard
    // Optional: Absolute path to CA certificates bundle (cacert.pem). Needed on Windows to avoid cURL error 60.
    // Example: __DIR__ . '/cacert.pem'
    'ca_bundle' => file_exists(__DIR__ . '/cacert.pem') ? (__DIR__ . '/cacert.pem') : null,
];