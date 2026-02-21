<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixApiDocsCommand extends Command
{
    protected $signature = 'swagger:fix-api-docs
                            {--input= : Path to api-docs.json (default: storage/api-docs/api-docs.json)}
                            {--output= : Path to write corrected JSON (default: overwrite input)}
                            {--dry-run : Do not write, only report changes}';

    protected $description = 'Post-process OpenAPI api-docs.json: replace generic requestBody, add path params, wrap 200 responses, fix enums, ensure security';

    /** @var array<string, mixed> */
    private $doc = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private $resourceFields = [];

    /** @var array<int, string> */
    private $publicPathPatterns = [];

    /** @var int */
    private $statsRequestBody = 0;

    /** @var int */
    private $statsPathParams = 0;

    /** @var int */
    private $statsResponses = 0;

    /** @var int */
    private $statsEnums = 0;

    /** @var int */
    private $statsSecurity = 0;

    public function handle(): int
    {
        $inputPath = $this->option('input') ?: storage_path('api-docs/api-docs.json');
        $outputPath = $this->option('output') ?: $inputPath;

        if (! is_file($inputPath)) {
            $this->error('Input file not found: ' . $inputPath);
            return 1;
        }

        $json = file_get_contents($inputPath);
        $this->doc = json_decode($json, true);
        if (! is_array($this->doc)) {
            $this->error('Invalid JSON in ' . $inputPath);
            return 1;
        }

        $config = config('api_docs_fix', []);
        $this->resourceFields = $config['resource_fields'] ?? [];
        $this->publicPathPatterns = $config['public_path_patterns'] ?? ['callback', 'webhook', 'oauth2-callback'];
        $this->statsRequestBody = 0;
        $this->statsPathParams = 0;
        $this->statsResponses = 0;
        $this->statsEnums = 0;
        $this->statsSecurity = 0;

        $paths = $this->doc['paths'] ?? [];
        if (! is_array($paths)) {
            $this->error('Missing or invalid paths in document');
            return 1;
        }

        foreach ($paths as $pathStr => $pathItem) {
            if (! is_array($pathItem)) {
                continue;
            }
            $pathParams = $this->extractPathParamNames($pathStr);
            foreach (['get', 'post', 'put', 'patch', 'delete', 'options', 'head'] as $method) {
                if (! isset($pathItem[$method]) || ! is_array($pathItem[$method])) {
                    continue;
                }
            $op = &$pathItem[$method];
            $this->fixOperationRequestBody($pathStr, $method, $op);
            $this->injectRequestBodyExample($op);
            $this->fixOperationPathParameters($pathStr, $pathParams, $op);
            $this->fixOperation200Response($op);
            $this->fixOperationSecurity($pathStr, $op);
            }
            $this->doc['paths'][$pathStr] = $pathItem;
        }

        $this->fixIntegerEnumsInDoc($this->doc);

        if (! $this->option('dry-run')) {
            $dir = dirname($outputPath);
            if (! is_dir($dir)) {
                if (! @mkdir($dir, 0755, true)) {
                    $this->error('Cannot create directory: ' . $dir);
                    return 1;
                }
            }
            $encoded = json_encode($this->doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                $this->error('JSON encode failed');
                return 1;
            }
            file_put_contents($outputPath, $encoded);
            $this->info('Written: ' . $outputPath);
        } else {
            $this->info('Dry run. No file written.');
        }

        $this->line('RequestBody replaced: ' . $this->statsRequestBody . ', path params added: ' . $this->statsPathParams . ', 200 wrapped: ' . $this->statsResponses . ', enums fixed: ' . $this->statsEnums . ', security set: ' . $this->statsSecurity);
        return 0;
    }

    private function isGenericRequestBody(?array $requestBody): bool
    {
        if ($requestBody === null || ! isset($requestBody['content']['application/json']['schema'])) {
            return false;
        }
        $schema = $requestBody['content']['application/json']['schema'];
        $props = $schema['properties'] ?? [];
        if (count($props) !== 1 || ! isset($props['data'])) {
            return false;
        }
        $data = $props['data'];
        if (! isset($data['type']) || $data['type'] !== 'array') {
            return false;
        }
        $items = $data['items'] ?? [];
        return isset($items['type']) && $items['type'] === 'string';
    }

    private function fixOperationRequestBody(string $path, string $method, array &$op): void
    {
        $requestBody = $op['requestBody'] ?? null;
        if (! $this->isGenericRequestBody($requestBody)) {
            return;
        }

        $summary = (string) ($op['summary'] ?? '');
        $pathLower = strtolower($path);
        $summaryLower = strtolower($summary);

        if (preg_match('/callback|webhook|oauth2-callback/', $pathLower) || preg_match('/callback|webhook/', $summaryLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'additionalProperties' => true,
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/upload-multiple|upload-multiple/', $pathLower)) {
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
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/upload|upload-image|upload-deed|upload-chunk|upload-receipt|upload-video/', $pathLower) || preg_match('/upload/', $summaryLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'file' => [
                                    'type' => 'string',
                                    'format' => 'binary',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/change-stage|change-status/', $pathLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'status_id' => ['type' => 'integer'],
                            ],
                            'required' => ['status_id'],
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/change-priority/', $pathLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'priority_id' => ['type' => 'integer'],
                            ],
                            'required' => ['priority_id'],
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/change-procedure/', $pathLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'procedure_id' => ['type' => 'integer'],
                            ],
                            'required' => ['procedure_id'],
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        if (preg_match('/change-type/', $pathLower)) {
            $op['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'type_id' => ['type' => 'integer'],
                            ],
                            'required' => ['type_id'],
                        ],
                    ],
                ],
            ];
            $this->statsRequestBody++;
            return;
        }

        $allOptional = in_array($method, ['put', 'patch'], true);
        $schema = $this->buildSchemaFromPathAndMethod($path, $method, $allOptional);
        $op['requestBody'] = [
            'required' => ! $allOptional,
            'content' => [
                'application/json' => [
                    'schema' => $schema,
                ],
            ],
        ];
        $this->statsRequestBody++;
    }

    private function buildSchemaFromPathAndMethod(string $path, string $method, bool $allOptional): array
    {
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $resource = null;
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === 'v1' || $seg === 'api' || preg_match('/^\{[^}]+\}$/', $seg)) {
                continue;
            }
            $resource = $seg;
            break;
        }
        if ($resource === null) {
            $resource = 'default';
        }
        $fields = $this->resourceFields[$resource] ?? null;
        if ($fields === null) {
            $defaults = config('api_docs_fix.default_fields', [
                'name' => ['type' => 'string', 'required' => false],
                'title' => ['type' => 'string', 'required' => false],
                'description' => ['type' => 'string', 'required' => false],
                'status' => ['type' => 'string', 'required' => false],
                'amount' => ['type' => 'number', 'required' => false],
            ]);
            $fields = $defaults;
        }

        $properties = [];
        $required = [];
        foreach ($fields as $name => $def) {
            $type = $def['type'] ?? 'string';
            $prop = ['type' => $type];
            if ($type === 'string' && ! isset($def['format'])) {
                $prop['maxLength'] = 255;
            }
            $properties[$name] = $prop;
            if (! $allOptional && ! empty($def['required'])) {
                $required[] = $name;
            }
        }
        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }
        return $schema;
    }

    private function extractPathParamNames(string $path): array
    {
        if (preg_match_all('/\{(\w+)\}/', $path, $m)) {
            return $m[1];
        }
        return [];
    }

    private function fixOperationPathParameters(string $pathStr, array $pathParams, array &$op): void
    {
        if ($pathParams === []) {
            return;
        }
        $existing = $op['parameters'] ?? [];
        $existingByName = [];
        foreach ($existing as $p) {
            if (isset($p['name'])) {
                $existingByName[$p['name']] = true;
            }
        }
        $toAdd = [];
        foreach ($pathParams as $name) {
            if (isset($existingByName[$name])) {
                continue;
            }
            $type = (strpos($name, 'id') !== false || $name === 'appId') ? 'integer' : 'string';
            $toAdd[] = [
                'name' => $name,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => $type],
            ];
            $this->statsPathParams++;
        }
        if ($toAdd !== []) {
            $op['parameters'] = array_merge($toAdd, $existing);
        }
    }

    /**
     * Add example object to request body when schema has properties but no example (for Try it out in Swagger UI).
     */
    private function injectRequestBodyExample(array &$op): void
    {
        $content = $op['requestBody']['content']['application/json'] ?? null;
        if ($content === null || ! isset($content['schema']['properties']) || isset($content['example'])) {
            return;
        }
        $schema = $content['schema'];
        $properties = $schema['properties'];
        $required = array_flip($schema['required'] ?? []);
        $example = [];
        foreach ($properties as $propName => $propSpec) {
            if (strpos($propName, '.') !== false) {
                continue;
            }
            $type = $propSpec['type'] ?? 'string';
            if ($type === 'integer') {
                $example[$propName] = isset($propSpec['enum']) ? (int) $propSpec['enum'][0] : 1;
            } elseif ($type === 'number') {
                $example[$propName] = 1.0;
            } elseif ($type === 'boolean') {
                $example[$propName] = true;
            } elseif ($type === 'array') {
                $example[$propName] = [];
            } elseif ($type === 'object' && isset($propSpec['properties']) && is_array($propSpec['properties'])) {
                $nested = [];
                foreach ($propSpec['properties'] as $subName => $subSpec) {
                    $subType = $subSpec['type'] ?? 'string';
                    $nested[$subName] = ($subType === 'integer') ? 1 : (($subType === 'boolean') ? true : 'string');
                }
                $example[$propName] = $nested;
            } elseif (isset($propSpec['enum']) && is_array($propSpec['enum']) && $propSpec['enum'] !== []) {
                $example[$propName] = (string) $propSpec['enum'][0];
            } elseif (($propSpec['format'] ?? '') === 'email') {
                $example[$propName] = 'user@example.com';
            } else {
                $example[$propName] = ($propSpec['format'] ?? '') === 'binary' ? '' : 'string';
            }
        }
        if ($example !== []) {
            $op['requestBody']['content']['application/json']['example'] = $example;
        }
    }

    private function fixOperation200Response(array &$op): void
    {
        if (! isset($op['responses']['200'])) {
            return;
        }
        if (isset($op['responses']['200']['content'])) {
            return;
        }
        $op['responses']['200']['content'] = [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => [
                            'type' => 'string',
                            'example' => 'success',
                        ],
                        'data' => [
                            'type' => 'object',
                        ],
                    ],
                ],
            ],
        ];
        $this->statsResponses++;
    }

    private function isPublicPath(string $path, string $summary): bool
    {
        $s = strtolower($path . ' ' . $summary);
        foreach ($this->publicPathPatterns as $pattern) {
            if (strpos($s, strtolower($pattern)) !== false) {
                return true;
            }
        }
        return false;
    }

    private function fixOperationSecurity(string $pathStr, array &$op): void
    {
        $summary = (string) ($op['summary'] ?? '');
        if ($this->isPublicPath($pathStr, $summary)) {
            return;
        }
        if (isset($op['security']) && is_array($op['security']) && $op['security'] !== []) {
            return;
        }
        $op['security'] = [['sanctum' => []]];
        $this->statsSecurity++;
    }

    private function fixIntegerEnumsInDoc(array &$node): void
    {
        if (isset($node['type']) && isset($node['enum']) && $node['type'] === 'integer') {
            $fixed = [];
            foreach ($node['enum'] as $v) {
                if (is_numeric($v)) {
                    $fixed[] = (int) $v;
                } else {
                    $fixed[] = $v;
                }
            }
            if ($fixed !== $node['enum']) {
                $node['enum'] = $fixed;
                $this->statsEnums++;
            }
            return;
        }
        foreach ($node as $key => &$value) {
            if (is_array($value)) {
                $this->fixIntegerEnumsInDoc($value);
            }
        }
    }

}
