<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Contracts\AmiClientInterface;
use App\Domain\Calling\DTOs\AmiOriginateDto;

/**
 * In-memory AMI fake for tests.
 * Records all originate/hangup calls so assertions can inspect them.
 */
final class FakeAmiClient implements AmiClientInterface
{
    /** @var AmiOriginateDto[] */
    public array $originated = [];

    /** @var string[] channel names passed to hangup() */
    public array $hungUp = [];

    private bool $connected = true;

    public function originate(AmiOriginateDto $dto): void
    {
        $this->originated[] = $dto;
    }

    public function hangup(string $channelName): void
    {
        $this->hungUp[] = $channelName;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $connected): void
    {
        $this->connected = $connected;
    }

    public function assertOriginated(string $callId): void
    {
        $found = collect($this->originated)->firstWhere('callId', $callId);
        assert($found !== null, "Expected AMI originate for call {$callId} but it was not called.");
    }

    public function assertOriginateCount(int $count): void
    {
        assert(
            count($this->originated) === $count,
            "Expected {$count} AMI originates but got " . count($this->originated)
        );
    }
}
