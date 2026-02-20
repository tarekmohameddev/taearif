<?php

/**
 * Full OpenAPI 3.0 correction script.
 * - Detects fallback requestBody schemas and replaces with inferred schemas (rules A–F).
 * - Adds missing path parameters ({id}, {appId}, {gateway}, etc.).
 * - Replaces string enum numbers with integer enum.
 * - Ensures secured endpoints have security: [{ "sanctum": [] }].
 * - Enforces standard 200 response: { "status": "success", "data": {} }.
 * - Preserves existing correct schemas (affiliate, categories, etc.).
 *
 * Usage: php fix_openapi_full.php [input.json] [output.json]
 * Default: storage/api-docs/api-docs.json → overwrite.
 */

$input = $argc >= 2 ? $argv[1] : (__DIR__ . '/storage/api-docs/api-docs.json');
$output = $argc >= 3 ? $argv[2] : $input;

$json = @file_get_contents($input);
if ($json === false) {
    fwrite(STDERR, "Cannot read: {$input}\n");
    exit(1);
}

$doc = json_decode($json, true);
if (!is_array($doc) || !isset($doc['paths'])) {
    fwrite(STDERR, "Invalid OpenAPI JSON or missing paths\n");
    exit(1);
}

$paths = &$doc['paths'];
$publicPathPatterns = ['callback', 'webhook', 'oauth2-callback'];

// ----- Helpers -----

function extractPathParamNames(string $path): array
{
    if (preg_match_all('/\{(\w+)\}/', $path, $m)) {
        return $m[1];
    }
    return [];
}

function pathParamType(string $name): string
{
    $lower = strtolower($name);
    if ($lower === 'gateway' || $lower === 'slug' || $lower === 'code' || $lower === 'region' || $lower === 'type'
        || $lower === 'priority' || $lower === 'procedure' || $lower === 'stage' || $lower === 'user'
        || $lower === 'reminder' || strpos($lower, 'type') !== false) {
        return 'string';
    }
    if (strpos($lower, 'id') !== false || $lower === 'appid' || $lower === 'rentalid' || $lower === 'customerid'
        || $lower === 'transaction_id' || $lower === 'user_theme_id' || $lower === 'addon_id'
        || $lower === 'installationid' || $lower === 'purchaserequestid' || $lower === 'stageid') {
        return 'integer';
    }
    return 'string';
}

function isPublicPath(string $path, string $summary): bool
{
    $s = strtolower($path . ' ' . $summary);
    foreach (['callback', 'webhook', 'oauth2-callback'] as $pattern) {
        if (strpos($s, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Detect fallback requestBody schema:
 * - schema is only "type": "string" (no properties)
 * - "data": { "type": "array", "items": { "type": "string" } }
 * - empty "required": []
 * - only generic placeholder (single "data" or only name/title/description/status with no required)
 */
function isFallbackRequestBody(?array $requestBody): bool
{
    if ($requestBody === null || !isset($requestBody['content'])) {
        return false;
    }
    $content = $requestBody['content'];
    $schema = null;
    if (isset($content['application/json']['schema'])) {
        $schema = $content['application/json']['schema'];
    } else {
        return false;
    }

    if (!is_array($schema)) {
        return false;
    }

    // Top-level schema is just "type": "string"
    if (isset($schema['type']) && $schema['type'] === 'string' && empty($schema['properties'])) {
        return true;
    }

    $props = $schema['properties'] ?? [];
    $required = $schema['required'] ?? [];

    // Empty required: fallback if single "data" array of strings, or only generic props
    if ($required === []) {
        if (count($props) === 1 && isset($props['data']) && is_array($props['data'])) {
            $data = $props['data'];
            if (isset($data['type']) && $data['type'] === 'array') {
                $items = $data['items'] ?? [];
                if (isset($items['type']) && $items['type'] === 'string') {
                    return true;
                }
            }
        }
        // Do NOT treat as fallback if schema has specific fields (affiliate: fullname, bank_name, etc.)
        $specificKeys = ['fullname', 'bank_name', 'bank_account_number', 'iban', 'email', 'password', 'slug'];
        foreach ($specificKeys as $k) {
            if (isset($props[$k])) {
                return false; // keep existing correct schema
            }
        }
        $genericOnly = true;
        foreach (array_keys($props) as $key) {
            if (!in_array($key, ['name', 'title', 'description', 'status', 'amount', 'data', 'config', 'app_id'], true)) {
                $genericOnly = false;
                break;
            }
        }
        if ($genericOnly && count($props) <= 5) {
            return true;
        }
    }

    // Single property "data" with array of strings
    if (count($props) === 1 && isset($props['data'])) {
        $data = $props['data'];
        if (is_array($data) && isset($data['type']) && $data['type'] === 'array') {
            $items = $data['items'] ?? [];
            if (isset($items['type']) && $items['type'] === 'string') {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check if path/summary indicates we must use rule C (app_id integer, config).
 */
function isInstallVerifyPurchasePayment(string $path, string $summary): bool
{
    $s = strtolower($path . ' ' . $summary);
    return (strpos($s, 'install') !== false || strpos($s, 'verify') !== false
        || strpos($s, 'purchase') !== false || strpos($s, 'payment') !== false);
}

/**
 * Check if path/summary indicates webhook/callback (rule E).
 */
function isWebhookOrCallback(string $path, string $summary): bool
{
    $s = strtolower($path . ' ' . $summary);
    return (strpos($s, 'callback') !== false || strpos($s, 'webhook') !== false || strpos($s, 'oauth2-callback') !== false);
}

/**
 * Check if path/summary indicates file upload (rule F).
 */
function isFileUpload(string $path, string $summary): bool
{
    $s = strtolower($path . ' ' . $summary);
    return (strpos($s, 'upload') !== false || strpos($s, 'upload-image') !== false
        || strpos($s, 'upload-deed') !== false || strpos($s, 'upload-chunk') !== false
        || strpos($s, 'upload-receipt') !== false || strpos($s, 'upload-video') !== false);
}

function isUploadMultiple(string $path): bool
{
    return (strpos(strtolower($path), 'upload-multiple') !== false);
}

/**
 * Check if path/summary indicates change/move/reorder/toggle (rule D).
 */
function isChangeMoveReorderToggle(string $path, string $summary): bool
{
    $s = strtolower($path . ' ' . $summary);
    return (strpos($s, 'change-stage') !== false || strpos($s, 'change-status') !== false
        || strpos($s, 'change-priority') !== false || strpos($s, 'change-procedure') !== false
        || strpos($s, 'change-type') !== false || strpos($s, '/move') !== false
        || strpos($s, 'reorder') !== false || strpos($s, 'toggle') !== false);
}

/**
 * Build schema for change/move/reorder/toggle (rule D).
 */
function buildChangeMoveReorderToggleSchema(string $path): array
{
    $pathLower = strtolower($path);
    $props = ['id' => ['type' => 'integer']];
    if (strpos($pathLower, 'change-stage') !== false) {
        $props['stage_id'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['stage_id']];
    }
    if (strpos($pathLower, 'change-status') !== false) {
        $props['status_id'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['status_id']];
    }
    if (strpos($pathLower, 'change-priority') !== false) {
        $props['priority_id'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['priority_id']];
    }
    if (strpos($pathLower, 'change-procedure') !== false) {
        $props['procedure_id'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['procedure_id']];
    }
    if (strpos($pathLower, 'change-type') !== false) {
        $props['type_id'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['type_id']];
    }
    if (strpos($pathLower, 'move') !== false) {
        $props['position'] = ['type' => 'integer'];
        return ['type' => 'object', 'properties' => $props, 'required' => ['position']];
    }
    if (strpos($pathLower, 'toggle') !== false) {
        $props['value'] = ['type' => 'boolean'];
        $props['status'] = ['type' => 'string'];
        return ['type' => 'object', 'properties' => $props];
    }
    return ['type' => 'object', 'properties' => $props];
}

/**
 * Build CREATE schema (rule A): name/title, description, status, *_id integer, config/settings/meta object.
 */
function buildCreateSchema(string $path, string $method): array
{
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    $resource = 'resource';
    foreach ($segments as $seg) {
        if ($seg !== '' && !preg_match('/^\{[^}]+\}$/', $seg)) {
            $resource = $seg;
            break;
        }
    }

    $properties = [
        'name' => ['type' => 'string', 'maxLength' => 255],
        'title' => ['type' => 'string', 'maxLength' => 255],
        'description' => ['type' => 'string', 'nullable' => true],
        'status' => ['type' => 'string', 'maxLength' => 50],
        'config' => ['type' => 'object'],
        'settings' => ['type' => 'object'],
        'meta' => ['type' => 'object'],
    ];

    // Common *_id fields
    $idFields = ['category_id', 'user_id', 'property_id', 'project_id', 'building_id', 'customer_id', 'app_id'];
    foreach ($idFields as $f) {
        $properties[$f] = ['type' => 'integer'];
    }

    $required = ['name'];
    if ($resource === 'blogs' || $resource === 'posts') {
        $required = ['title'];
    }
    if ($resource === 'categories') {
        $required = ['name'];
    }
    if ($resource === 'properties') {
        $required = ['title'];
    }

    return [
        'type' => 'object',
        'properties' => $properties,
        'required' => $required,
    ];
}

/**
 * Build UPDATE schema (rule B): same as create but all optional.
 */
function buildUpdateSchema(string $path): array
{
    $create = buildCreateSchema($path, 'post');
    unset($create['required']);
    $create['properties'] = $create['properties'] ?? [];
    foreach ($create['properties'] as $k => $v) {
        $create['properties'][$k]['nullable'] = true;
    }
    return $create;
}

/**
 * Standard 200 response schema.
 */
function standard200Schema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'status' => ['type' => 'string', 'example' => 'success'],
            'data' => ['type' => 'object'],
        ],
    ];
}

// ----- Fix requestBody for install/verify/purchase/payment (app_id integer) even when not "fallback" -----
function ensureAppIdInteger(array &$requestBody): void
{
    if ($requestBody === null || !isset($requestBody['content']['application/json']['schema'])) {
        return;
    }
    $schema = &$requestBody['content']['application/json']['schema'];
    if (!isset($schema['properties']['app_id'])) {
        return;
    }
    $schema['properties']['app_id'] = ['type' => 'integer', 'description' => 'Application ID'];
    if (!isset($schema['required']) || !is_array($schema['required'])) {
        $schema['required'] = [];
    }
    if (!in_array('app_id', $schema['required'], true)) {
        $schema['required'] = array_values(array_unique(array_merge($schema['required'], ['app_id'])));
    }
}

// ----- Process each path -----
foreach ($paths as $pathStr => $pathItem) {
    if (!is_array($pathItem)) {
        continue;
    }

    $pathParams = extractPathParamNames($pathStr);

    foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $method) {
        if (!isset($pathItem[$method]) || !is_array($pathItem[$method])) {
            continue;
        }

        $op = &$pathItem[$method];
        $summary = (string) ($op['summary'] ?? '');

        // --- RequestBody ---
        if (isset($op['requestBody'])) {
            $rb = &$op['requestBody'];
            if (isset($rb['content']['application/json']['schema'])) {
                if (isInstallVerifyPurchasePayment($pathStr, $summary)) {
                    ensureAppIdInteger($rb);
                }
            }
            if (isFallbackRequestBody($rb)) {
            if (isWebhookOrCallback($pathStr, $summary)) {
                $op['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => ['type' => 'object', 'additionalProperties' => true],
                        ],
                    ],
                ];
            } elseif (isUploadMultiple($pathStr)) {
                $op['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'files' => [
                                        'type' => 'array',
                                        'items' => ['type' => 'string', 'format' => 'binary'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ];
            } elseif (isFileUpload($pathStr, $summary)) {
                $op['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => ['type' => 'string', 'format' => 'binary'],
                                ],
                            ],
                        ],
                    ],
                ];
            } elseif (isChangeMoveReorderToggle($pathStr, $summary)) {
                $schema = buildChangeMoveReorderToggleSchema($pathStr);
                $op['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => ['schema' => $schema],
                    ],
                ];
            } elseif (isInstallVerifyPurchasePayment($pathStr, $summary)) {
                $op['requestBody'] = [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['app_id'],
                                'properties' => [
                                    'app_id' => ['type' => 'integer'],
                                    'config' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ];
            } else {
                $allOptional = in_array($method, ['put', 'patch'], true);
                $schema = $allOptional ? buildUpdateSchema($pathStr) : buildCreateSchema($pathStr, $method);
                $op['requestBody'] = [
                    'required' => !$allOptional,
                    'content' => [
                        'application/json' => ['schema' => $schema],
                    ],
                ];
            }
            }
        }

        // --- Path parameters ---
        $existingParams = $op['parameters'] ?? [];
        $existingByName = [];
        foreach ($existingParams as $p) {
            if (isset($p['name'])) {
                $existingByName[$p['name']] = true;
            }
        }
        $toAdd = [];
        foreach ($pathParams as $name) {
            if (isset($existingByName[$name])) {
                continue;
            }
            $toAdd[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => pathParamType($name)],
            ];
        }
        if ($toAdd !== []) {
            $op['parameters'] = array_merge($toAdd, $existingParams);
        }

        // --- 200 response ---
        if (isset($op['responses']['200'])) {
            if (!isset($op['responses']['200']['content']['application/json']['schema'])) {
                $op['responses']['200']['content'] = [
                    'application/json' => [
                        'schema' => standard200Schema(),
                    ],
                ];
            } else {
                $sch = &$op['responses']['200']['content']['application/json']['schema'];
                if (!isset($sch['properties']['status']) || !isset($sch['properties']['data'])) {
                    $sch['properties'] = array_merge(
                        ['status' => ['type' => 'string', 'example' => 'success'], 'data' => ['type' => 'object']],
                        $sch['properties'] ?? []
                    );
                }
            }
        }

        // --- Security ---
        if (!isPublicPath($pathStr, $summary)) {
            if (empty($op['security']) || !is_array($op['security'])) {
                $op['security'] = [['sanctum' => []]];
            }
        }
    }

    $doc['paths'][$pathStr] = $pathItem;
}

// ----- Integer enums (replace string enum numbers with integer) -----
function fixIntegerEnums(array &$node): void
{
    if (isset($node['type'], $node['enum']) && $node['type'] === 'integer') {
        $fixed = [];
        foreach ($node['enum'] as $v) {
            if (is_numeric($v)) {
                $fixed[] = (int) $v;
            } else {
                $fixed[] = $v;
            }
        }
        $node['enum'] = $fixed;
        return;
    }
    if (isset($node['enum']) && is_array($node['enum'])) {
        $allNumeric = true;
        foreach ($node['enum'] as $v) {
            if (!is_numeric($v)) {
                $allNumeric = false;
                break;
            }
        }
        if ($allNumeric && count($node['enum']) > 0) {
            $node['type'] = 'integer';
            $node['enum'] = array_map(function ($v) {
                return (int) $v;
            }, $node['enum']);
        }
        return;
    }
    foreach ($node as $key => &$value) {
        if (is_array($value)) {
            fixIntegerEnums($value);
        }
    }
}

fixIntegerEnums($doc);

// ----- Write -----
$encoded = json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($encoded === false) {
    fwrite(STDERR, "JSON encode failed\n");
    exit(1);
}

$dir = dirname($output);
if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
    fwrite(STDERR, "Cannot create directory: {$dir}\n");
    exit(1);
}

if (file_put_contents($output, $encoded) === false) {
    fwrite(STDERR, "Cannot write: {$output}\n");
    exit(1);
}

echo "Written: {$output}\n";
