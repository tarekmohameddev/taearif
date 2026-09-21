<?php

namespace App\Services\TenantBrand;

use App\Contracts\TenantBrandVariantRepository;
use App\Models\TenantBrandSourceAsset;

final class EloquentTenantBrandVariantRepository implements TenantBrandVariantRepository
{
    public function findReady(string $sourceUrl, ?string $sourceContentHash): ?array
    {
        $asset = TenantBrandSourceAsset::where('source_url_hash', hash('sha256', $sourceUrl))
            ->where('status', 'ready')
            ->first();

        if (! $asset || ! $asset->variant_url || ! $asset->active_asset_content_hash) {
            return null;
        }

        if ($sourceContentHash !== null
            && ! hash_equals((string) $asset->source_content_hash, $sourceContentHash)) {
            return null;
        }

        $asset->forceFill(['last_referenced_at' => now()])->saveQuietly();

        return [
            'logoUrl' => $asset->variant_url,
            'inline' => $asset->inline_data,
            'v' => $asset->active_asset_content_hash,
        ];
    }
}
