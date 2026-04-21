<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateMobilePostmanCollection extends Command
{
    protected $signature = 'postman:mobile
                            {--base-url= : Default baseUrl value written to environment file}
                            {--output=docs/api/mobile/postman/mobile.collection.json : Output path for Postman collection JSON}
                            {--env-output=docs/api/mobile/postman/mobile.environment.json : Output path for Postman environment JSON}';

    protected $description = 'Generate an up-to-date Postman collection for /api/mobile routes';

    public function handle(): int
    {
        $routes = $this->getMobileRoutes();

        $collection = [
            'info' => [
                'name' => 'Taearif Mobile API',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => $this->groupToPostmanFolders($routes),
            'variable' => [
                ['key' => 'baseUrl', 'value' => '{{baseUrl}}'],
                ['key' => 'mobile_token', 'value' => '{{mobile_token}}'],
            ],
        ];

        $env = $this->buildEnvironment();

        $outputPath = base_path((string) $this->option('output'));
        $envOutputPath = base_path((string) $this->option('env-output'));

        $this->ensureDir(dirname($outputPath));
        $this->ensureDir(dirname($envOutputPath));

        file_put_contents($outputPath, json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($envOutputPath, json_encode($env, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Written collection: ' . $outputPath);
        $this->info('Written environment: ' . $envOutputPath);
        $this->line('Routes included: ' . count($routes));

        return 0;
    }

    /**
     * @return array<int, array{method: string, uri: string, name: string, is_public: bool}>
     */
    private function getMobileRoutes(): array
    {
        $out = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri(); // e.g. api/mobile/auth/login
            if (!Str::startsWith($uri, 'api/mobile')) {
                continue;
            }

            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            if ($methods === []) {
                $methods = ['GET'];
            }

            $actionName = $route->getActionName();
            $name = is_string($actionName) && str_contains($actionName, '@')
                ? Str::afterLast($actionName, '@')
                : $uri;

            $middleware = $route->gatherMiddleware();
            $isPublic = !in_array('auth:sanctum', $middleware, true);

            foreach ($methods as $method) {
                $out[] = [
                    'method' => strtoupper($method),
                    'uri' => $uri,
                    'name' => $name,
                    'is_public' => $isPublic,
                ];
            }
        }

        usort($out, fn ($a, $b) => strcmp($a['uri'] . $a['method'], $b['uri'] . $b['method']));

        return $out;
    }

    /**
     * @param array<int, array{method: string, uri: string, name: string, is_public: bool}> $routes
     * @return array<int, mixed>
     */
    private function groupToPostmanFolders(array $routes): array
    {
        $folders = [];

        foreach ($routes as $r) {
            $folder = $this->folderNameFor($r['uri']);
            if (!isset($folders[$folder])) {
                $folders[$folder] = [];
            }
            $folders[$folder][] = $this->routeToPostmanItem($r);
        }

        ksort($folders);

        return array_map(
            fn ($name, $items) => ['name' => $name, 'item' => array_values($items)],
            array_keys($folders),
            array_values($folders)
        );
    }

    private function folderNameFor(string $uri): string
    {
        $suffix = Str::after($uri, 'api/mobile');
        $suffix = ltrim($suffix, '/');
        $first = explode('/', $suffix)[0] ?? '';
        if ($first === '') {
            return 'Mobile';
        }
        return Str::title(str_replace('-', ' ', $first));
    }

    /**
     * @param array{method: string, uri: string, name: string, is_public: bool} $r
     * @return array<string, mixed>
     */
    private function routeToPostmanItem(array $r): array
    {
        $rawUrl = '{{baseUrl}}/' . ltrim($r['uri'], '/');

        $headers = [
            ['key' => 'Accept', 'value' => 'application/json', 'type' => 'text'],
            ['key' => 'Content-Type', 'value' => 'application/json', 'type' => 'text'],
        ];

        $request = [
            'auth' => $r['is_public']
                ? ['type' => 'noauth']
                : [
                    'type' => 'bearer',
                    'bearer' => [
                        ['key' => 'token', 'value' => '{{mobile_token}}', 'type' => 'string'],
                    ],
                ],
            'method' => $r['method'],
            'header' => $headers,
            'url' => [
                'raw' => $rawUrl,
            ],
        ];

        $body = $this->exampleBodyFor($r['method'], $r['uri']);
        if ($body !== null) {
            $flags = JSON_UNESCAPED_SLASHES;
            if (is_array($body)) {
                $flags |= JSON_PRETTY_PRINT;
            }
            $request['body'] = [
                'mode' => 'raw',
                'raw' => json_encode($body, $flags),
                'options' => [
                    'raw' => ['language' => 'json'],
                ],
            ];
        }

        $item = [
            'name' => $this->displayNameFor($r['method'], $r['uri'], $r['name']),
            'request' => $request,
        ];

        if ($r['method'] === 'POST' && $r['uri'] === 'api/mobile/auth/login') {
            $item['event'] = [
                [
                    'listen' => 'test',
                    'script' => [
                        'type' => 'text/javascript',
                        'exec' => [
                            "var jsonData = pm.response.json();",
                            "if (jsonData && jsonData.status === 'success' && jsonData.data && jsonData.data.token) {",
                            "  pm.environment.set('mobile_token', jsonData.data.token);",
                            "}",
                        ],
                    ],
                ],
            ];
        }

        return $item;
    }

    private function displayNameFor(string $method, string $uri, string $fallback): string
    {
        $path = '/' . ltrim(Str::after($uri, 'api/mobile'), '/');
        $path = preg_replace('/\{(\w+)\}/', ':$1', $path);
        return "{$method} {$path}";
    }

    /**
     * @return array<string, mixed>|\stdClass|null
     */
    private function exampleBodyFor(string $method, string $uri): array|\stdClass|null
    {
        if (!in_array($method, ['POST', 'PATCH', 'PUT'], true)) {
            return null;
        }

        $dynamic = $this->matchDynamicBody($method, $uri);
        if ($dynamic !== null) {
            return $dynamic;
        }

        return match ($uri) {
            'api/mobile/auth/login' => ['email' => 'user@example.com', 'password' => 'secret'],
            'api/mobile/auth/forgot-password' => ['email' => 'user@example.com'],
            'api/mobile/auth/logout' => new \stdClass(),
            'api/mobile/notifications/read-all' => new \stdClass(),
            'api/mobile/profile' => ['name' => 'Ahmed Ali', 'phone' => '0509999999'],
            'api/mobile/devices' => ['token' => 'your-fcm-or-apns-token', 'platform' => 'android'],
            default => $method === 'POST' ? new \stdClass() : null,
        };
    }

    private function matchDynamicBody(string $method, string $uri): ?array
    {
        if ($method === 'PATCH' && Str::is('api/mobile/customers/*/stage', $uri)) {
            return ['stage_id' => 'deal_completed'];
        }
        if ($method === 'PATCH' && Str::is('api/mobile/customers/*/priority', $uri)) {
            return ['priority_id' => 1];
        }
        if ($method === 'PATCH' && Str::is('api/mobile/property-requests/*/status', $uri)) {
            return ['status_id' => 1];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEnvironment(): array
    {
        $baseUrl = (string) ($this->option('base-url') ?: 'http://127.0.0.1:8000');

        return [
            'name' => 'Taearif Mobile (Local)',
            'values' => [
                [
                    'key' => 'baseUrl',
                    'value' => $baseUrl,
                    'type' => 'default',
                    'enabled' => true,
                ],
                [
                    'key' => 'mobile_token',
                    'value' => '',
                    'type' => 'secret',
                    'enabled' => true,
                ],
            ],
        ];
    }

    private function ensureDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }
        mkdir($dir, 0775, true);
    }
}

