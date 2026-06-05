<?php

declare(strict_types=1);

return [
    'default' => env('ELASTIC_CONNECTION', 'default'),
    'connections' => [
        'default' => [
            'hosts' => [
                env('ELASTIC_HOST', env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200')),
            ],
            'basicAuthentication' => [
                env('ELASTIC_USERNAME', env('ELASTICSEARCH_USER')),
                env('ELASTIC_PASSWORD', env('ELASTICSEARCH_PASS')),
            ],
        ],
    ],
];
