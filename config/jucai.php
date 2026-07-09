<?php

return [
    'default' => [
        'host' => env('JUCAI_HOST', 'https://util.jxujob.com:10003'),
        'accesskey' => env('JUCAI_ACCESSKEY', 'c6ff01395225e20fc408bfc7353c8fd1'),
    ],
    'dt' => [
        'host' => env('JUCAI_DT_HOST', 'https://util.jxujob.com:10003'),
        'app_key' => env('JUCAI_DT_APP_KEY', 'c6ff01395225e20fc408bfc7353c8fd1'),
        'app_secret' => env('JUCAI_DT_APP_SECRET', 'c6ff01395225e20fc408bfc7353c8fd1'),
        'cache_store' => env('JUCAI_DT_CACHE_STORE'),
    ],
];
