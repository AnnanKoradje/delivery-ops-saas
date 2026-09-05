<?php

return [
    'mode' => env('DELIVERY_EMAIL_MODE', 'preview'),
    'from_address' => env('DELIVERY_PLATFORM_FROM_ADDRESS'),
    'from_name' => env('DELIVERY_PLATFORM_FROM_NAME', 'Delivery updates'),
];
