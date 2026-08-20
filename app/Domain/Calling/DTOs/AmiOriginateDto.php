<?php

declare(strict_types=1);

namespace App\Domain\Calling\DTOs;

final class AmiOriginateDto
{
    public function __construct(
        public readonly string $callId,        // UUID = TAEARIF_CALL_ID
        public readonly string $sipUsername,   // agent SIP id
        public readonly string $context,       // taearif-out
        public readonly string $destDialString, // TAEARIF_DEST (e.g. 966512345678)
        public readonly string $trunkEndpoint,  // TAEARIF_TRUNK PJSIP endpoint id
        public readonly string $callerIdE164,   // TAEARIF_CALLERID E.164
        public readonly bool   $record,         // TAEARIF_RECORD
        public readonly int    $ringTimeoutMs = 30000,
    ) {}
}
