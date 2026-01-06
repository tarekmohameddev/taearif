<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Api\EmployeeActivityLog;
use App\Services\ActivityActionMapper;
use Illuminate\Support\Facades\Log;

class LogEmployeeRequestActivity
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Only log for employees, not tenants
        if (!$user || !$user->isEmployee()) {
            return $next($request);
        }

        $response = $next($request);

        // Determine if this request should be logged
        if ($this->shouldLogRequest($request)) {
            try {
                $this->logEmployeeActivity($request, $response);
            } catch (\Exception $e) {
                // Don't let logging errors break the response
                Log::error('Failed to log employee activity', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'method' => $request->method(),
                    'uri' => $request->getRequestUri(),
                ]);
            }
        }

        return $response;
    }

    private function shouldLogRequest(Request $request): bool
    {
        $method = $request->method();

        // Always log writes
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // For GET requests, only log high-signal reads
        if ($method === 'GET') {
            return $this->isHighSignalRead($request);
        }

        return false;
    }

    private function isHighSignalRead(Request $request): bool
    {
        $uri = $request->getRequestUri();

        // Check for {id} parameter in route
        if ($request->route() && collect($request->route()->parameters())->has('id')) {
            return true;
        }

        // Check for specific keywords indicating detailed views or exports
        $highSignalPatterns = ['details', 'export', 'download', 'show'];
        foreach ($highSignalPatterns as $pattern) {
            if (str_contains(strtolower($uri), $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function logEmployeeActivity(Request $request, $response): void
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Get human-readable action key
        $actionKey = ActivityActionMapper::getActionKeyOnly($request);

        // Get target information
        $targetInfo = $this->extractTargetInfo($request);

        // Sanitize request data
        $requestData = $this->sanitizeRequestData($request);

        // Add response status
        $requestData['response_status'] = $response->getStatusCode();

        EmployeeActivityLog::create([
            'user_id' => $tenantId,
            'actor_type' => 'employee',
            'actor_id' => $user->id,
            'action' => $actionKey,
            'target_type' => $targetInfo['type'],
            'target_id' => $targetInfo['id'],
            'new_values' => $requestData,
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
        ]);
    }

    private function extractTargetInfo(Request $request): array
    {
        $route = $request->route();

        if (!$route) {
            return ['type' => null, 'id' => null];
        }

        $parameters = $route->parameters();

        // Look for common ID parameters
        $idParams = ['id', 'customer_id', 'card_id', 'request_id', 'stage_id', 'procedure_id', 'priority_id', 'type_id'];

        foreach ($idParams as $param) {
            if (isset($parameters[$param])) {
                return [
                    'type' => $this->mapParamToTargetType($param),
                    'id' => $parameters[$param]
                ];
            }
        }

        // If no specific ID found, use generic route URI
        return [
            'type' => $route->uri(),
            'id' => null
        ];
    }

    private function mapParamToTargetType(string $param): string
    {
        $mapping = [
            'id' => 'resource',
            'customer_id' => 'api_customers',
            'card_id' => 'crm_cards',
            'request_id' => 'crm_requests',
            'stage_id' => 'users_api_customers_stages',
            'procedure_id' => 'users_api_customers_procedures',
            'priority_id' => 'users_api_customers_priorities',
            'type_id' => 'users_api_customers_types',
        ];

        return $mapping[$param] ?? $param;
    }

    private function sanitizeRequestData(Request $request): array
    {
        $data = [];

        // Add route parameters
        if ($request->route()) {
            $data['route_params'] = $request->route()->parameters();
        }

        // Add query parameters (excluding sensitive ones)
        $queryParams = $request->query();
        unset($queryParams['password'], $queryParams['token'], $queryParams['api_key']);
        if (!empty($queryParams)) {
            $data['query_params'] = $queryParams;
        }

        // Add body data for writes (sanitized)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            $bodyData = $this->sanitizeBodyData($request->all());
            if (!empty($bodyData)) {
                $data['body'] = $bodyData;
            }
        }

        return $data;
    }

    private function sanitizeBodyData(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'api_key', 'secret',
            'credit_card', 'card_number', 'cvv', 'pin'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        // Remove uploaded files metadata to avoid storing file contents
        foreach ($data as $key => $value) {
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $data[$key] = [
                    'filename' => $value->getClientOriginalName(),
                    'size' => $value->getSize(),
                    'mime_type' => $value->getMimeType(),
                ];
            }
        }

        return $data;
    }
}
