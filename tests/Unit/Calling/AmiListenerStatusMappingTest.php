<?php

namespace Tests\Unit\Calling;

use App\Console\Commands\Calling\AmiListenCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for the AMI event -> call_logs status mapping logic.
 * Uses reflection to test the private mapEventToStatus / mapHangupEvent methods
 * without needing a database or real AMI connection.
 */
class AmiListenerStatusMappingTest extends TestCase
{
    private AmiListenCommand $command;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new AmiListenCommand();
        $this->ref     = new ReflectionClass($this->command);
    }

    private function callPrivate(string $method, ...$args): mixed
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($this->command, ...$args);
    }

    /** @test */
    public function test_dial_begin_subevent_maps_to_ringing_dest(): void
    {
        $result = $this->callPrivate('mapDialEvent', ['SubEvent' => 'Begin']);
        $this->assertSame('ringing_dest', $result);
    }

    /** @test */
    public function test_dial_end_answer_maps_to_answered(): void
    {
        $result = $this->callPrivate('mapDialEvent', ['SubEvent' => 'End', 'DialStatus' => 'ANSWER']);
        $this->assertSame('answered', $result);
    }

    /** @test */
    public function test_dial_end_busy_maps_to_busy(): void
    {
        $result = $this->callPrivate('mapDialEvent', ['SubEvent' => 'End', 'DialStatus' => 'BUSY']);
        $this->assertSame('busy', $result);
    }

    /** @test */
    public function test_dial_end_noanswer_maps_to_no_answer(): void
    {
        $result = $this->callPrivate('mapDialEvent', ['SubEvent' => 'End', 'DialStatus' => 'NOANSWER']);
        $this->assertSame('no_answer', $result);
    }

    /** @test */
    public function test_hangup_after_answer_maps_to_completed(): void
    {
        $result = $this->callPrivate('mapHangupEvent', ['Cause' => '16'], 'answered');
        $this->assertSame('completed', $result);
    }

    /** @test */
    public function test_hangup_with_busy_cause_maps_to_busy(): void
    {
        $result = $this->callPrivate('mapHangupEvent', ['Cause' => '17'], 'ringing_dest');
        $this->assertSame('busy', $result);
    }

    /** @test */
    public function test_hangup_with_no_answer_cause_maps_to_no_answer(): void
    {
        $result = $this->callPrivate('mapHangupEvent', ['Cause' => '19'], 'ringing_agent');
        $this->assertSame('no_answer', $result);
    }
}
