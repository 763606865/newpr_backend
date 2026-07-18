<?php

return [
    'default' => env('AI_DRIVER', 'custom'),
    'resume_parse_driver' => env('AI_RESUME_PARSE_DRIVER', env('AI_DRIVER', 'custom')),

    'drivers' => [
        'custom' => [
            'base_url' => env('AI_CUSTOM_BASE_URL', ''),
            'api_key' => env('AI_CUSTOM_API_KEY', ''),
            'model' => env('AI_CUSTOM_MODEL', ''),
            'chat_path' => env('AI_CUSTOM_CHAT_PATH', '/api/chat/completions'),
            'resume_parse_path' => env('AI_CUSTOM_RESUME_PARSE_PATH', '/api/v1/parse'),
            'timeout' => env('AI_CUSTOM_TIMEOUT'),
        ],

        'bailian' => [
            'base_url' => env('AI_BAILIAN_BASE_URL', 'https://dashscope.aliyuncs.com/compatible-mode/v1'),
            'api_key' => env('AI_BAILIAN_API_KEY', ''),
            'model' => env('AI_BAILIAN_MODEL', 'qwen-plus'),
            'chat_path' => env('AI_BAILIAN_CHAT_PATH', '/chat/completions'),
            'timeout' => env('AI_BAILIAN_TIMEOUT'),
        ],

        'openai' => [
            'base_url' => env('AI_OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('AI_OPENAI_API_KEY', ''),
            'model' => env('AI_OPENAI_MODEL', 'gpt-4.1-mini'),
            'chat_path' => env('AI_OPENAI_CHAT_PATH', '/chat/completions'),
            'timeout' => env('AI_OPENAI_TIMEOUT'),
        ],
    ],
];
