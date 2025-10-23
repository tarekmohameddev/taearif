<?php

namespace App\Jobs\Matching;

use App\Services\Matching\MatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScoreMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $source,
        public int $requestId,
        public bool $forceAi = true,
        public int $limit = 25,
    ) {}

    public function handle(MatchingService $matching): void
    {
        $matching->generateMatchesForRequest($this->source, $this->requestId, $this->limit, $this->forceAi);
    }
}




