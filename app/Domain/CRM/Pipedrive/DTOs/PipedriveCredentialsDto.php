<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\DTOs;

final class PipedriveCredentialsDto
{
    public function __construct(
        public readonly bool $enabled,
        public readonly ?string $apiToken,
        public readonly ?string $baseUrl,
        public readonly ?int $pipelineId,
        public readonly ?int $stageId,
        public readonly ?string $dealTitlePrefix,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiToken !== null && $this->baseUrl !== null;
    }

    public function canAutoSync(): bool
    {
        return $this->enabled && $this->isConfigured();
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'api_token' => $this->apiToken ? '********' : null,
            'base_url' => $this->baseUrl,
            'pipeline_id' => $this->pipelineId,
            'stage_id' => $this->stageId,
            'deal_title_prefix' => $this->dealTitlePrefix,
        ];
    }
}
