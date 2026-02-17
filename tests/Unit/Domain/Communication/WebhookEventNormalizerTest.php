<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Services\WebhookEventNormalizer;
use PHPUnit\Framework\TestCase;

class WebhookEventNormalizerTest extends TestCase
{
    private WebhookEventNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new WebhookEventNormalizer();
    }

    /** @test */
    public function normalize_for_hash_strips_dynamic_keys(): void
    {
        $payload = [
            'timestamp' => 1234567890,
            'id' => 'wamid.xxx',
            'status' => 'delivered',
            'received_at' => '2024-01-01T00:00:00Z',
        ];
        $out = $this->normalizer->normalizeForHash($payload);
        $this->assertArrayNotHasKey('timestamp', $out);
        $this->assertArrayNotHasKey('received_at', $out);
        $this->assertArrayHasKey('id', $out);
        $this->assertArrayHasKey('status', $out);
    }

    /** @test */
    public function compute_event_hash_is_deterministic(): void
    {
        $payload = ['id' => 'a', 'status' => 'sent'];
        $hash1 = $this->normalizer->computeEventHash($payload);
        $hash2 = $this->normalizer->computeEventHash($payload);
        $this->assertSame($hash1, $hash2);
    }

    /** @test */
    public function compute_event_hash_different_for_different_payload(): void
    {
        $hash1 = $this->normalizer->computeEventHash(['id' => 'a']);
        $hash2 = $this->normalizer->computeEventHash(['id' => 'b']);
        $this->assertNotSame($hash1, $hash2);
    }

    /** @test */
    public function compute_event_hash_ignores_timestamp(): void
    {
        $hash1 = $this->normalizer->computeEventHash(['id' => 'x', 'timestamp' => 1]);
        $hash2 = $this->normalizer->computeEventHash(['id' => 'x', 'timestamp' => 2]);
        $this->assertSame($hash1, $hash2);
    }
}
