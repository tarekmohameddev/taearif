<?php

declare(strict_types=1);

namespace App\Domain\Ai\Contracts;

use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;

interface LlmClient
{
    public function complete(LlmRequest $request): LlmResponse;
}
