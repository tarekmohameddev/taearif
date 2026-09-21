<?php

use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Services\TenantBrand\TenantBrandResolver;
use App\Services\TenantWebsite\PublicBrandingLogo;
use Illuminate\Contracts\Console\Kernel;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$resolver = $app->make(TenantBrandResolver::class);
$branding = $app->make(PublicBrandingLogo::class);
$hostCounts = [];
$winners = [];
$withoutLogo = 0;
$localHosts = array_fill_keys([
    'api.taearif.com',
    'taearif.com',
    'www.taearif.com',
], true);

User::query()
    ->where('account_type', 'tenant')
    ->select(['id', 'username'])
    ->orderBy('id')
    ->chunkById(100, function ($tenants) use ($resolver, $branding, &$hostCounts, &$winners, &$withoutLogo, $localHosts) {
        $ids = $tenants->pluck('id');
        $globals = TenantGlobalComponent::whereIn('user_id', $ids)->get()->keyBy('user_id');
        $layouts = TenantWebsiteLayout::whereIn('user_id', $ids)->get()->keyBy('user_id');
        $settings = BasicSetting::whereIn('user_id', $ids)->get()->keyBy('user_id');

        foreach ($tenants as $tenant) {
            $globalData = is_array($globals->get($tenant->id)?->data) ? $globals->get($tenant->id)->data : [];
            $layoutData = is_array($layouts->get($tenant->id)?->data) ? $layouts->get($tenant->id)->data : [];
            $brandingUrl = $branding->from($settings->get($tenant->id), $globalData);
            $url = $resolver->resolve($globalData, $brandingUrl, $layoutData);

            if ($url === null) {
                $withoutLogo++;
                continue;
            }

            $host = strtolower((string) parse_url($url, PHP_URL_HOST));
            $hostCounts[$host] = ($hostCounts[$host] ?? 0) + 1;
            $path = null;
            $size = null;
            if (isset($localHosts[$host])) {
                $urlPath = parse_url($url, PHP_URL_PATH);
                if (is_string($urlPath)) {
                    $candidate = realpath(public_path(ltrim(rawurldecode($urlPath), '/')));
                    $publicRoot = realpath(public_path());
                    if ($candidate !== false && $publicRoot !== false && is_file($candidate)
                        && str_starts_with($candidate, rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                        $path = $candidate;
                        $size = filesize($candidate);
                    }
                }
            }

            $winners[] = [
                'tenant_id' => (int) $tenant->id,
                'username' => $tenant->username,
                'url' => $url,
                'host' => $host,
                'size' => is_int($size) ? $size : null,
                'local_path' => $path,
            ];
        }
    });

arsort($hostCounts);
$headHosts = array_fill_keys([
    'api.taearif.com',
    'taearif.com',
    'dalel-lovat.vercel.app',
    'c.top4top.io',
    'clusters.sa',
], true);

if (in_array('--head', $argv, true)) {
    $urls = [];
    foreach ($winners as $item) {
        if ($item['size'] === null && isset($headHosts[$item['host']])) {
            $urls[$item['url']] = true;
        }
    }

    $urls = array_keys($urls);
    $headers = [];
    $client = new Client();
    $requests = static function () use ($urls, $client) {
        foreach ($urls as $url) {
            yield $url => static fn () => $client->headAsync($url, [
                'allow_redirects' => ['max' => 3],
                'connect_timeout' => 3,
                'timeout' => 8,
                'http_errors' => false,
            ]);
        }
    };

    (new Pool($client, $requests(), [
        'concurrency' => 15,
        'fulfilled' => static function ($response, string $url) use (&$headers) {
            $length = $response->getHeaderLine('Content-Length');
            $headers[$url] = [
                'status' => $response->getStatusCode(),
                'size' => ctype_digit($length) ? (int) $length : null,
                'content_type' => $response->getHeaderLine('Content-Type') ?: null,
            ];
        },
        'rejected' => static function ($reason, string $url) use (&$headers) {
            $headers[$url] = ['status' => null, 'size' => null, 'content_type' => null];
        },
    ]))->promise()->wait();

    foreach ($winners as &$item) {
        $head = $headers[$item['url']] ?? null;
        if ($item['size'] === null && is_array($head) && is_int($head['size'])) {
            $item['size'] = $head['size'];
            $item['size_source'] = 'http-head';
            $item['http_status'] = $head['status'];
            $item['content_type'] = $head['content_type'];
        }
    }
    unset($item);
}

$sized = array_values(array_filter($winners, static fn (array $item) => is_int($item['size'])));
usort($sized, static fn (array $a, array $b) => $b['size'] <=> $a['size']);

echo json_encode([
    'tenant_count' => array_sum($hostCounts) + $withoutLogo,
    'winning_logo_count' => array_sum($hostCounts),
    'without_logo_count' => $withoutLogo,
    'host_counts' => $hostCounts,
    'largest_local_files' => array_slice($sized, 0, 10),
    'winning_urls_without_local_size' => count($winners) - count($sized),
    'unresolved_size_samples' => array_slice(array_values(array_filter(
        $winners,
        static fn (array $item) => $item['size'] === null
    )), 0, 20),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), PHP_EOL;
