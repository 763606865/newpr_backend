<?php

return [
    'base_uri' => env('AMAP_BASE_URI', 'https://restapi.amap.com'),

    /**
     * Web 服务 Key，用于服务端 REST API（地理编码、逆地理编码等）。
     */
    'key' => env('AMAP_KEY', ''),

    /**
     * Web 端 JS API Key，用于前端地图组件。
     */
    'web_key' => env('AMAP_WEB_API_KEY', ''),

    'timeout' => (int) env('AMAP_TIMEOUT', 5),
];
