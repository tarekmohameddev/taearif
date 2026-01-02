<?php

return [
    'name' => 'WhatsappAI',
    
    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | The OpenAI model to use for conversation analysis.
    | Recommended: gpt-4o-mini for cost efficiency
    |
    */
    'model' => env('WHATSAPPAI_MODEL', 'gpt-4o-mini'),
    
    /*
    |--------------------------------------------------------------------------
    | Session Timeout
    |--------------------------------------------------------------------------
    |
    | Number of minutes to wait after last message before processing
    | the conversation with AI. This allows customers to complete their
    | thought before analysis.
    |
    */
    'session_timeout' => (int) env('WHATSAPPAI_TIMEOUT', 5),
    
    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue to use for processing conversations.
    | Use a dedicated queue to manage AI processing separately.
    |
    */
    'queue' => env('WHATSAPPAI_QUEUE', 'default'),
    
    /*
    |--------------------------------------------------------------------------
    | Webhook Verify Token
    |--------------------------------------------------------------------------
    |
    | Token used by Meta to verify your webhook endpoint.
    | Set this in your Meta App Dashboard and .env file.
    |
    */
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'your-verify-token-here'),
];
