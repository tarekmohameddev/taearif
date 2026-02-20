<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Public path patterns (no Sanctum required)
    |--------------------------------------------------------------------------
    | Paths containing any of these substrings are treated as public and
    | will not get operation-level security: [{ "sanctum": [] }].
    */
    'public_path_patterns' => [
        'callback',
        'webhook',
        'oauth2-callback',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource → request body fields (for POST/PUT/PATCH schema inference)
    |--------------------------------------------------------------------------
    | Key: path segment (e.g. first segment like "blogs", "buildings", or "crm").
    | Value: array of [ "propertyName" => [ "type" => "string"|"integer"|"number"|"boolean", "required" => bool ] ].
    | Used when replacing generic "data" array fallback with realistic create/update schemas.
    */
    'resource_fields' => [
        'blogs' => [
            'title' => ['type' => 'string', 'required' => true],
            'body' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
            'featured_image' => ['type' => 'string', 'required' => false],
        ],
        'buildings' => [
            'name' => ['type' => 'string', 'required' => true],
            'address' => ['type' => 'string', 'required' => false],
            'description' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
        ],
        'properties' => [
            'title' => ['type' => 'string', 'required' => true],
            'address' => ['type' => 'string', 'required' => false],
            'description' => ['type' => 'string', 'required' => false],
            'price' => ['type' => 'number', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
        ],
        'contracts' => [
            'title' => ['type' => 'string', 'required' => true],
            'start_date' => ['type' => 'string', 'required' => false],
            'end_date' => ['type' => 'string', 'required' => false],
            'amount' => ['type' => 'number', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
        ],
        'crm' => [
            'name' => ['type' => 'string', 'required' => false],
            'note' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
        ],
        'categories' => [
            'name' => ['type' => 'string', 'required' => true],
            'slug' => ['type' => 'string', 'required' => false],
            'description' => ['type' => 'string', 'required' => false],
        ],
        'projects' => [
            'name' => ['type' => 'string', 'required' => true],
            'description' => ['type' => 'string', 'required' => false],
            'status' => ['type' => 'string', 'required' => false],
        ],
        'apps' => [
            'app_id' => ['type' => 'string', 'required' => false],
            'config' => ['type' => 'object', 'required' => false],
        ],
        'pixels' => [
            'name' => ['type' => 'string', 'required' => true],
            'platform' => ['type' => 'string', 'required' => false],
            'pixel_id' => ['type' => 'string', 'required' => false],
        ],
        'onboarding' => [
            'step' => ['type' => 'string', 'required' => false],
            'completed' => ['type' => 'boolean', 'required' => false],
        ],
        'auth' => [
            'email' => ['type' => 'string', 'required' => false],
            'password' => ['type' => 'string', 'required' => false],
        ],
        'payment' => [
            'amount' => ['type' => 'number', 'required' => false],
            'currency' => ['type' => 'string', 'required' => false],
        ],
        'permissions' => [
            'name' => ['type' => 'string', 'required' => true],
            'description' => ['type' => 'string', 'required' => false],
            'name_ar' => ['type' => 'string', 'required' => false],
            'name_en' => ['type' => 'string', 'required' => false],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default fields when resource is not in resource_fields
    |--------------------------------------------------------------------------
    */
    'default_fields' => [
        'name' => ['type' => 'string', 'required' => false],
        'title' => ['type' => 'string', 'required' => false],
        'description' => ['type' => 'string', 'required' => false],
        'status' => ['type' => 'string', 'required' => false],
        'amount' => ['type' => 'number', 'required' => false],
    ],
];
