<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\DTOs;

final class LocationCandidate
{
    public function __construct(
        public readonly string $type, // city|district|region
        public readonly int $id,
        public readonly string $nameAr,
        public readonly ?string $nameEn,
        public readonly ?int $cityId = null,        // districts only
        public readonly ?string $cityNameAr = null, // districts only
        public readonly float $score = 0.0,
        public readonly string $reason = '',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type'         => $this->type,
            'id'           => $this->id,
            'name_ar'      => $this->nameAr,
            'name_en'      => $this->nameEn,
            'city_id'      => $this->cityId,
            'city_name_ar' => $this->cityNameAr,
            'score'        => round($this->score, 2),
            'reason'       => $this->reason,
        ];
    }
}

