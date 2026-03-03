<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Listeners\HandleConversationOpened;
use App\Domain\Communication\Listeners\HandleMessageReceived;
use App\Domain\Communication\Listeners\HandleMessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class QueuedListenerWiringTest extends TestCase
{
    /** @test */
    public function handle_message_received_implements_should_queue(): void
    {
        $listener = new HandleMessageReceived();
        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    /** @test */
    public function handle_message_sent_implements_should_queue(): void
    {
        $listener = new HandleMessageSent();
        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    /** @test */
    public function handle_conversation_opened_implements_should_queue(): void
    {
        $listener = new HandleConversationOpened();
        $this->assertInstanceOf(ShouldQueue::class, $listener);
    }

    /** @test */
    public function queue_name_resolves_from_config(): void
    {
        $listener = new HandleMessageReceived();
        $queue = $listener->viaQueue();
        $this->assertIsString($queue);
        $this->assertSame(config('communication.automation.queue', 'communication'), $queue);
    }
}
