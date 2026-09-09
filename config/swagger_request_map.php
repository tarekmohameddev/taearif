<?php

$base = [

    /*
    |--------------------------------------------------------------------------
    | Swagger request body rules (docs only)
    |--------------------------------------------------------------------------
    |
    | Maps controller@method to validation rules used only for OpenAPI docs.
    | Key: 'App\\Http\\Controllers\\...\\Controller@method'
    | Value: class-string with static rulesForDocs(): array, or direct rules array.
    | Used when the controller uses inline $request->validate() so rules cannot
    | be discovered by FormRequest reflection.
    |
    */

    // AffiliateController@register, CustomerController@store/update, ProjectController@store/update
    // removed — these controllers now type-hint a FormRequest; schema is resolved automatically.

    // No request body (action-only POST)
    'App\Http\Controllers\ImpersonationController@stop' => [],

    // Domain onboarding (docs durability; FormRequests also resolve these)
    'App\Http\Controllers\Api\DomainSettingsController@store' => [
        'custom_name' => ['required', 'string', 'max:255'],
        'dns_mode' => ['sometimes', 'required', 'string', 'in:vercel_ns,external_dns'],
    ],
    'App\Http\Controllers\Api\DomainSettingsController@enableWww' => [
        'id' => ['required', 'integer'],
    ],

];

$extended = file_exists(__DIR__ . '/swagger_request_map_extended.php')
    ? require __DIR__ . '/swagger_request_map_extended.php'
    : [];

return array_merge($extended, $base);
