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

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => env('OPENAI_TIMEOUT', 30),
        'retries' => env('OPENAI_RETRIES', 2),
        'temperature' => env('OPENAI_TEMPERATURE', 0.2),
        'max_document_chars' => env('OPENAI_MAX_DOCUMENT_CHARS', 12000),

        /*
        |--------------------------------------------------------------------------
        | Per-task models
        |--------------------------------------------------------------------------
        | A faster/smaller model for quick answer evaluation and stronger
        | models for quiz generation and assessment recommendations.
        */
        'models' => [
            'default' => env('OPENAI_MODEL_DEFAULT', 'gpt-4o-mini'),
            'assessment' => env('OPENAI_MODEL_ASSESSMENT', 'gpt-4o-mini'),
            'quiz' => env('OPENAI_MODEL_QUIZ', 'gpt-4o-mini'),
            'answer' => env('OPENAI_MODEL_ANSWER', 'gpt-4o-mini'),
        ],

        'assessment_max_tokens' => env('OPENAI_ASSESSMENT_MAX_TOKENS', 1200),
        'quiz_max_tokens' => env('OPENAI_QUIZ_MAX_TOKENS', 2500),
        'quiz_json_retries' => env('OPENAI_QUIZ_JSON_RETRIES', 3),
        'answer_json_retries' => env('OPENAI_ANSWER_JSON_RETRIES', 2),
        'cache_ttl' => env('OPENAI_QUIZ_CACHE_TTL', 604800),
    ],

];
