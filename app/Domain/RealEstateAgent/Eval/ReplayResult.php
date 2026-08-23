<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Eval;

final class ReplayResult
{
    /**
     * @param string[] $failures
     */
    public function __construct(
        public readonly string $fixtureId,
        public readonly bool   $passed,
        public readonly array  $failures,
    ) {}
}
