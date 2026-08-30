<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Contracts;

use App\Domain\Ai\Location\DTOs\LocationCandidate;

interface LocationCandidateRetrieval
{
    /**
     * @return array{normalized: string, has_district_marker: bool, candidates: LocationCandidate[]}
     */
    public function retrieve(string $rawLocationText): array;
}

