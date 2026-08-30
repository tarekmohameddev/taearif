<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Contracts;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Location\DTOs\LocationCandidate;

interface LocationRerankService
{
    /**
     * @param LocationCandidate[] $candidates
     */
    public function rerank(
        LlmClient $driver,
        string $model,
        string $rawLocationText,
        string $normalized,
        bool $hasDistrictMarker,
        array $candidates,
    ): LlmResponse;
}

