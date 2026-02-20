<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;

class GenerateSwaggerApiPathsCommand extends Command
{
    /** @var int */
    private $mappedCount = 0;

    /** @var int */
    private $formRequestCount = 0;

    /** @var int */
    private $inlineCount = 0;

    /** @var int */
    private $fallbackCount = 0;

    /** @var int */
    private $warningCount = 0;

    /** @var array<int, array{method: string, path: string, controller: string, controller_method: string}> */
    private $unresolvedList = [];

    protected $signature = 'swagger:generate-api-paths
                            {--dry-run : Show what would be generated without writing}
                            {--report : Write unresolved write ops to storage/app/swagger_unresolved_report.json}';

    protected $description = 'Generate OpenAPI PathItem annotations from routes/api.php for L5-Swagger (Main API only, excludes Admin API)';

    public function handle(): int
    {
        $this->mappedCount = 0;
        $this->formRequestCount = 0;
        $this->inlineCount = 0;
        $this->fallbackCount = 0;
        $this->warningCount = 0;
        $this->unresolvedList = [];

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
        $this->line('Mapped: ' . $this->mappedCount . ', FormRequest: ' . $this->formRequestCount . ', Inline: ' . $this->inlineCount . ', Fallback: ' . $this->fallbackCount . ', Warnings: ' . $this->warningCount);

        if ($this->option('report') && $this->unresolvedList !== []) {
            $reportPath = storage_path('app/swagger_unresolved_report.json');
            file_put_contents($reportPath, json_encode(array_values($this->unresolvedList), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('Report written: ' . $reportPath . ' (' . count($this->unresolvedList) . ' entries)');
        }

        return 0;
    }

    /**
     * @return array<int, array{uri: string, path: string, methods: array<string>, action: string, secured: bool, controller_fqcn: string|null, controller_method: string|null, route_uri: string, http_method: string}>
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

            $actionName = $route->getActionName();
            $action = $uri;
            $controllerFqcn = null;
            $controllerMethod = null;
            if (is_string($actionName) && strpos($actionName, '@') !== false) {
                $action = substr($actionName, strrpos($actionName, '@') + 1);
                $controllerFqcn = substr($actionName, 0, strrpos($actionName, '@'));
                $controllerMethod = $action;
            }

            $middleware = $route->gatherMiddleware();
            $secured = in_array('auth:sanctum', $middleware, true);

            foreach ($methods as $method) {
                $out[] = [
                    'uri'              => $uri,
                    'path'             => $path,
                    'methods'          => [$method],
                    'action'           => $action,
                    'secured'          => $secured,
                    'controller_fqcn'  => $controllerFqcn,
                    'controller_method' => $controllerMethod,
                    'route_uri'        => $uri,
                    'http_method'      => $method,
                ];
            }
        }

        return $out;
    }

    /**
     * Group by path; each path has list of operations with full metadata.
     *
     * @param array<int, array{uri: string, path: string, methods: array<string>, action: string, secured: bool, controller_fqcn: string|null, controller_method: string|null, route_uri: string, http_method: string}> $routes
     * @return array<string, array<int, array{method: string, action: string, secured: bool, controller_fqcn: string|null, controller_method: string|null, route_uri: string, http_method: string}>>
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
                'method'            => strtoupper($method),
                'action'            => $r['action'],
                'secured'           => $r['secured'],
                'controller_fqcn'   => $r['controller_fqcn'],
                'controller_method' => $r['controller_method'],
                'route_uri'         => $r['route_uri'],
                'http_method'       => $r['http_method'],
            ];
        }
        ksort($grouped);
        return $grouped;
    }

    /**
     * Resolve validation rules for a write operation. Precedence: docs map, FormRequest, inline extraction, fallback.
     *
     * @param array{method: string, action: string, secured: bool, controller_fqcn: string|null, controller_method: string|null, route_uri: string, http_method: string} $op
     * @param string $path OpenAPI path for warning message
     * @return array{rules: array<string, mixed>, source: 'docs_map'|'form_request'|'inline_extracted'}|null
     */
    private function resolveValidationRulesForOperation(array $op, string $path): ?array
    {
        $controllerFqcn = $op['controller_fqcn'] ?? null;
        $controllerMethod = $op['controller_method'] ?? null;
        if ($controllerFqcn === null || $controllerMethod === null) {
            return null;
        }

        $rules = $this->resolveRulesFromDocsMap($controllerFqcn, $controllerMethod);
        if ($rules !== null) {
            $this->mappedCount++;
            return ['rules' => $rules, 'source' => 'docs_map'];
        }

        $rules = $this->resolveRulesFromFormRequest($controllerFqcn, $controllerMethod);
        if ($rules !== null) {
            $this->formRequestCount++;
            return ['rules' => $rules, 'source' => 'form_request'];
        }

        $rules = $this->resolveRulesFromInlineValidation($controllerFqcn, $controllerMethod);
        if ($rules !== null) {
            $this->inlineCount++;
            return ['rules' => $rules, 'source' => 'inline_extracted'];
        }

        $this->warningCount++;
        $this->unresolvedList[] = [
            'method' => $op['method'],
            'path' => $path,
            'controller' => $controllerFqcn,
            'controller_method' => $controllerMethod,
        ];
        $this->warn("[swagger] rules unresolved for {$op['method']} {$path} ({$controllerFqcn}@{$controllerMethod})");
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveRulesFromDocsMap(string $controllerFqcn, string $controllerMethod): ?array
    {
        $map = config('swagger_request_map', []);
        $key = $controllerFqcn . '@' . $controllerMethod;
        if (! isset($map[$key])) {
            return null;
        }
        $entry = $map[$key];
        if (is_array($entry)) {
            return $entry;
        }
        if (is_string($entry) && class_exists($entry)) {
            if (method_exists($entry, 'rulesForDocs')) {
                return $entry::rulesForDocs();
            }
            if (method_exists($entry, 'rules')) {
                return $entry::rules();
            }
        }
        if (is_callable($entry)) {
            return $entry();
        }
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveRulesFromFormRequest(string $controllerFqcn, string $controllerMethod): ?array
    {
        try {
            if (! class_exists($controllerFqcn)) {
                return null;
            }
            $ref = new ReflectionClass($controllerFqcn);
            if (! $ref->hasMethod($controllerMethod)) {
                return null;
            }
            $method = $ref->getMethod($controllerMethod);
            $params = $method->getParameters();
            $formRequestClass = null;
            foreach ($params as $param) {
                $type = $param->getType();
                if ($type && ! $type->isBuiltin()) {
                    $name = $type->getName();
                    if (is_subclass_of($name, \Illuminate\Foundation\Http\FormRequest::class)) {
                        $formRequestClass = $name;
                        break;
                    }
                }
            }
            if ($formRequestClass === null) {
                return null;
            }
            $request = Request::create('/', 'GET');
            $request->setUserResolver(fn () => null);
            app()->instance('request', $request);
            $formRequest = $formRequestClass::createFrom($request)->setContainer(app());
            if (method_exists($formRequest, 'setRedirector') && app()->bound('redirect')) {
                $formRequest->setRedirector(app('redirect'));
            }
            $rules = $formRequest->rules();
            return is_array($rules) ? $rules : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract validation rules from controller method body (inline $request->validate or Validator::make).
     * Uses token-based parsing; returns null if rules are dynamic or unresolvable.
     *
     * @return array<string, mixed>|null
     */
    private function resolveRulesFromInlineValidation(string $controllerFqcn, string $controllerMethod): ?array
    {
        try {
            if (! class_exists($controllerFqcn)) {
                return null;
            }
            $ref = new ReflectionClass($controllerFqcn);
            if (! $ref->hasMethod($controllerMethod)) {
                return null;
            }
            $method = $ref->getMethod($controllerMethod);
            $file = $ref->getFileName();
            if ($file === false || ! is_readable($file)) {
                return null;
            }
            $body = $this->getControllerMethodBody($file, $method->getStartLine(), $method->getEndLine());
            if ($body === null || $body === '') {
                return null;
            }
            $rules = $this->extractRulesArrayFromMethodBody($body);
            return is_array($rules) && $rules !== [] ? $rules : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Read method body from file by line range (1-based).
     */
    private function getControllerMethodBody(string $file, int $startLine, int $endLine): ?string
    {
        $lines = @file($file, FILE_IGNORE_NEW_LINES);
        if ($lines === false || $endLine > count($lines)) {
            return null;
        }
        $slice = array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
        return implode("\n", $slice);
    }

    /**
     * Find first rules array in method body: $request->validate([...]) or Validator::make(..., [...]) or $var = [...]; ... validate($var).
     * Returns parsed rules array or null.
     *
     * @return array<string, mixed>|null
     */
    private function extractRulesArrayFromMethodBody(string $body): ?array
    {
        $rules = $this->extractRulesArrayFromMethodBodyViaRegex($body);
        if ($rules !== null && $rules !== []) {
            return $rules;
        }
        $rules = $this->extractRulesArrayFromMethodBodyViaTokens($body);
        if ($rules !== null) {
            return $rules;
        }
        return null;
    }

    /**
     * Token-based extraction. Falls back to regex if this returns null.
     *
     * @return array<string, mixed>|null
     */
    private function extractRulesArrayFromMethodBodyViaTokens(string $body): ?array
    {
        $tokens = @token_get_all('<?php ' . $body);
        if ($tokens === false) {
            return null;
        }
        $len = count($tokens);
        $i = 0;
        while ($i < $len) {
            $t = $tokens[$i];
            $content = is_array($t) ? $t[1] : $t;
            if (is_array($t) && $t[0] === T_VARIABLE && $content === '$request') {
                $arr = $this->tryExtractValidateArray($tokens, $i);
                if ($arr !== null) {
                    return $this->parseRulesArrayString($arr);
                }
            }
            if (is_array($t) && $t[0] === T_STRING && $content === 'Validator') {
                $arr = $this->tryExtractValidatorMakeArray($tokens, $i);
                if ($arr !== null) {
                    return $this->parseRulesArrayString($arr);
                }
            }
            $i++;
        }
        return $this->tryExtractRulesVariableThenValidate($body);
    }

    /**
     * Regex-based fallback: find $request->validate([...]) or Validator::make(..., [...]) and extract array by bracket matching.
     *
     * @return array<string, mixed>|null
     */
    private function extractRulesArrayFromMethodBodyViaRegex(string $body): ?array
    {
        $arrayStr = null;
        if (preg_match('/\$request\s*->\s*validate\s*\(\s*\[/', $body, $m, PREG_OFFSET_CAPTURE)) {
            $pos = strpos($body, '[', $m[0][1]);
            if ($pos !== false) {
                $arrayStr = $this->extractArrayLiteralFromString($body, $pos);
            }
        }
        if ($arrayStr === null && preg_match('/Validator\s*::\s*make\s*\(/', $body, $m, PREG_OFFSET_CAPTURE)) {
            $afterParen = strpos($body, '(', $m[0][1]) + 1;
            $arrayStr = $this->findSecondArgumentArrayLiteral($body, $afterParen);
        }
        if ($arrayStr !== null) {
            return $this->parseRulesArrayString($arrayStr);
        }
        return null;
    }

    /**
     * From position of opening [, return the full array literal (bracket-balanced), respecting strings.
     */
    private function extractArrayLiteralFromString(string $body, int $start): ?string
    {
        $len = strlen($body);
        if ($start >= $len || $body[$start] !== '[') {
            return null;
        }
        $depth = 0;
        $i = $start;
        $inString = false;
        $escape = false;
        $quote = null;
        while ($i < $len) {
            $c = $body[$i];
            if ($escape) {
                $escape = false;
                $i++;
                continue;
            }
            if ($inString) {
                if ($c === '\\') {
                    $escape = true;
                } elseif ($c === $quote) {
                    $inString = false;
                }
                $i++;
                continue;
            }
            if ($c === '"' || $c === "'") {
                $inString = true;
                $quote = $c;
                $i++;
                continue;
            }
            if ($c === '[' || $c === '(' || $c === '{') {
                $depth++;
            } elseif ($c === ']' || $c === ')' || $c === '}') {
                $depth--;
                if ($depth === 0 && $c === ']') {
                    return substr($body, $start, $i - $start + 1);
                }
            }
            $i++;
        }
        return null;
    }

    /**
     * From position after opening (, find the second argument (first is data, second is rules array) and return the array literal.
     */
    private function findSecondArgumentArrayLiteral(string $body, int $afterOpenParen): ?string
    {
        $len = strlen($body);
        $i = $afterOpenParen;
        $depth = 1;
        $inString = false;
        $escape = false;
        $quote = null;
        $firstArgEnd = null;
        while ($i < $len && $depth > 0) {
            $c = $body[$i];
            if ($escape) {
                $escape = false;
                $i++;
                continue;
            }
            if ($inString) {
                if ($c === '\\') {
                    $escape = true;
                } elseif ($c === $quote) {
                    $inString = false;
                }
                $i++;
                continue;
            }
            if ($c === '"' || $c === "'") {
                $inString = true;
                $quote = $c;
                $i++;
                continue;
            }
            if ($c === '(' || $c === '[' || $c === '{') {
                $depth++;
            } elseif ($c === ')' || $c === ']' || $c === '}') {
                $depth--;
            }
            if ($depth === 1 && $c === ',') {
                $firstArgEnd = $i;
                break;
            }
            $i++;
        }
        if ($firstArgEnd === null) {
            return null;
        }
        $i = $firstArgEnd + 1;
        while ($i < $len && ctype_space($body[$i])) {
            $i++;
        }
        if ($i >= $len || $body[$i] !== '[') {
            return null;
        }
        return $this->extractArrayLiteralFromString($body, $i);
    }

    /**
     * Given tokens and index at $request, look for -> validate ( [ and return the array substring.
     */
    private function tryExtractValidateArray(array $tokens, int $start): ?string
    {
        $i = $start + 1;
        $len = count($tokens);
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        $op = $tokens[$i] ?? null;
        $isArrow = $op === '->' || (is_array($op) && ($op[0] === T_OBJECT_OPERATOR || ($op[0] === T_STRING && $op[1] === '->')));
        if ($i >= $len || ! $isArrow) {
            return null;
        }
        $i++;
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len || ! is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING) {
            return null;
        }
        if (strtolower($tokens[$i][1]) !== 'validate') {
            return null;
        }
        $i++;
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len || $tokens[$i] !== '(') {
            return null;
        }
        $i++;
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len || $tokens[$i] !== '[') {
            return null;
        }
        return $this->extractArrayLiteralFromTokens($tokens, $i);
    }

    /**
     * Given tokens and index at Validator, look for :: make ( ... , [ and return the second-argument array substring.
     */
    private function tryExtractValidatorMakeArray(array $tokens, int $start): ?string
    {
        $i = $start;
        $len = count($tokens);
        if ($i + 2 >= $len) {
            return null;
        }
        $i++;
        if ($tokens[$i] !== '::') {
            return null;
        }
        $i++;
        if ($i >= $len || ! is_array($tokens[$i]) || $tokens[$i][0] !== T_STRING || strtolower($tokens[$i][1]) !== 'make') {
            return null;
        }
        $i++;
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len || $tokens[$i] !== '(') {
            return null;
        }
        $i++;
        $depth = 1;
        $firstArgEnd = null;
        while ($i < $len && $depth > 0) {
            $t = $tokens[$i];
            $c = is_array($t) ? $t[1] : $t;
            if ($c === '(' || $c === '[') {
                $depth++;
            } elseif ($c === ')' || $c === ']') {
                $depth--;
            }
            $i++;
            if ($depth === 1 && ($c === ',' || $c === ')')) {
                $firstArgEnd = $i - 1;
                break;
            }
        }
        if ($firstArgEnd === null) {
            return null;
        }
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len || $tokens[$i] !== '[') {
            return null;
        }
        return $this->extractArrayLiteralFromTokens($tokens, $i);
    }

    /**
     * Extract array literal starting at token index $start (the '[' token). Returns substring or null.
     */
    private function extractArrayLiteralFromTokens(array $tokens, int $start): ?string
    {
        $depth = 0;
        $parts = [];
        for ($i = $start; $i < count($tokens); $i++) {
            $t = $tokens[$i];
            $content = is_array($t) ? $t[1] : $t;
            $parts[] = $content;
            if ($content === '[' || $content === '(') {
                $depth++;
            } elseif ($content === ']' || $content === ')') {
                $depth--;
                if ($depth === 0) {
                    return implode('', $parts);
                }
            }
        }
        return null;
    }

    /**
     * Try to find validate($var) or Validator::make(..., $var), then $var = [ ... ] earlier in body.
     */
    private function tryExtractRulesVariableThenValidate(string $body): ?array
    {
        $varName = null;
        if (preg_match('/->\s*validate\s*\(\s*\$(\w+)\s*\)/', $body, $m)) {
            $varName = $m[1];
        } elseif (preg_match('/Validator\s*::\s*make\s*\([^,]+,\s*\$(\w+)\s*\)/', $body, $m)) {
            $varName = $m[1];
        }
        if ($varName === null) {
            return null;
        }
        if (! preg_match('/\$' . preg_quote($varName, '/') . '\s*=\s*\[/', $body, $m)) {
            return null;
        }
        $arrayStart = strpos($body, '[', strpos($body, $m[0]));
        if ($arrayStart === false) {
            return null;
        }
        $depth = 1;
        $i = $arrayStart + 1;
        $len = strlen($body);
        while ($i < $len && $depth > 0) {
            $c = $body[$i];
            if ($c === '[' || $c === '(' || $c === '{') {
                $depth++;
            } elseif ($c === ']' || $c === ')' || $c === '}') {
                $depth--;
            }
            $i++;
        }
        if ($depth !== 0) {
            return null;
        }
        $arrayStr = substr($body, $arrayStart, $i - $arrayStart);
        return $this->parseRulesArrayString($arrayStr);
    }

    /**
     * Parse a PHP array literal string into Laravel rules array. Handles string and array values; treats closures/Rule as 'string'.
     *
     * @return array<string, mixed>|null
     */
    private function parseRulesArrayString(string $arrayStr): ?array
    {
        $arrayStr = trim($arrayStr);
        if ($arrayStr === '' || $arrayStr[0] !== '[') {
            return null;
        }
        $tokens = @token_get_all('<?php ' . $arrayStr);
        if ($tokens === false) {
            return null;
        }
        $result = [];
        $i = 0;
        $len = count($tokens);
        while ($i < $len) {
            if ($tokens[$i] === '[' || (is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE)) {
                $i++;
                continue;
            }
            if ($tokens[$i] === ']') {
                break;
            }
            $key = $this->parseArrayKey($tokens, $i);
            if ($key === null) {
                $i++;
                continue;
            }
            while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                $i++;
            }
            if ($i >= $len || $tokens[$i] !== '=>') {
                $i++;
                continue;
            }
            $i++;
            $value = $this->parseArrayValue($tokens, $i);
            if ($value !== null) {
                $result[$key] = $value;
            }
            $i++;
        }
        return $result;
    }

    private function parseArrayKey(array $tokens, int &$i): ?string
    {
        $len = count($tokens);
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len) {
            return null;
        }
        $t = $tokens[$i];
        if (is_array($t) && ($t[0] === T_CONSTANT_ENCAPSED_STRING || $t[0] === T_STRING)) {
            $s = $t[1];
            if (($s[0] === "'" || $s[0] === '"') && strlen($s) >= 2) {
                $i++;
                return stripcslashes(substr($s, 1, -1));
            }
            if ($t[0] === T_STRING) {
                $i++;
                return $s;
            }
        }
        return null;
    }

    /**
     * @return string|array<int, string>|null
     */
    private function parseArrayValue(array $tokens, int &$i)
    {
        $len = count($tokens);
        while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
        if ($i >= $len) {
            return null;
        }
        $t = $tokens[$i];
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
            $s = $t[1];
            $i++;
            return stripcslashes(substr($s, 1, -1));
        }
        if (is_array($t) && $t[0] === T_STRING) {
            $s = $t[1];
            if (strpos($s, 'Rule') !== false || $s === 'function') {
                $i++;
                return 'string';
            }
            $i++;
            return $s;
        }
        if ($t === '[') {
            $inner = [];
            $i++;
            while ($i < $len && $tokens[$i] !== ']') {
                while ($i < $len && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $i++;
                }
                if ($i >= $len || $tokens[$i] === ']') {
                    break;
                }
                if (is_array($tokens[$i]) && $tokens[$i][0] === T_CONSTANT_ENCAPSED_STRING) {
                    $inner[] = stripcslashes(substr($tokens[$i][1], 1, -1));
                    $i++;
                } elseif (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                    $inner[] = $tokens[$i][1];
                    $i++;
                } else {
                    $i++;
                }
                while ($i < $len && $tokens[$i] !== ',' && $tokens[$i] !== ']') {
                    $i++;
                }
                if ($i < $len && $tokens[$i] === ',') {
                    $i++;
                }
            }
            if ($i < $len && $tokens[$i] === ']') {
                $i++;
            }
            return implode('|', $inner);
        }
        if ($t === ')' || $t === '}' || $t === ',') {
            return null;
        }
        $i++;
        return 'string';
    }

    /**
     * Map Laravel validation rules to OpenAPI schema shape.
     *
     * @param array<string, mixed> $rules
     * @return array{required: array<int, string>, properties: array<string, array<string, mixed>>, content_type: 'application/json'|'multipart/form-data'}
     */
    private function rulesToSchema(array $rules): array
    {
        $required = [];
        $properties = [];
        $contentType = 'application/json';
        $normalized = $this->normalizeRulesForSchema($rules);

        foreach ($normalized as $attribute => $ruleSet) {
            if (strpos($attribute, '.') !== false) {
                continue;
            }
            $isRequired = $this->isRuleRequired($ruleSet);
            if ($isRequired) {
                $required[] = $attribute;
            }
            $prop = $this->ruleSetToProperty($ruleSet);
            if (isset($prop['format']) && $prop['format'] === 'binary') {
                $contentType = 'multipart/form-data';
            }
            $properties[$attribute] = $prop;
        }

        foreach ($normalized as $attribute => $ruleSet) {
            if (preg_match('/^(.+)\.\*$/', $attribute, $m)) {
                $base = $m[1];
                if (! isset($properties[$base])) {
                    $properties[$base] = ['type' => 'array', 'items' => ['type' => 'string']];
                } else {
                    $properties[$base]['items'] = $this->ruleSetToProperty($ruleSet);
                }
            }
        }

        return [
            'required' => array_values(array_unique($required)),
            'properties' => $properties,
            'content_type' => $contentType,
        ];
    }

    /**
     * Normalize rules: expand string to array, strip bail/sometimes, flatten array rules to string where possible.
     *
     * @param array<string, mixed> $rules
     * @return array<string, array<int, string>>
     */
    private function normalizeRulesForSchema(array $rules): array
    {
        $out = [];
        foreach ($rules as $key => $value) {
            if (is_string($value)) {
                $tokens = array_map('trim', explode('|', $value));
            } elseif (is_array($value)) {
                $tokens = [];
                foreach ($value as $v) {
                    if (is_string($v)) {
                        $tokens[] = $v;
                    } elseif (is_object($v)) {
                        $tokens[] = 'string';
                    }
                }
            } else {
                $tokens = ['string'];
            }
            $tokens = array_values(array_filter($tokens, function ($t) {
                return $t !== 'bail' && $t !== 'sometimes';
            }));
            if ($tokens === []) {
                $tokens = ['string'];
            }
            $out[$key] = $tokens;
        }
        return $out;
    }

    private function isRuleRequired(array $tokens): bool
    {
        return in_array('required', $tokens, true);
    }

    /**
     * @param array<int, string> $tokens
     * @return array<string, mixed>
     */
    private function ruleSetToProperty(array $tokens): array
    {
        $type = 'string';
        $format = null;
        $minVal = null;
        $maxVal = null;
        $enum = null;

        foreach ($tokens as $t) {
            if (str_starts_with($t, 'max:')) {
                $v = (int) substr($t, 4);
                $maxVal = $v;
            } elseif (str_starts_with($t, 'min:')) {
                $v = (int) substr($t, 4);
                $minVal = $v;
            } elseif (str_starts_with($t, 'in:')) {
                $enum = array_map('trim', explode(',', substr($t, 3)));
            } elseif ($t === 'integer') {
                $type = 'integer';
            } elseif ($t === 'numeric' || $t === 'number') {
                $type = 'number';
            } elseif ($t === 'boolean') {
                $type = 'boolean';
            } elseif ($t === 'array') {
                $type = 'array';
            } elseif ($t === 'email') {
                $type = 'string';
                $format = 'email';
            } elseif (in_array($t, ['file', 'image'], true) || str_starts_with($t, 'mimes:') || str_starts_with($t, 'mimetypes:')) {
                $type = 'string';
                $format = 'binary';
            }
        }

        $prop = ['type' => $type];
        if ($format !== null) {
            $prop['format'] = $format;
        }
        $isNumeric = $type === 'integer' || $type === 'number';
        if ($minVal !== null) {
            if ($isNumeric) {
                $prop['minimum'] = $minVal;
            } else {
                $prop['minLength'] = $minVal;
            }
        }
        if ($maxVal !== null) {
            if ($isNumeric) {
                $prop['maximum'] = $maxVal;
            } else {
                $prop['maxLength'] = $maxVal;
            }
        }
        if ($enum !== null) {
            $prop['enum'] = $enum;
        }
        return $prop;
    }

    /**
     * Emit docblock lines for @OA\RequestBody from schema (JSON or multipart).
     *
     * @param array{required: array<int, string>, properties: array<string, array<string, mixed>>, content_type: 'application/json'|'multipart/form-data'} $schema
     * @return array<int, string>
     */
    private function emitRequestBodyAnnotations(array $schema): array
    {
        $required = $schema['required'];
        $properties = $schema['properties'];
        $contentType = $schema['content_type'];
        $requiredStr = implode(',', array_map(function ($r) {
            return '"' . $this->escapeDocblockValue($r) . '"';
        }, $required));

        $lines = [];
        $requiredBraced = $requiredStr !== '' ? '{' . $requiredStr . '}' : '{}';
        if ($contentType === 'multipart/form-data') {
            $lines[] = " *         @OA\\RequestBody(required=true, @OA\\MediaType(mediaType=\"multipart/form-data\", @OA\\Schema(type=\"object\", required={$requiredBraced},";
        } else {
            $lines[] = " *         @OA\\RequestBody(required=true, @OA\\JsonContent(type=\"object\", required={$requiredBraced},";
        }
        foreach ($properties as $propName => $propSpec) {
            $lines[] = ' *             ' . $this->propertyToOaLine($propName, $propSpec) . ',';
        }
        $lines[] = $contentType === 'multipart/form-data' ? " *         )))," : " *         )),";
        return $lines;
    }

    private function escapeDocblockValue(string $s): string
    {
        return str_replace(['\\', '"', '*/'], ['\\\\', '\\"', '*\/'], $s);
    }

    /**
     * @param array<string, mixed> $spec
     */
    private function propertyToOaLine(string $propName, array $spec): string
    {
        $type = $spec['type'] ?? 'string';
        $parts = ['property="' . $this->escapeDocblockValue($propName) . '"', 'type="' . $type . '"'];
        if (! empty($spec['format'])) {
            $parts[] = 'format="' . $this->escapeDocblockValue((string) $spec['format']) . '"';
        }
        if (isset($spec['minLength'])) {
            $parts[] = 'minLength=' . (int) $spec['minLength'];
        }
        if (isset($spec['maxLength'])) {
            $parts[] = 'maxLength=' . (int) $spec['maxLength'];
        }
        if (isset($spec['minimum'])) {
            $parts[] = 'minimum=' . (int) $spec['minimum'];
        }
        if (isset($spec['maximum'])) {
            $parts[] = 'maximum=' . (int) $spec['maximum'];
        }
        if (! empty($spec['enum'])) {
            $enumVals = array_map(function ($v) {
                return '"' . $this->escapeDocblockValue((string) $v) . '"';
            }, $spec['enum']);
            $parts[] = 'enum={' . implode(',', $enumVals) . '}';
        }
        if ($type === 'array') {
            $itemType = 'string';
            if (isset($spec['items']) && is_array($spec['items'])) {
                $itemType = $spec['items']['type'] ?? 'string';
            }
            $parts[] = '@OA\\Items(type="' . $this->escapeDocblockValue((string) $itemType) . '")';
        } elseif (isset($spec['items'])) {
            $items = $spec['items'];
            $itemType = is_array($items) ? ($items['type'] ?? 'string') : 'string';
            $parts[] = '@OA\\Items(type="' . $this->escapeDocblockValue((string) $itemType) . '")';
        }
        return '@OA\\Property(' . implode(', ', $parts) . ')';
    }

    /**
     * @param array<string, array<int, array{method: string, action: string, secured: bool, controller_fqcn: string|null, controller_method: string|null, route_uri: string, http_method: string}>> $grouped
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
                if (in_array($oaMethod, ['Post', 'Put', 'Patch'], true)) {
                    $resolved = $this->resolveValidationRulesForOperation($op, $path);
                    $requestBodyLines = null;
                    if ($resolved !== null) {
                        try {
                            $schema = $this->rulesToSchema($resolved['rules']);
                            $requestBodyLines = $this->emitRequestBodyAnnotations($schema);
                        } catch (\Throwable $e) {
                            $requestBodyLines = null;
                        }
                    }
                    if ($requestBodyLines !== null) {
                        foreach ($requestBodyLines as $line) {
                            $opsLines[] = $line;
                        }
                    } else {
                        $this->fallbackCount++;
                        $opsLines[] = " *         @OA\\RequestBody(required=true, @OA\\JsonContent(type=\"object\", description=\"Request body (JSON)\", example=\"{}\")),";
                    }
                }
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
