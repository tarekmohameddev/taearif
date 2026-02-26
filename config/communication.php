<?php

return [
    'enabled' => env('COMMUNICATION_ENABLED', false),
    'automation' => [
        'enabled' => env('COMMUNICATION_AUTOMATION_ENABLED', false),
        'queue' => env('COMMUNICATION_AUTOMATION_QUEUE', 'communication'),
        'suggest_max_chars' => (int) env('COMMUNICATION_AUTOMATION_SUGGEST_MAX_CHARS', 300),
        'ai_rate_limit_attempts' => (int) env('COMMUNICATION_AI_RATE_LIMIT_ATTEMPTS', 5),
        'ai_rate_limit_window_seconds' => (int) env('COMMUNICATION_AI_RATE_LIMIT_WINDOW_SECONDS', 60),
    ],
    'ai' => [
        'enabled' => env('COMMUNICATION_AI_ENABLED', false),
        'timeout_seconds' => (int) env('COMMUNICATION_AI_TIMEOUT_SECONDS', 15),
    ],
    'sms' => [
        'enabled' => env('COMMUNICATION_SMS_ENABLED', false),
        'provider' => env('COMMUNICATION_SMS_PROVIDER', null),
        'queue' => env('COMMUNICATION_SMS_QUEUE', 'communication'),
        'default_country_code' => env('COMMUNICATION_SMS_DEFAULT_COUNTRY_CODE', '966'),
        'webhook_secret' => env('COMMUNICATION_SMS_WEBHOOK_SECRET', ''),
        'batch_size' => (int) env('COMMUNICATION_SMS_BATCH_SIZE', 200),
        'max_manual_recipients' => (int) env('COMMUNICATION_SMS_MAX_MANUAL_RECIPIENTS', 5000),
    ],

    'email' => [
        'enabled' => env('COMMUNICATION_EMAIL_ENABLED', false),
        'provider' => env('COMMUNICATION_EMAIL_PROVIDER', null),
        'queue' => env('COMMUNICATION_EMAIL_QUEUE', 'communication'),
        'webhook_secret' => env('COMMUNICATION_EMAIL_WEBHOOK_SECRET', ''),
        'batch_size' => (int) env('COMMUNICATION_EMAIL_BATCH_SIZE', 100),
        'max_manual_recipients' => (int) env('COMMUNICATION_EMAIL_MAX_MANUAL_RECIPIENTS', 5000),
        'default_from_email' => env('MAIL_FROM_ADDRESS'),
        'default_from_name' => env('MAIL_FROM_NAME'),
    ],

    'whatsapp' => [
        'enabled' => env('COMMUNICATION_WHATSAPP_ENABLED', false),
        'provider' => env('COMMUNICATION_WHATSAPP_PROVIDER', null),
        'webhook_verify_token' => env('COMMUNICATION_WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
        'app_secret' => env('COMMUNICATION_WHATSAPP_APP_SECRET', ''),
        'evolution' => [
            'base_url' => env('COMMUNICATION_WHATSAPP_EVOLUTION_BASE_URL', null),
            'api_key' => env('COMMUNICATION_WHATSAPP_EVOLUTION_API_KEY', null),
            'webhook_secret' => env('COMMUNICATION_WHATSAPP_EVOLUTION_WEBHOOK_SECRET', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Evolution API instance → WhatsApp number mapping
    |--------------------------------------------------------------------------
    | Used to resolve tenant owner for Evolution webhook: instance name is
    | mapped to the WhatsApp business number stored in whatsapp_users.number.
    | If null, inbound persistence from Evolution webhook is skipped.
    */
    'evolution_instance_number' => env('COMMUNICATION_EVOLUTION_INSTANCE_NUMBER', null),

    /*
    |--------------------------------------------------------------------------
    | Phase 6: Communication Reliability & Operations
    |--------------------------------------------------------------------------
    */
    'reliability' => [
        'enabled' => env('COMMUNICATION_RELIABILITY_ENABLED', false),
        'queue' => env('COMMUNICATION_RELIABILITY_QUEUE', 'communication'),
        'retry' => [
            'max_attempts' => (int) env('COMMUNICATION_RELIABILITY_RETRY_MAX_ATTEMPTS', 3),
            'initial_backoff_seconds' => (int) env('COMMUNICATION_RELIABILITY_RETRY_INITIAL_BACKOFF', 30),
            'max_backoff_seconds' => (int) env('COMMUNICATION_RELIABILITY_RETRY_MAX_BACKOFF', 600),
        ],
        'reconcile' => [
            'enabled' => env('COMMUNICATION_RELIABILITY_RECONCILE_ENABLED', true),
            'interval_minutes' => (int) env('COMMUNICATION_RELIABILITY_RECONCILE_INTERVAL', 15),
            'lookback_days' => (int) env('COMMUNICATION_RELIABILITY_RECONCILE_LOOKBACK', 30),
        ],
        'retention_days' => (int) env('COMMUNICATION_RELIABILITY_RETENTION_DAYS', 30),
        'whatsapp_polling_enabled' => env('COMMUNICATION_RELIABILITY_WHATSAPP_POLLING', true),
        'sms_polling_enabled' => env('COMMUNICATION_RELIABILITY_SMS_POLLING', true),
    ],
];
