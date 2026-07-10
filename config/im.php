<?php

return [
    'default' => env('IM_DRIVER', 'custom'),

    // 自定义
    'custom' => [
        'end_point' => env('IM_END_POINT', ''),
        'app_key' => env('IM_APP_KEY', ''),
        'app_secret' => env('IM_APP_SECRET', ''),
        'app_code' => env('IM_APP_CODE', ''),
    ],
    // 腾讯IM
    'tencent' => [
        'end_point' => env('IM_END_POINT', ''),
        'app_key' => env('IM_APP_KEY', ''),
        'app_secret' => env('IM_APP_SECRET', ''),
    ],
    // 环信IM
    'easemob' => [
        'end_point' => env('IM_END_POINT', ''),
        'app_key' => env('IM_APP_KEY', ''),
        'app_secret' => env('IM_APP_SECRET', ''),
    ],

    // 融云 (RongCloud)
    'rongcloud' => [
        'end_point' => env('IM_RONGCLOUD_END_POINT', ''),
        'app_key' => env('IM_RONGCLOUD_APP_KEY', ''),
        'app_secret' => env('IM_RONGCLOUD_APP_SECRET', ''),
    ],
];
