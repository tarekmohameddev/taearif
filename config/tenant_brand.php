<?php

$appHost = parse_url((string) env('APP_URL', ''), PHP_URL_HOST);
$extraHosts = array_filter(array_map('trim', explode(',', (string) env('TENANT_BRAND_ASSET_HOSTS', ''))));
$localHosts = array_filter(array_map('trim', explode(',', (string) env('TENANT_BRAND_LOCAL_ASSET_HOSTS', ''))));
$knownLocalHosts = ['taearif.com', 'api.taearif.com', 'www.taearif.com'];

return [
    'asset_hosts' => array_values(array_unique(array_filter(array_merge(
        is_string($appHost) ? [$appHost] : [],
        $extraHosts
    )))),
    'local_asset_hosts' => array_values(array_unique(array_filter(array_merge(
        is_string($appHost) ? [$appHost] : [],
        $localHosts,
        $knownLocalHosts
    )))),
    'variant_base_url' => env(
        'TENANT_BRAND_VARIANT_BASE_URL',
        'https://api.taearif.com/storage/tenant-brand'
    ),
    'inline_max_bytes' => (int) env('TENANT_BRAND_INLINE_MAX_BYTES', 8192),
    'max_source_bytes' => (int) env('TENANT_BRAND_MAX_SOURCE_BYTES', 5242880),
    'max_source_dimension' => (int) env('TENANT_BRAND_MAX_SOURCE_DIMENSION', 10000),
    'max_source_pixels' => (int) env('TENANT_BRAND_MAX_SOURCE_PIXELS', 40000000),
    'variant_height' => (int) env('TENANT_BRAND_VARIANT_HEIGHT', 160),
    'variant_max_width' => (int) env('TENANT_BRAND_VARIANT_MAX_WIDTH', 800),
    'webp_quality' => (int) env('TENANT_BRAND_WEBP_QUALITY', 82),
    'payload_ttl_seconds' => (int) env('TENANT_BRAND_PAYLOAD_TTL', 21600),
    'metadata_ttl_seconds' => (int) env('TENANT_BRAND_METADATA_TTL', 2592000),
    'negative_max_per_minute' => (int) env('TENANT_BRAND_NEGATIVE_MAX_PER_MINUTE', 10000),
];
