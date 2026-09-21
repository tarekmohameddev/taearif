<?php

namespace App\Services\TenantBrand;

final class TenantBrandProjector
{
    public function __construct(private TenantBrandContentHasher $contentHasher)
    {
    }

    /** @return array{logoUrl: ?string, inline: null, v: string} */
    public function project(?string $sourceUrl): array
    {
        if ($sourceUrl === null) {
            return ['logoUrl' => null, 'inline' => null, 'v' => 'none'];
        }

        $hash = $this->contentHasher->hash($sourceUrl);
        if ($hash === null) {
            return ['logoUrl' => null, 'inline' => null, 'v' => 'none'];
        }

        return ['logoUrl' => $sourceUrl, 'inline' => null, 'v' => $hash];
    }
}
