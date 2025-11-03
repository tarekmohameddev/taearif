<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\NumberHelper;
use Illuminate\Http\Request;

class ConvertArabicNumerals
{
    /**
     * Handle an incoming request.
     *
     * Convert all Arabic-Indic and Persian numerals to Western numerals
     * in all request inputs (GET, POST, JSON, etc.)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle JSON requests separately
        if ($request->isJson()) {
            $this->convertJsonData($request);
            return $next($request);
        }

        // Convert query parameters for GET requests
        if ($request->isMethod('get')) {
            $this->convertRequestData($request, 'query');
        }

        // Convert form data for POST/PUT/PATCH
        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            $this->convertRequestData($request, 'request');
        }

        return $next($request);
    }

    /**
     * Convert request data (query or request parameters)
     *
     * @param Request $request
     * @param string $type
     * @return void
     */
    protected function convertRequestData(Request $request, string $type): void
    {
        $data = $request->{$type}->all();

        if (!empty($data)) {
            $converted = NumberHelper::convertArrayToWestern($data);
            $request->{$type}->replace($converted);
        }
    }

    /**
     * Convert JSON request data
     *
     * @param Request $request
     * @return void
     */
    protected function convertJsonData(Request $request): void
    {
        $data = $request->all();

        if (!empty($data)) {
            $converted = NumberHelper::convertArrayToWestern($data);
            // Replace all input data with converted values
            $request->replace($converted);
        }
    }
}

