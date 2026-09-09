<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use RuntimeException;

class FakeDashboardPresenceRedisFactory implements RedisFactory
{
    private FakeDashboardPresenceRedisConnection $connection;

    public function __construct(bool $shouldThrow = false)
    {
        $this->connection = new FakeDashboardPresenceRedisConnection($shouldThrow);
    }

    public function connection($name = null)
    {
        return $this->connection;
    }

    public function resolve($name = null)
    {
        return $this->connection($name);
    }
}

class FakeDashboardPresenceRedisConnection
{
    public array $sets = [];

    public array $expires = [];

    private array $results = [];

    public function __construct(
        private readonly bool $shouldThrow = false
    ) {
    }

    public function pipeline(callable $callback): array
    {
        if ($this->shouldThrow) {
            throw new RuntimeException('redis unavailable');
        }

        $commandsBefore = count($this->results);
        $callback($this);

        return array_slice($this->results, $commandsBefore);
    }

    public function zadd(string $key, int $score, string $member): int
    {
        $this->sets[$key][$member] = $score;

        return $this->recordResult(1);
    }

    public function expire(string $key, int $ttl): int
    {
        $this->expires[$key] = $ttl;

        return $this->recordResult(1);
    }

    public function zremrangebyscore(string $key, string $min, string $max): int
    {
        $removed = 0;
        $maxScore = (int) $max;

        foreach ($this->sets[$key] ?? [] as $member => $score) {
            if ($score <= $maxScore) {
                unset($this->sets[$key][$member]);
                $removed++;
            }
        }

        return $this->recordResult($removed);
    }

    public function zcard(string $key): int
    {
        return $this->recordResult(count($this->sets[$key] ?? []));
    }

    private function recordResult(int $value): int
    {
        $this->results[] = $value;

        return $value;
    }
}
