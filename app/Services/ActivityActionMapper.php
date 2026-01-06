<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class ActivityActionMapper
{
    /**
     * Map HTTP method and route to human-readable action key
     */
    public static function getActionKey(Request $request): string
    {
        $method = $request->method();
        $route = $request->route();
        
        if (!$route) {
            return 'activity.unknown';
        }
        
        $routeName = $route->getName();
        $uri = $route->uri();
        
        // Try to get action from route name first (most reliable)
        if ($routeName) {
            $actionKey = self::mapRouteNameToAction($routeName, $method);
            if ($actionKey) {
                return $actionKey;
            }
        }
        
        // Fallback to URI pattern matching
        return self::mapUriToAction($uri, $method);
    }
    
    /**
     * Map route name to action key
     */
    private static function mapRouteNameToAction(string $routeName, string $method): ?string
    {
        // Extract resource and action from route name
        // Example: "api.customers.store" -> "customers" + "store" + POST
        $parts = explode('.', $routeName);
        
        if (count($parts) < 2) {
            return null;
        }
        
        // Get the last two parts (resource and action)
        $resource = $parts[count($parts) - 2] ?? null;
        $action = $parts[count($parts) - 1] ?? null;
        
        if (!$resource || !$action) {
            return null;
        }
        
        return self::buildActionKey($resource, $action, $method);
    }
    
    /**
     * Map URI pattern to action key
     */
    private static function mapUriToAction(string $uri, string $method): string
    {
        // Remove route parameters for pattern matching
        $uri = preg_replace('/\{[^}]+\}/', '', $uri);
        $uri = trim($uri, '/');
        $segments = explode('/', $uri);
        
        // Remove 'api', 'v1' prefixes
        $segments = array_filter($segments, function($seg) {
            return !in_array($seg, ['api', 'v1']);
        });
        $segments = array_values($segments);
        
        if (empty($segments)) {
            return 'activity.unknown';
        }
        
        $resource = $segments[0];
        $action = $segments[1] ?? null;
        
        // Check if there's a special action in the URI
        if (count($segments) > 1) {
            $lastSegment = $segments[count($segments) - 1];
            if (self::isSpecialAction($lastSegment)) {
                $action = $lastSegment;
            }
        }
        
        return self::buildActionKey($resource, $action, $method);
    }
    
    /**
     * Build action key from resource, action, and method
     */
    private static function buildActionKey(string $resource, ?string $action, string $method): string
    {
        // Normalize resource name (remove plural, handle special cases)
        $resource = self::normalizeResource($resource);
        
        // Map HTTP method + action to action type
        $actionType = self::mapMethodToActionType($method, $action);
        
        return "activity.{$actionType}.{$resource}";
    }
    
    /**
     * Normalize resource name
     */
    private static function normalizeResource(string $resource): string
    {
        // Remove plural
        $resource = rtrim($resource, 's');
        
        // Handle special cases and mappings
        $mapping = [
            // Customers
            'customer' => 'customer',
            'customers' => 'customer',
            'api_customers' => 'customer',
            'api_customer' => 'customer',
            
            // Properties
            'property' => 'property',
            'properties' => 'property',
            
            // Projects
            'project' => 'project',
            'projects' => 'project',
            
            // Roles
            'role' => 'role',
            'roles' => 'role',
            
            // Employees
            'employee' => 'employee',
            'employees' => 'employee',
            
            // CRM Cards
            'card' => 'card',
            'cards' => 'card',
            'crm_cards' => 'card',
            'crm_card' => 'card',
            
            // CRM Requests
            'request' => 'request',
            'requests' => 'request',
            'crm_requests' => 'request',
            'crm_request' => 'request',
            
            // CRM Stages
            'stage' => 'stage',
            'stages' => 'stage',
            'users_api_customers_stages' => 'stage',
            
            // CRM Procedures
            'procedure' => 'procedure',
            'procedures' => 'procedure',
            'users_api_customers_procedures' => 'procedure',
            
            // CRM Priorities
            'priority' => 'priority',
            'priorities' => 'priority',
            'users_api_customers_priorities' => 'priority',
            
            // CRM Types
            'type' => 'type',
            'types' => 'type',
            'users_api_customers_types' => 'type',
            
            // Buildings
            'building' => 'building',
            'buildings' => 'building',
            
            // Reservations
            'reservation' => 'reservation',
            'reservations' => 'reservation',
            
            // Marketing
            'marketing_channel' => 'marketing_channel',
            'marketing_channels' => 'marketing_channel',
            'channel' => 'marketing_channel',
            'channels' => 'marketing_channel',
            
            // Appointments
            'appointment' => 'appointment',
            'appointments' => 'appointment',
            'customer_appointment' => 'appointment',
            'customer_appointments' => 'appointment',
            
            // Reminders
            'reminder' => 'reminder',
            'reminders' => 'reminder',
            'customer_reminder' => 'reminder',
            'customer_reminders' => 'reminder',
            
            // Domains
            'domain' => 'domain',
            'domains' => 'domain',
            
            // WhatsApp
            'whatsapp' => 'whatsapp',
            'whatsapp_addon' => 'whatsapp_addon',
            
            // Employee Addons
            'employee_addon' => 'employee_addon',
            'employee_addons' => 'employee_addon',
            
            // Content
            'content' => 'content',
            'general' => 'content',
            'footer' => 'content',
            'banner' => 'content',
            'about' => 'content',
            'menu' => 'content',
            
            // Settings
            'settings' => 'settings',
            'setting' => 'settings',
            'theme' => 'settings',
            'payment' => 'settings',
            
            // Categories
            'category' => 'category',
            'categories' => 'category',
            
            // Cities & Districts
            'city' => 'city',
            'cities' => 'city',
            'district' => 'district',
            'districts' => 'district',
            
            // Apps
            'app' => 'app',
            'apps' => 'app',
            
            // Embeddings & Chat
            'embedding' => 'embedding',
            'embeddings' => 'embedding',
            'chat' => 'chat',
            
            // Credits
            'credit' => 'credit',
            'credits' => 'credit',
            'transaction' => 'transaction',
            'transactions' => 'transaction',
            
            // Analytics
            'analytics' => 'analytics',
            'dashboard' => 'dashboard',
            
            // Filters & Templates
            'filter' => 'filter',
            'filters' => 'filter',
            'template' => 'template',
            'templates' => 'template',
            
            // Facades & FAQs
            'facade' => 'facade',
            'facades' => 'facade',
            'faq' => 'faq',
            'faqs' => 'faq',
            
            // Property Requests
            'property_request' => 'property_request',
            'property_requests' => 'property_request',
            
            // Steps
            'step' => 'step',
            'steps' => 'step',
        ];
        
        return $mapping[$resource] ?? $resource;
    }
    
    /**
     * Check if a segment is a special action
     */
    private static function isSpecialAction(string $segment): bool
    {
        $specialActions = [
            'toggle-featured', 'toggle-featured', 'toggle_status', 'toggle-status',
            'duplicate', 'reorder', 'reorder-featured', 'reorder_featured',
            'bulk-import', 'bulk_import', 'bulk-action', 'bulk_action',
            'export', 'download', 'change-stage', 'change_stage',
            'change-priority', 'change_priority', 'change-type', 'change_type',
            'change-procedure', 'change_procedure', 'move', 'install',
            'uninstall', 'link', 'unlink', 'accept', 'reject',
            'upload', 'upload-image', 'upload-deed-image', 'upload-building-image',
            'set-active', 'set-primary', 'request-ssl', 'ssl-status',
            'toggle-visibility', 'toggle-show-properties', 'verify',
            'sync-verified', 'send-message', 'send-whatsapp-to-customer',
            'with-inquiries', 'details', 'stats', 'statistics', 'usage',
            'plans', 'balance', 'purchase', 'analytics', 'search',
            'filters', 'defaults', 'reset', 'bulk', 'complete',
            'available-roles', 'available-permissions', 'available-units',
            'progress', 'me', 'logout', 'login', 'register',
        ];
        
        return in_array($segment, $specialActions) || in_array(str_replace('-', '_', $segment), $specialActions);
    }
    
    /**
     * Map HTTP method and action to action type
     */
    private static function mapMethodToActionType(string $method, ?string $action): string
    {
        // Handle specific actions first
        if ($action) {
            $normalizedAction = str_replace('-', '_', strtolower($action));
            
            $actionMap = [
                'toggle-featured' => 'toggle_featured',
                'toggle_featured' => 'toggle_featured',
                'toggle-status' => 'toggle_status',
                'toggle_status' => 'toggle_status',
                'toggle-visibility' => 'toggle_visibility',
                'toggle_visibility' => 'toggle_visibility',
                'toggle-show-properties' => 'toggle_show_properties',
                'toggle_show_properties' => 'toggle_show_properties',
                'duplicate' => 'duplicate',
                'reorder' => 'reorder',
                'reorder-featured' => 'reorder_featured',
                'reorder_featured' => 'reorder_featured',
                'bulk-import' => 'bulk_import',
                'bulk_import' => 'bulk_import',
                'bulk-action' => 'bulk_action',
                'bulk_action' => 'bulk_action',
                'bulk' => 'bulk_action',
                'export' => 'export',
                'download' => 'download',
                'change-stage' => 'change_stage',
                'change_stage' => 'change_stage',
                'change-priority' => 'change_priority',
                'change_priority' => 'change_priority',
                'change-type' => 'change_type',
                'change_type' => 'change_type',
                'change-procedure' => 'change_procedure',
                'change_procedure' => 'change_procedure',
                'move' => 'move',
                'install' => 'install',
                'uninstall' => 'uninstall',
                'link' => 'link',
                'unlink' => 'unlink',
                'accept' => 'accept',
                'reject' => 'reject',
                'upload' => 'upload',
                'upload-image' => 'upload',
                'upload_deed_image' => 'upload',
                'upload-building-image' => 'upload',
                'set-active' => 'set_active',
                'set_active' => 'set_active',
                'set-primary' => 'set_primary',
                'set_primary' => 'set_primary',
                'request-ssl' => 'request_ssl',
                'request_ssl' => 'request_ssl',
                'ssl-status' => 'ssl_status',
                'ssl_status' => 'ssl_status',
                'verify' => 'verify',
                'sync-verified' => 'sync_verified',
                'sync_verified' => 'sync_verified',
                'send-message' => 'send_message',
                'send_message' => 'send_message',
                'send-whatsapp-to-customer' => 'send_message',
                'send_whatsapp_to_customer' => 'send_message',
                'details' => 'view',
                'stats' => 'view',
                'statistics' => 'view',
                'usage' => 'view',
                'plans' => 'view',
                'balance' => 'view',
                'analytics' => 'view',
                'search' => 'view',
                'filters' => 'view',
                'defaults' => 'view',
                'reset' => 'reset',
                'complete' => 'complete',
                'available-roles' => 'view',
                'available-permissions' => 'view',
                'available-units' => 'view',
                'progress' => 'view',
                'me' => 'view',
                'logout' => 'logout',
                'login' => 'login',
                'register' => 'register',
                'purchase' => 'purchase',
            ];
            
            if (isset($actionMap[$normalizedAction])) {
                return $actionMap[$normalizedAction];
            }
        }
        
        // Map HTTP methods
        $methodMap = [
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
            'GET' => 'view',
        ];
        
        return $methodMap[$method] ?? 'unknown';
    }
    
    /**
     * Get translated action message
     */
    public static function getTranslatedAction(Request $request, ?string $locale = null): string
    {
        $actionKey = self::getActionKey($request);
        
        if ($locale) {
            return Lang::get("activity_log.{$actionKey}", [], $locale) ?: $actionKey;
        }
        
        return trans("activity_log.{$actionKey}") ?: $actionKey;
    }
    
    /**
     * Get action key for direct use (without translation)
     */
    public static function getActionKeyOnly(Request $request): string
    {
        return self::getActionKey($request);
    }
    
    /**
     * Translate an action key to a human-readable label
     */
    public static function translateActionKey(string $actionKey, ?string $locale = null): string
    {
        // Handle old format (e.g., "POST /api/v1/customers")
        if (strpos($actionKey, ' ') !== false || strpos($actionKey, '/') !== false) {
            // Try to parse old format
            if (preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s+(.+)$/', $actionKey, $matches)) {
                $method = $matches[1];
                $uri = $matches[2];
                // Try to convert to new format
                $uri = preg_replace('/\{[^}]+\}/', '', $uri);
                $uri = trim($uri, '/');
                $segments = explode('/', $uri);
                $segments = array_filter($segments, function($seg) {
                    return !in_array($seg, ['api', 'v1']);
                });
                $segments = array_values($segments);
                
                if (!empty($segments)) {
                    $resource = $segments[0];
                    $action = $segments[1] ?? null;
                    $resource = self::normalizeResource($resource);
                    $actionType = self::mapMethodToActionType($method, $action);
                    $actionKey = "activity.{$actionType}.{$resource}";
                }
            }
        }
        
        if ($locale) {
            return Lang::get("activity_log.{$actionKey}", [], $locale) ?: $actionKey;
        }
        
        return trans("activity_log.{$actionKey}") ?: $actionKey;
    }
}

