<?php

return [
    'sender' => env('BIRTHDAY_WHATSAPP_SENDER', '+6285121069310'),
    'timezone' => 'Asia/Jakarta',
    'send_hour' => 9,
    'gateway_url' => env('BIRTHDAY_WHATSAPP_GATEWAY_URL'),
    'gateway_token' => env('BIRTHDAY_WHATSAPP_GATEWAY_TOKEN'),
];
