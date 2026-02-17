<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication;

use App\Domain\Communication\Services\StatusTransitionGuard;
use PHPUnit\Framework\TestCase;

class StatusTransitionGuardTest extends TestCase
{
    private StatusTransitionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new StatusTransitionGuard();
    }

    /** @test */
    public function allows_queued_to_sent_whatsapp(): void
    {
        $this->assertTrue($this->guard->canTransition('queued', 'sent', 'whatsapp'));
    }

    /** @test */
    public function allows_sent_to_delivered_whatsapp(): void
    {
        $this->assertTrue($this->guard->canTransition('sent', 'delivered', 'whatsapp'));
    }

    /** @test */
    public function blocks_delivered_to_sent_whatsapp(): void
    {
        $this->assertFalse($this->guard->canTransition('delivered', 'sent', 'whatsapp'));
    }

    /** @test */
    public function blocks_read_to_delivered_whatsapp(): void
    {
        $this->assertFalse($this->guard->canTransition('read', 'delivered', 'whatsapp'));
    }

    /** @test */
    public function allows_same_status(): void
    {
        $this->assertTrue($this->guard->canTransition('sent', 'sent', 'whatsapp'));
    }

    /** @test */
    public function allows_pending_to_sent_sms(): void
    {
        $this->assertTrue($this->guard->canTransition('pending', 'sent', 'sms'));
    }

    /** @test */
    public function allows_sent_to_delivered_sms(): void
    {
        $this->assertTrue($this->guard->canTransition('sent', 'delivered', 'sms'));
    }

    /** @test */
    public function blocks_delivered_to_failed_sms(): void
    {
        $this->assertFalse($this->guard->canTransition('delivered', 'failed', 'sms'));
    }
}
