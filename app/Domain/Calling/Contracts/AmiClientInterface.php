<?php

declare(strict_types=1);

namespace App\Domain\Calling\Contracts;

use App\Domain\Calling\DTOs\AmiOriginateDto;
use App\Domain\Calling\Exceptions\AmiException;

interface AmiClientInterface
{
    /**
     * Send an AMI Originate action to ring an agent's softphone and then connect to a customer.
     *
     * @throws AmiException
     */
    public function originate(AmiOriginateDto $dto): void;

    /**
     * Hang up a specific Asterisk channel by its full channel name
     * (e.g. "PJSIP/agent_42_7-00000001") using the AMI Hangup action.
     * The channel name is captured from the OriginateResponse event by the
     * ami-listen daemon and stored in call_logs.asterisk_channel.
     *
     * @throws AmiException
     */
    public function hangup(string $channelName): void;

    /**
     * Return true if the underlying connection is still alive.
     */
    public function isConnected(): bool;
}
