<?php

namespace App\Services\TenantBrand;

use App\Contracts\TenantBrandVariantRepository;
use App\Jobs\GenerateTenantBrandVariant;

final class TenantBrandProjector
{
    public function __construct(
        private TenantBrandContentHasher $contentHasher,
        private TenantBrandVariantRepository $variants
    ) {
    }

    /** @return array{logoUrl: ?string, inline: ?string, v: string} */
    public function project(?string $sourceUrl, ?int $tenantId = null): array
    {
        if ($sourceUrl === null) {
            return ['logoUrl' => null, 'inline' => null, 'v' => 'none'];
        }

        $hash = $this->contentHasher->hash($sourceUrl);
        $variant = $this->variants->findReady($sourceUrl, $hash);
        if ($variant !== null) {
            return $variant;
        }

        if ($hash !== null && $tenantId !== null) {
            GenerateTenantBrandVariant::dispatchAfterResponse($sourceUrl, $tenantId);
        }

        if ($hash === null) {
            // Interim fallback until an immutable variant is ready. The prefix
            // makes clear that this is a source-URL revision, not a content hash.
            return [
                'logoUrl' => $sourceUrl,
                'inline' => null,
                'v' => 'original-' . hash('sha256', $sourceUrl),
            ];
        }

        return ['logoUrl' => $sourceUrl, 'inline' => null, 'v' => $hash];
    }
}
