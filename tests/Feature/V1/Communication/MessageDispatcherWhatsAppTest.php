<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Domain\Communication\Contracts\MessageDispatcher;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MessageDispatcherWhatsAppTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('messages')) {
            $this->markTestSkipped('conversations and messages tables required.');
        }
    }

    /** @test */
    public function dispatch_updates_message_to_sent_on_provider_success(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->withArgs(function ($phone, $content): bool {
                return $phone === '+966501234567' && $content === 'Hello';
            })->andReturn(true);
        });

        $user = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Hello',
            'direction' => 'outbound',
            'status' => 'queued',
            'provider_message_id' => null,
        ]);

        $dispatcher = app(MessageDispatcher::class);
        $dispatcher->dispatch($message);

        $message->refresh();
        $this->assertSame('sent', $message->status);
    }

    /** @test */
    public function dispatch_updates_message_to_failed_and_throws_on_provider_failure(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(false);
        });

        $user = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Hello',
            'direction' => 'outbound',
            'status' => 'queued',
            'provider_message_id' => null,
        ]);

        $dispatcher = app(MessageDispatcher::class);

        try {
            $dispatcher->dispatch($message);
            $this->fail('Expected ProviderSendFailedException');
        } catch (\App\Domain\Communication\Exceptions\ProviderSendFailedException $e) {
            $message->refresh();
            $this->assertSame('failed', $message->status);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
