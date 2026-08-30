<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\DTOs;

/**
 * Canonical location resolution output for bot/search use.
 *
 * This is intentionally richer than the legacy LocationResolver array contract,
 * but can be projected back down to preserve backwards compatibility.
 */
final class LocationResolution
{
    /**
     * @param LocationCandidate[] $candidates
     */
    public function __construct(
        public readonly ?int $cityId,
        public readonly ?int $districtId,
        public readonly ?int $regionId,
        public readonly ?string $cityName,
        public readonly ?string $districtName,
        public readonly int $confidence,
        public readonly bool $needsClarification,
        public readonly ?string $clarificationQuestion,
        public readonly string $source, // llm|deterministic|fallback
        public readonly array $candidates = [],
    ) {}

    /** @return array<string, mixed> */
    public function toLegacyArray(): array
    {
        return [
            'city_id'                => $this->cityId,
            'region_id'              => $this->regionId,
            'district_id'            => $this->districtId,
            'city_name'              => $this->cityName,
            'district_name'          => $this->districtName,
            'confidence'             => $this->confidence,
            'needs_clarification'    => $this->needsClarification,
            'clarification_question' => $this->clarificationQuestion,
            // extra diagnostics (safe for callers to ignore)
            'resolution_source'      => $this->source,
            'candidates'             => array_map(static fn (LocationCandidate $c) => $c->toArray(), $this->candidates),
        ];
    }
}

