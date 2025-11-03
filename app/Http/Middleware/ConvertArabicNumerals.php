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
        // Convert all input data
        if ($request->isMethod('get')) {
            $this->convertRequestData($request, 'query');
        }

        if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) {
            $this->convertRequestData($request, 'request');
        }

        // Also handle JSON requests
        if ($request->isJson()) {
            $this->convertJsonData($request);
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
        $data = $request->json()->all();

        if (!empty($data)) {
            $converted = NumberHelper::convertArrayToWestern($data);
            $request->json()->replace($converted);
        }
    }
}

