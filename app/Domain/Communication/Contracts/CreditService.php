<?php

namespace App\Domain\Communication\Contracts;

interface CreditService
{
    public function hasSufficientCredits(int $userId, int $amount): bool;

    public function deduct(int $userId, int $amount, string $referenceType, string $referenceId): void;

    public function refund(int $userId, int $amount, string $referenceType, string $referenceId): void;

    public function reserve(int $userId, int $amount, string $referenceType, string $referenceId): void;

    public function consumeReserved(int $userId, int $amount, string $referenceType, string $referenceId): void;

    public function releaseReserved(int $userId, int $amount, string $referenceType, string $referenceId): void;
}
