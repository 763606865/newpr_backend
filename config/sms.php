<?php

return [
    'driver' => env('SMS_DRIVER', 'jucai'),

    'jucai' => [
        'username' => env('SMS_JUCAI_USERNAME', 'bmoa'),
        'password' => env('SMS_JUCAI_PASSWORD', '387138'),
        'template_id' => env('SMS_JUCAI_TEMPLATE_ID', '123456'),
    ],
];
