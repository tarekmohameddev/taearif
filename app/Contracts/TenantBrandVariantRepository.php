<?php

namespace App\Contracts;

interface TenantBrandVariantRepository
{
    /** @return array{logoUrl: string, inline: ?string, v: string}|null */
    public function findReady(string $sourceUrl, ?string $sourceContentHash): ?array;
}
