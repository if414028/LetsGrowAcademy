<?php

return [
    'monthly_price' => (int) env('SUBSCRIPTION_MONTHLY_PRICE', 50000),
    'durations' => [3, 6, 12],
    'warning_days' => 7,

    // Only include channels that have been activated in the Midtrans dashboard.
    'enabled_payment_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env(
        'SUBSCRIPTION_PAYMENT_METHODS',
        'bni_va,cimb_va,permata_va,bri_va,gopay_qris,bsi_va,gopay,echannel',
    ))))),
    'gateway_vat_percent' => (int) env('SUBSCRIPTION_GATEWAY_VAT_PERCENT', 11),

    // Rates from the merchant's dashboard; percentage rates use basis points (100 = 1%).
    // E-wallet rates use the digital-product category for this digital subscription.
    'payment_methods' => [
        'bsi_va' => ['label' => 'BSI Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'cimb_va' => ['label' => 'CIMB Niaga Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'bni_va' => ['label' => 'BNI Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'bri_va' => ['label' => 'BRI Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'echannel' => ['label' => 'Mandiri Bill Payment', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'permata_va' => ['label' => 'PermataBank Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'seabank_va' => ['label' => 'SeaBank Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'danamon_va' => ['label' => 'Danamon Virtual Account', 'group' => 'Bank transfer', 'fixed_fee' => 4000, 'fee_basis_points' => 0],
        'gopay_qris' => ['label' => 'GoPay Dynamic QRIS', 'group' => 'QRIS', 'fixed_fee' => 0, 'fee_basis_points' => 70, 'snap_payment_method' => 'gopay', 'snap_ui_mode' => 'qr'],
        'gopay' => ['label' => 'GoPay', 'group' => 'E-wallet', 'fixed_fee' => 0, 'fee_basis_points' => 400, 'snap_ui_mode' => 'deeplink'],
        'dana' => ['label' => 'DANA', 'group' => 'E-wallet', 'fixed_fee' => 0, 'fee_basis_points' => 150],
        'ovo' => ['label' => 'OVO', 'group' => 'E-wallet', 'fixed_fee' => 0, 'fee_basis_points' => 273],
        'shopeepay' => ['label' => 'ShopeePay', 'group' => 'E-wallet', 'fixed_fee' => 0, 'fee_basis_points' => 400, 'snap_ui_mode' => 'deeplink'],
    ],

    'bank' => [
        'name' => env('SUBSCRIPTION_BANK_NAME', 'BCA'),
        'account_number' => env('SUBSCRIPTION_BANK_ACCOUNT', '0000000000'),
        'account_holder' => env('SUBSCRIPTION_BANK_HOLDER', "Let's Grow Academy"),
    ],
];
