<?php

use Yansongda\Pay\Pay;

$wechatPublicCertSerial = (string) env('PAY_WECHAT_PUBLIC_CERT_SERIAL', '');
$wechatPublicCertPath = (string) env('PAY_WECHAT_PUBLIC_CERT_PATH', '');

return [
    'defaults' => [
        'wechat_scene' => env('PAY_WECHAT_DEFAULT_SCENE', 'app'),
        'alipay_scene' => env('PAY_ALIPAY_DEFAULT_SCENE', 'app'),
    ],

    'wechat' => [
        'default' => [
            'mch_id' => env('PAY_WECHAT_MCH_ID', ''),
            'mch_secret_key_v2' => env('PAY_WECHAT_MCH_SECRET_KEY_V2', ''),
            'mch_secret_key' => env('PAY_WECHAT_MCH_SECRET_KEY', ''),
            'mch_secret_cert' => env('PAY_WECHAT_MCH_SECRET_CERT', ''),
            'mch_public_cert_path' => env('PAY_WECHAT_MCH_PUBLIC_CERT_PATH', ''),
            'notify_url' => rtrim((string) env('APP_URL', ''), '/').'/rc/payments/notify/wechat',
            'mp_app_id' => env('PAY_WECHAT_MP_APP_ID', ''),
            'mini_app_id' => env('PAY_WECHAT_MINI_APP_ID', ''),
            'app_id' => env('PAY_WECHAT_APP_ID', ''),
            'wechat_public_cert_path' => $wechatPublicCertSerial !== '' && $wechatPublicCertPath !== ''
                ? [$wechatPublicCertSerial => $wechatPublicCertPath]
                : [],
            'mode' => (int) env('PAY_WECHAT_MODE', Pay::MODE_NORMAL),
        ],
    ],

    'alipay' => [
        'default' => [
            'app_id' => env('PAY_ALIPAY_APP_ID', ''),
            'seller_id' => env('PAY_ALIPAY_SELLER_ID', ''),
            'app_secret_cert' => env('PAY_ALIPAY_APP_SECRET_CERT', ''),
            'app_public_cert_path' => env('PAY_ALIPAY_APP_PUBLIC_CERT_PATH', ''),
            'alipay_public_cert_path' => env('PAY_ALIPAY_PUBLIC_CERT_PATH', ''),
            'alipay_root_cert_path' => env('PAY_ALIPAY_ROOT_CERT_PATH', ''),
            'return_url' => env('PAY_ALIPAY_RETURN_URL', ''),
            'notify_url' => rtrim((string) env('APP_URL', ''), '/').'/rc/payments/notify/alipay',
            'mode' => (int) env('PAY_ALIPAY_MODE', Pay::MODE_NORMAL),
        ],
    ],

    'logger' => [
        'enable' => (bool) env('PAY_LOG_ENABLE', true),
        'file' => storage_path('logs/pay.log'),
        'level' => env('PAY_LOG_LEVEL', 'info'),
        'type' => 'daily',
        'max_file' => 30,
    ],

    'http' => [
        'timeout' => (float) env('PAY_HTTP_TIMEOUT', 10),
        'connect_timeout' => (float) env('PAY_HTTP_CONNECT_TIMEOUT', 5),
    ],
];
