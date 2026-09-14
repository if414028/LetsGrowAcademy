<?php

return [
    'merchant_id' => env('MIDTRANS_MERCHANT_ID'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'finish_url' => env('MIDTRANS_FINISH_URL', rtrim((string) env('APP_URL'), '/').'/subscription/payment-finish'),
];
