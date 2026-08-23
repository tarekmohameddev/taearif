<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WhatsAppWebhookServiceMappingLogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['wa_numbers', 'whatsapp_users'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        Cache::flush();
    }

    public function test_unresolved_mapping_warns_once_then_debugs(): void
    {
        Log::spy();

        $service = app(WhatsAppWebhookService::class);
        $payload = [
            'phone_number_id' => 'test-unresolved-'.uniqid(),
            'display_phone_number' => '966500009999',
        ];

        $this->assertNull($service->resolveTenantFromPayload($payload, 'meta'));
        $this->assertNull($service->resolveTenantFromPayload($payload, 'meta'));

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context) use ($payload): bool {
                return $message === 'communication.whatsapp.wa_number_mapping'
                    && ($context['outcome'] ?? null) === 'unresolved'
                    && ($context['phone_number_id'] ?? null) === $payload['phone_number_id'];
            })
            ->once();

        Log::shouldHaveReceived('debug')
            ->withArgs(function (string $message, array $context) use ($payload): bool {
                return $message === 'communication.whatsapp.wa_number_mapping'
                    && ($context['outcome'] ?? null) === 'unresolved'
                    && ($context['phone_number_id'] ?? null) === $payload['phone_number_id'];
            })
            ->once();
    }
}
