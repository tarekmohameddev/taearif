<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Automation\AutomationEngine;
use App\Domain\Communication\Services\AIResponderService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class AutomationEngineFlagGuardTest extends TestCase
{
    use DatabaseTransactions;

    private function createMessageWithConversation(array $meta = []): Message
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);
        return Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Hello',
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => null,
            'meta' => $meta,
        ]);
    }

    /** @test */
    public function communication_disabled_does_not_call_ai(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => false]);
        config(['communication.automation.enabled' => true]);
        config(['communication.ai.enabled' => true]);

        $message = $this->createMessageWithConversation();
        $message->setRelation('conversation', $message->conversation()->first());
        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertFalse(Cache::has('ai-suggest-message:' . $message->id));
        $this->assertFalse(RateLimiter::tooManyAttempts('ai-suggest-conversation:' . $message->conversation_id, 1));
    }

    /** @test */
    public function automation_disabled_does_not_call_ai(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => true]);
        config(['communication.automation.enabled' => false]);
        config(['communication.ai.enabled' => true]);

        $message = $this->createMessageWithConversation();
        $message->setRelation('conversation', $message->conversation()->first());
        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertFalse(Cache::has('ai-suggest-message:' . $message->id));
        $this->assertFalse(RateLimiter::tooManyAttempts('ai-suggest-conversation:' . $message->conversation_id, 1));
    }

    /** @test */
    public function ai_disabled_does_not_call_ai(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => true]);
        config(['communication.automation.enabled' => true]);
        config(['communication.ai.enabled' => false]);

        $message = $this->createMessageWithConversation();
        $message->setRelation('conversation', $message->conversation()->first());
        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertFalse(Cache::has('ai-suggest-message:' . $message->id));
        $this->assertFalse(RateLimiter::tooManyAttempts('ai-suggest-conversation:' . $message->conversation_id, 1));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
