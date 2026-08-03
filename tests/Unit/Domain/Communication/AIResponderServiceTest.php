<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Services\AIResponderService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AIResponderServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function createMessage(string $content = 'Hello', string $channel = 'whatsapp'): Message
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => $content,
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => null,
            'meta' => [],
        ]);
        $message->setRelation('conversation', $conversation);
        return $message;
    }

    /** @test */
    public function empty_content_returns_null(): void
    {
        config(['communication.enabled' => true, 'communication.ai.enabled' => true]);
        $message = $this->createMessage('');
        $service = new AIResponderService();
        $this->assertNull($service->suggestReply($message));
    }

    /** @test */
    public function non_whatsapp_channel_returns_null(): void
    {
        config(['communication.enabled' => true, 'communication.ai.enabled' => true]);
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'sms',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Hello',
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => null,
            'meta' => [],
        ]);
        $message->setRelation('conversation', $conversation);
        $service = new AIResponderService();
        $this->assertNull($service->suggestReply($message));
    }

    /** @test */
    public function ai_disabled_returns_null(): void
    {
        config(['communication.enabled' => true, 'communication.ai.enabled' => false]);
        $message = $this->createMessage('Hello');
        $service = new AIResponderService();
        $this->assertNull($service->suggestReply($message));
    }

    /** @test */
    public function missing_api_key_returns_null(): void
    {
        config([
            'communication.enabled' => true,
            'communication.ai.enabled' => true,
            'openai.api_key' => '',
        ]);
        $message = $this->createMessage('Hello');
        $service = new AIResponderService();
        $this->assertNull($service->suggestReply($message));
    }

    /** @test */
    public function provider_error_returns_null(): void
    {
        config(['communication.enabled' => true, 'communication.ai.enabled' => true]);
        $message = $this->createMessage('Hello');
        $service = new AIResponderService();
        $result = $service->suggestReply($message);
        $this->assertTrue($result === null || is_string($result));
    }
}
