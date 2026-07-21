<?php

return [
    'default' => env('IM_DRIVER', 'custom'),

    'conversation' => [
        'initial_messages' => [
            'job_seeker' => '希望和你聊聊这个职位，是否有时间呢？',
            'recruiter' => 'Hi，看了您的过往经历感觉您比较符合我们的职位要求，方便聊一聊吗?',
        ],
    ],

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
