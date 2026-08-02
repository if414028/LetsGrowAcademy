<?php

return [
    'monthly_price' => (int) env('SUBSCRIPTION_MONTHLY_PRICE', 50000),
    'durations' => [3, 6, 12],
    'warning_days' => 7,

    'bank' => [
        'name' => env('SUBSCRIPTION_BANK_NAME', 'BCA'),
        'account_number' => env('SUBSCRIPTION_BANK_ACCOUNT', '0000000000'),
        'account_holder' => env('SUBSCRIPTION_BANK_HOLDER', "Let's Grow Academy"),
    ],
];
