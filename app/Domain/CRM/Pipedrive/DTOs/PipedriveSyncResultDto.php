<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\DTOs;

final class PipedriveSyncResultDto
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?int $personId,
        public readonly ?int $orgId,
        public readonly ?int $dealId,
        public readonly ?string $errorMessage,
    ) {}

    public static function skipped(string $reason): self
    {
        return new self(
            success: false,
            status: 'skipped',
            personId: null,
            orgId: null,
            dealId: null,
            errorMessage: $reason,
        );
    }

    public static function failed(string $reason): self
    {
        return new self(
            success: false,
            status: 'failed',
            personId: null,
            orgId: null,
            dealId: null,
            errorMessage: $reason,
        );
    }

    public static function succeeded(int $personId, ?int $orgId, int $dealId): self
    {
        return new self(
            success: true,
            status: 'success',
            personId: $personId,
            orgId: $orgId,
            dealId: $dealId,
            errorMessage: null,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'person_id' => $this->personId,
            'org_id' => $this->orgId,
            'deal_id' => $this->dealId,
            'error_message' => $this->errorMessage,
        ];
    }
}
