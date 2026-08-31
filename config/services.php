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
    'posthog' => [
        'key' => env('POSTHOG_API_KEY'),
        'host' => env('POSTHOG_HOST', 'https://us.i.posthog.com'),
        'personal_key' => env('POSTHOG_PERSONAL_API_KEY'),
        'enabled' => env('POSTHOG_ENABLED', false),
    ],
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'recaptcha' => [
        'secret' => env('RECAPTCHA_SECRET'),
        'api_enabled' => env('RECAPTCHA_API_ENABLED', true),
    ],


    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'paytm-wallet' => [
        'env' => '',
        'merchant_id' => '',
        'merchant_key' => '',
        'merchant_website' => '',
        'channel' => '',
        'industry_type' => '',
      ],

      'stripe' => [
        'key' => '',
        'secret' => '',
    ],
    // 'google' => [
    //     'analytics_property_id' => env('GOOGLE_ANALYTICS_PROPERTY_ID'),
    //     'analytics_view_id' => env('GOOGLE_ANALYTICS_VIEW_ID'),
    //     'analytics_service_account_credentials_json' => storage_path('app/google/service-account.json'),
    // ],

    'google' => [
        'analytics_property_id' => env('GOOGLE_ANALYTICS_PROPERTY_ID'),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URL'),
    ],

    // Meta (Facebook) WhatsApp Cloud API
    'meta' => [
        // Meta App credentials
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),

        // System User Access Token (App Token) for debug_token and API calls
        'app_token' => env('META_APP_TOKEN'),

        // API version, e.g. v20.0
        'api_version' => env('META_API_VERSION', 'v20.0'),

        // Optional default Business Account ID (WABA) if not taken from DB settings
        'business_account_id' => env('META_BUSINESS_ACCOUNT_ID'),

        // Embedded Signup configuration
        'embedded_signup_config_id' => env('META_EMBEDDED_SIGNUP_CONFIG_ID'),

        // Redirect URI configured in Meta App (must match exactly)
        'redirect_uri' => env('META_REDIRECT_URI'),
    ],

    'vercel' => [
        'token' => env('VERCEL_TOKEN'),
        'team_id' => env('VERCEL_TEAM_ID'),
        'project_id' => env('VERCEL_PROJECT_ID'),
        'base_url' => env('VERCEL_API_BASE', 'https://api.vercel.com'),
        'nameservers' => [
            'ns1.vercel-dns.com',
            'ns2.vercel-dns.com',
        ],
        'max_domains_per_tenant' => (int) env('VERCEL_MAX_DOMAINS_PER_TENANT', 5),
        'max_project_domains' => (int) env('VERCEL_MAX_PROJECT_DOMAINS', 50),
        'platform_domain_count' => (int) env('VERCEL_PLATFORM_DOMAIN_COUNT', 4),
        'check_nameservers' => filter_var(env('VERCEL_CHECK_NAMESERVERS', true), FILTER_VALIDATE_BOOLEAN),
        'auto_attach_custom_domain' => filter_var(env('VERCEL_AUTO_ATTACH_CUSTOM_DOMAIN', true), FILTER_VALIDATE_BOOLEAN),
    ],

];
