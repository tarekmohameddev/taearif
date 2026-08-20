<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'gpt-4-turbo'),
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-5-mini'),
    'fast_model' => env('OPENAI_FAST_MODEL', 'gpt-5-nano'),
    'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 3000),
    'temperature' => (float) env('OPENAI_TEMPERATURE', 0.3),

    // Safety limits
    'rate_limit_per_minute' => (int) env('OPENAI_RATE_LIMIT_PER_MIN', 60),
    'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 30),
];



