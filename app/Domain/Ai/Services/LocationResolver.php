<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Domain\Ai\Location\Services\LocationRagResolver;

final class LocationResolver
{
    public function __construct(
        private readonly LocationRagResolver $ragResolver,
    ) {}

    /**
     * Resolve free-text Arabic location to city_id and/or district_id for a tenant.
     *
     * @return array{city_id: int|null, region_id: int|null, district_id: int|null, city_name: string|null, district_name: string|null, confidence: int, needs_clarification: bool, clarification_question: string|null}
     */
    public function resolve(int $tenantId, string $locationText): array
    {
        // Note: we keep the legacy output contract but route resolution through
        // a richer, validated Location-RAG pipeline (candidates + LLM rerank).
        return $this->ragResolver
            ->resolve($tenantId, $locationText)
            ->toLegacyArray();
    }
}
