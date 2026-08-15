<?php

return [
    'api_token' => env('TRUCKFUND_API_TOKEN'),
    'high_value_score_threshold' => (int) env('TRUCKFUND_HIGH_VALUE_SCORE', 185),
    'lead_number_prefix' => env('TRUCKFUND_LEAD_PREFIX', 'LD'),
    'app_number_prefix' => env('TRUCKFUND_APP_PREFIX', 'FA'),

    'image_max_kb' => (int) env('TRUCKFUND_IMAGE_MAX_KB', 2048),
    'image_max_width' => (int) env('TRUCKFUND_IMAGE_MAX_WIDTH', 1920),
    'image_jpeg_quality' => (int) env('TRUCKFUND_IMAGE_JPEG_QUALITY', 82),
    'storage_retention_months' => (int) env('TRUCKFUND_STORAGE_RETENTION_MONTHS', 6),

    // The only accounts created by `truckfund:seed-demo`. Override the passwords
    // per environment; the defaults exist so a fresh install is never blank.
    'seed_admins' => [
        [
            'full_name' => 'Aiman',
            'email' => env('TRUCKFUND_SEED_AIMAN_EMAIL', 'aiman@autofund.test'),
            'password' => env('TRUCKFUND_SEED_AIMAN_PASSWORD', 'Ay#7mNq4Zt$Rv2Kx'),
        ],
        [
            'full_name' => 'Mohammed',
            'email' => env('TRUCKFUND_SEED_MOHAMMED_EMAIL', 'mohammed@autofund.test'),
            'password' => env('TRUCKFUND_SEED_MOHAMMED_PASSWORD', 'Mh$9dRk6Xw#Pb3Tq'),
        ],
    ],
];
