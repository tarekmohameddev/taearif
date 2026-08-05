<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Brain;

/**
 * Outcome of a single Employee::runTurn call.
 */
final class EmployeeTurnResult
{
    private function __construct(
        public readonly string  $outcome,   // delivered|shadow|handoff|skipped|failed
        public readonly ?string $reply,
        public readonly ?string $reason,
    ) {}

    public static function delivered(string $reply, string $reason = 'autonomous'): self
    {
        return new self('delivered', $reply, $reason);
    }

    public static function shadowed(string $draft): self
    {
        return new self('shadow', $draft, null);
    }

    public static function handoff(string $message, string $reason): self
    {
        return new self('handoff', $message, $reason);
    }

    public static function skipped(string $reason): self
    {
        return new self('skipped', null, $reason);
    }

    public static function failed(string $fallback, string $reason): self
    {
        return new self('failed', $fallback, $reason);
    }

    public function wasDelivered(): bool { return $this->outcome === 'delivered'; }
    public function wasShadowed():  bool { return $this->outcome === 'shadow'; }
    public function wasSkipped():   bool { return $this->outcome === 'skipped'; }
}
