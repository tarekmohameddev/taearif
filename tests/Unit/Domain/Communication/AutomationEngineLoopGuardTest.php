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

class AutomationEngineLoopGuardTest extends TestCase
{
    use DatabaseTransactions;

    private function createMessageWithConversation(array $meta = [], string $channel = 'whatsapp'): Message
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => $channel,
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
    public function meta_source_ai_is_skipped(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => true]);
        config(['communication.automation.enabled' => true]);
        config(['communication.ai.enabled' => true]);

        $message = $this->createMessageWithConversation(['source' => 'ai']);
        $message->setRelation('conversation', $message->conversation()->first());
        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertFalse(Cache::has('ai-suggest-message:' . $message->id));
        $this->assertFalse(RateLimiter::tooManyAttempts('ai-suggest-conversation:' . $message->conversation_id, 1));
    }

    /** @test */
    public function duplicate_message_key_is_skipped(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => true]);
        config(['communication.automation.enabled' => true]);
        config(['communication.ai.enabled' => true]);

        $message = $this->createMessageWithConversation();
        $message->setRelation('conversation', $message->conversation()->first());
        Cache::put('ai-suggest-message:' . $message->id, true, 3600);

        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertTrue(Cache::has('ai-suggest-message:' . $message->id));
        $this->assertFalse(RateLimiter::tooManyAttempts('ai-suggest-conversation:' . $message->conversation_id, 1));
    }

    /** @test */
    public function rate_limit_exceeded_is_skipped(): void
    {
        $ai = Mockery::mock(AIResponderService::class);
        $ai->shouldNotReceive('suggestReply');
        $this->app->instance(AIResponderService::class, $ai);

        config(['communication.enabled' => true]);
        config(['communication.automation.enabled' => true]);
        config(['communication.ai.enabled' => true]);
        config(['communication.automation.ai_rate_limit_attempts' => 2]);
        config(['communication.automation.ai_rate_limit_window_seconds' => 60]);

        $message = $this->createMessageWithConversation();
        $message->setRelation('conversation', $message->conversation()->first());
        $key = 'ai-suggest-conversation:' . $message->conversation_id;
        RateLimiter::hit($key, 60);
        RateLimiter::hit($key, 60);

        $engine = app(AutomationEngine::class);
        $engine->handleMessageReceived($message);

        $this->assertTrue(RateLimiter::tooManyAttempts($key, 2));
        $this->assertFalse(Cache::has('ai-suggest-message:' . $message->id));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
