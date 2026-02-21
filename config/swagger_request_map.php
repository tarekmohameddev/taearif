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

];

$extended = file_exists(__DIR__ . '/swagger_request_map_extended.php')
    ? require __DIR__ . '/swagger_request_map_extended.php'
    : [];

return array_merge($extended, $base);
