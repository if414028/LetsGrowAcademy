<?php

return [
    'api_token' => env('CRM_API_TOKEN'),
    'require_https' => env('CRM_REQUIRE_HTTPS', true),
    'webhooks_enabled' => env('CRM_WEBHOOKS_ENABLED', false),
    'registration_webhook_url' => env('CRM_REGISTRATION_WEBHOOK_URL'),
    'payment_webhook_url' => env('CRM_PAYMENT_WEBHOOK_URL'),
    'webhook_secret' => env('CRM_WEBHOOK_SECRET'),
];
