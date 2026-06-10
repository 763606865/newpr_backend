<?php

return [
    'default' => env('OCR_DRIVER', 'aliyun'),

    'drivers' => [
        'aliyun' => [
            'access_key_id' => env('OCR_ACCESS_KEY_ID', env('OSS_ACCESS_KEY_ID')),
            'access_key_secret' => env('OCR_ACCESS_KEY_SECRET', env('OSS_ACCESS_KEY_SECRET')),
            'endpoint' => env('OCR_ENDPOINT', 'ocr-api.cn-hangzhou.aliyuncs.com'),
            'region_id' => env('OCR_REGION_ID', 'cn-hangzhou'),
            'connect_timeout' => (int) env('OCR_CONNECT_TIMEOUT', 5),
            'read_timeout' => (int) env('OCR_READ_TIMEOUT', 10),
        ],
    ],
];
