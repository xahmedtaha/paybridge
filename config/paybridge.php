<?php

// config for AhmedTaha/PayBridge
return [
    'gateways' => [
        'fawrypay' => [
            'merchant_id' => env('FAWRYPAY_MERCHANT_ID'),
            'access_key' => env('FAWRYPAY_ACCESS_KEY'),
            'secret_key' => env('FAWRYPAY_SECRET_KEY'),
        ]
    ]
];
