<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'ollama' => [
        'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3'),
        'timeout' => env('OLLAMA_TIMEOUT', 90),
        'retries' => env('OLLAMA_RETRIES', 2),
        'temperature' => env('OLLAMA_TEMPERATURE', 0.2),
        'max_document_chars' => env('OLLAMA_MAX_DOCUMENT_CHARS', 12000),
        'quiz_json_retries' => env('OLLAMA_QUIZ_JSON_RETRIES', 3),
        'quiz_num_predict' => env('OLLAMA_QUIZ_NUM_PREDICT', 2500),
        'answer_json_retries' => env('OLLAMA_ANSWER_JSON_RETRIES', 2),
        'keep_alive' => env('OLLAMA_KEEP_ALIVE', '30m'),
        'num_ctx' => env('OLLAMA_NUM_CTX', 8192),
        'cache_ttl' => env('OLLAMA_CACHE_TTL', 604800),
        'num_thread' => env('OLLAMA_NUM_THREAD'),
        'num_gpu' => env('OLLAMA_NUM_GPU'),
    ],

];
