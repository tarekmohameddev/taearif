<?php

$appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
$extraHosts = array_filter(array_map('trim', explode(',', (string) env('TENANT_BRAND_ASSET_HOSTS', ''))));
$localHosts = array_filter(array_map('trim', explode(',', (string) env('TENANT_BRAND_LOCAL_ASSET_HOSTS', ''))));

return [
    'asset_hosts' => array_values(array_unique(array_filter(array_merge(
        is_string($appHost) ? [$appHost] : [],
        $extraHosts
    )))),
    'local_asset_hosts' => array_values(array_unique(array_filter(array_merge(
        is_string($appHost) ? [$appHost] : [],
        $localHosts
    )))),
    'payload_ttl_seconds' => (int) env('TENANT_BRAND_PAYLOAD_TTL', 21600),
    'metadata_ttl_seconds' => (int) env('TENANT_BRAND_METADATA_TTL', 2592000),
    'negative_max_per_minute' => (int) env('TENANT_BRAND_NEGATIVE_MAX_PER_MINUTE', 10000),
];
