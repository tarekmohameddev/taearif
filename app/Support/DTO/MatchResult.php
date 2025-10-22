<?php

namespace App\Support\DTO;

class MatchResult
{
    public int $propertyId;
    public int $databaseScore = 0; // 0..50
    public ?int $aiScore = null;   // 0..50
    public int $matchScore = 0;    // 0..100
    public ?string $explanation = null;
    /** @var string[]|null */
    public ?array $matchedCriteria = null;

    public function __construct(int $propertyId)
    {
        $this->propertyId = $propertyId;
    }
}



