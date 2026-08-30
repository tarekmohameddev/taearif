<?php

namespace Tests\Feature\Calling;

use App\Console\Commands\Calling\AmiListenCommand;
use App\Domain\Calling\Events\CallStatusUpdated;
use App\Domain\Calling\Models\CallLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use ReflectionClass;
use Tests\TestCase;

class AmiListenUniqueidTest extends TestCase
{
    use RefreshDatabase;

    private AmiListenCommand $command;
    private ReflectionClass $ref;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([CallStatusUpdated::class]);

        $this->command = new AmiListenCommand();
        $this->ref     = new ReflectionClass($this->command);
    }

    private function callPrivate(string $method, ...$args): mixed
    {
        $m = $this->ref->getMethod($method);
        $m->setAccessible(true);
        return $m->invoke($this->command, ...$args);
    }

    private function makeCall(): CallLog
    {
        $tenant = \App\Models\User::factory()->create(['account_type' => 'tenant']);
        $agent  = \App\Models\User::factory()->create([
            'account_type' => 'employee',
            'tenant_id'    => $tenant->id,
        ]);

        return CallLog::create([
            'id'        => '7f100dd3-4591-4253-8ac1-f033dfb03f15',
            'tenant_id' => $tenant->id,
            'user_id'   => $agent->id,
            'direction' => 'outbound',
            'to_e164'   => '+966566064451',
            'status'    => 'initiated',
        ]);
    }

    /** @test */
    public function test_originate_response_stores_uniqueid_and_channel(): void
    {
        $log = $this->makeCall();

        $this->callPrivate('processEvent', [
            'Event'     => 'OriginateResponse',
            'ActionID'  => 'orig-' . $log->id,
            'Channel'   => 'PJSIP/agent_1430_1430-00000021',
            'Uniqueid'  => '1787700790.33',
            'Response'  => 'Success',
        ]);

        $log->refresh();
        $this->assertSame('1787700790.33', $log->asterisk_uniqueid);
        $this->assertSame('PJSIP/agent_1430_1430-00000021', $log->asterisk_channel);
        $this->assertDatabaseHas('call_events', [
            'call_log_id' => $log->id,
            'event_name'  => 'OriginateResponse',
        ]);
    }

    /** @test */
    public function test_varset_stores_uniqueid_without_flooding_events(): void
    {
        $log = $this->makeCall();

        $this->callPrivate('processEvent', [
            'Event'     => 'VarSet',
            'Variable'  => 'TAEARIF_CALL_ID',
            'Value'     => $log->id,
            'Uniqueid'  => '1787700790.33',
            'Channel'   => 'PJSIP/agent_1430_1430-00000021',
        ]);

        $log->refresh();
        $this->assertSame('1787700790.33', $log->asterisk_uniqueid);
        $this->assertSame(0, $log->events()->count());
    }

    /** @test */
    public function test_hangup_correlates_by_linkedid_and_stores_payload(): void
    {
        $log = $this->makeCall();
        $log->update(['asterisk_uniqueid' => '1787700790.33']);

        $this->callPrivate('processEvent', [
            'Event'    => 'Hangup',
            'Channel'  => 'PJSIP/966566064451@loopback-00000022',
            'Uniqueid' => '1787700790.34',
            'Linkedid' => '1787700790.33',
            'Cause'    => '17',
            'Cause-txt'=> 'User busy',
        ]);

        $log->refresh();
        $this->assertSame('busy', $log->status);
        $this->assertDatabaseHas('call_events', [
            'call_log_id' => $log->id,
            'event_name'  => 'Hangup',
        ]);
        $this->assertSame('1787700790.33', $log->asterisk_uniqueid);
    }

    /** @test */
    public function test_b_leg_does_not_overwrite_a_leg_uniqueid(): void
    {
        $log = $this->makeCall();
        $log->update(['asterisk_uniqueid' => '1787700790.33']);

        $this->callPrivate('persistAsteriskIdentifiers', $log, [
            'Uniqueid' => '1787700790.34',
            'Linkedid' => '1787700790.33',
        ]);

        $log->refresh();
        $this->assertSame('1787700790.33', $log->asterisk_uniqueid);
    }
}
