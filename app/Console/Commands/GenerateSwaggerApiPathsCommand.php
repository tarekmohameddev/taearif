<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class GenerateSwaggerApiPathsCommand extends Command
{
    protected $signature = 'swagger:generate-api-paths
                            {--dry-run : Show what would be generated without writing}';

    protected $description = 'Generate OpenAPI PathItem annotations from routes/api.php for L5-Swagger (Main API only, excludes Admin API)';

    public function handle(): int
    {
        $routes = $this->getMainApiRoutes();
        $grouped = $this->groupRoutesByPath($routes);

        $php = $this->buildPhpDocFile($grouped);

        if ($this->option('dry-run')) {
            $this->info('Dry run. Would write ' . strlen($php) . ' bytes to GeneratedApiPathsDoc.php');
            $this->line('Paths count: ' . count($grouped));
            return 0;
        }

        $path = app_path('Http/Controllers/Api/GeneratedApiPathsDoc.php');
        if (! is_dir(dirname($path))) {
            $this->error('Directory does not exist: ' . dirname($path));
            return 1;
        }

        file_put_contents($path, $php);
        $this->info('Written: ' . $path);
        $this->info('Paths: ' . count($grouped) . '. Run: php artisan l5-swagger:generate');

        return 0;
    }

    /**
     * @return array<int, array{uri: string, path: string, methods: array<string>, action: string, secured: bool}>
     */
    private function getMainApiRoutes(): array
    {
        $mainApiPrefix = 'api/';
        $adminPrefix   = 'api/v1/admin';

        $out = [];
        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (strpos($uri, $mainApiPrefix) !== 0) {
                continue;
            }
            if (strpos($uri, $adminPrefix) === 0) {
                continue;
            }

            $path = '/' . ltrim(substr($uri, strlen($mainApiPrefix)), '/');

            $methods = $route->methods();
            $methods = array_diff($methods, ['HEAD']);
            if (empty($methods)) {
                $methods = ['GET'];
            }

            $action = $route->getActionName();
            if (is_string($action) && strpos($action, '@') !== false) {
                $action = substr($action, strrpos($action, '@') + 1);
            } else {
                $action = $uri;
            }

            $middleware = $route->gatherMiddleware();
            $secured = in_array('auth:sanctum', $middleware, true);

            foreach ($methods as $method) {
                $out[] = [
                    'uri'     => $uri,
                    'path'   => $path,
                    'methods' => [$method],
                    'action'  => $action,
                    'secured' => $secured,
                ];
            }
        }

        return $out;
    }

    /**
     * Group by path; each path has list of { method, action, secured }.
     *
     * @param array<int, array{uri: string, path: string, methods: array<string>, action: string, secured: bool}> $routes
     * @return array<string, array<int, array{method: string, action: string, secured: bool}>>
     */
    private function groupRoutesByPath(array $routes): array
    {
        $grouped = [];
        foreach ($routes as $r) {
            $path = $r['path'];
            $method = $r['methods'][0] ?? 'GET';
            if (! isset($grouped[$path])) {
                $grouped[$path] = [];
            }
            $grouped[$path][] = [
                'method'  => strtoupper($method),
                'action'  => $r['action'],
                'secured' => $r['secured'],
            ];
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * @param array<string, array<int, array{method: string, action: string, secured: bool}>> $grouped
     */
    private function buildPhpDocFile(array $grouped): string
    {
        $pathItems = [];
        foreach ($grouped as $path => $ops) {
            $pathEsc = $this->escapeDocblock($path);
            $opsLines = [];
            $last = count($ops) - 1;
            foreach (array_values($ops) as $i => $op) {
                $method = $op['method'];
                $oaMethod = ucfirst(strtolower($method));
                if (! in_array($oaMethod, ['Get', 'Post', 'Put', 'Patch', 'Delete'], true)) {
                    $oaMethod = 'Get';
                }
                $operationId = $this->uniqueOperationId($path, $method, $i);
                $tag = $this->pathToTag($path);
                $tagEsc = $this->escapeDocblock($tag);
                $summary = $this->escapeDocblock($this->summaryFromAction($op['action'], $path, $method));
                $sec = $op['secured'] ? ', security={{"sanctum":{}}}' : '';
                $opsLines[] = " *     @OA\\{$oaMethod}(";
                $opsLines[] = " *         operationId=\"{$operationId}\",";
                $opsLines[] = " *         tags={\"{$tagEsc}\"},";
                $opsLines[] = " *         summary=\"{$summary}\"{$sec},";
                $opsLines[] = " *         @OA\\Response(response=200, description=\"OK\"),";
                $opsLines[] = " *         @OA\\Response(response=401, description=\"Unauthenticated\")";
                $opsLines[] = $i < $last ? " *     )," : " *     )";
            }
            $pathItems[] = " * @OA\\PathItem(";
            $pathItems[] = " *     path=\"{$pathEsc}\",";
            $pathItems[] = implode("\n", $opsLines);
            $pathItems[] = " * )";
        }

        $annotations = implode("\n *\n", $pathItems);

        return <<<PHP
<?php

namespace App\Http\Controllers\Api;

/**
 * Auto-generated OpenAPI path items for Main API (routes/api.php).
 * Do not edit by hand. Regenerate with: php artisan swagger:generate-api-paths
 *
 * $annotations
 */
class GeneratedApiPathsDoc
{
    // Generated for L5-Swagger scan.
}

PHP;
    }

    private function summaryFromAction(string $action, string $path, string $method): string
    {
        $s = $action;
        if (strpos($s, '@') !== false) {
            $s = substr($s, strrpos($s, '@') + 1);
        }
        $s = preg_replace('/([A-Z])/', ' $1', $s);
        $s = trim($s);
        if ($s === '') {
            $s = $method . ' ' . $path;
        }
        return mb_substr($s, 0, 80);
    }

    private function escapeDocblock(string $s): string
    {
        return str_replace(['\\', '*/', '"'], ['\\\\', '*\/', '\\"'], $s);
    }

    private function uniqueOperationId(string $path, string $method, int $index): string
    {
        $s = strtolower($method) . '_' . trim($path, '/');
        $s = preg_replace('/[^a-z0-9_]/', '_', $s);
        $s = preg_replace('/_+/', '_', $s);
        $s = trim($s, '_') ?: 'op';
        return $s . '_' . $index;
    }

    private function pathToTag(string $path): string
    {
        $segments = array_filter(explode('/', trim($path, '/')));
        if (empty($segments)) {
            return 'API';
        }
        $first = $segments[0];
        if ($first === 'v1' && isset($segments[1])) {
            $first = $segments[1];
        } elseif ($first === 'v2' && isset($segments[1])) {
            $first = $segments[1];
        }
        $tag = str_replace('-', ' ', $first);
        $tag = ucwords($tag);
        return $tag ?: 'API';
    }
}
