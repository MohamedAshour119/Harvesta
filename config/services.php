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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'tap' => [
        'secret_key' => env('TAP_ENVIRONMENT') === 'live' 
            ? env('TAP_SECRET_KEY_LIVE') 
            : env('TAP_SECRET_KEY_TEST'),
        'publishable_key' => env('TAP_ENVIRONMENT') === 'live' 
            ? env('TAP_PUBLISHABLE_KEY_LIVE') 
            : env('TAP_PUBLISHABLE_KEY_TEST'),
        'environment' => env('TAP_ENVIRONMENT', 'test'),
        'webhook_secret' => env('TAP_WEBHOOK_SECRET'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'webhook_tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
    ],

    'recombee' => [
        'database_id' => env('RECOMBEE_DATABASE_ID'),
        'private_token' => env('RECOMBEE_PRIVATE_TOKEN'),
        'public_token' => env('RECOMBEE_PUBLIC_TOKEN'),
        'region' => env('RECOMBEE_REGION', 'eu-west'),
    ],

    'jina' => [
        'api_key' => env('JINA_API_KEY'),
        'model' => env('JINA_EMBEDDING_MODEL', 'jina-embeddings-v3'),
        'clip_model' => env('JINA_CLIP_MODEL', 'jina-clip-v2'),
        'api_url' => env('JINA_API_URL', 'https://api.jina.ai/v1/embeddings'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'allam-2-7b'),
        'temperature' => (float) env('GROQ_TEMPERATURE', 0.4),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 1024),
        'admin_model' => env('GROQ_ADMIN_MODEL', 'allam-2-7b'),
        'admin_temperature' => (float) env('GROQ_ADMIN_TEMPERATURE', 0.3),
    ],

];
