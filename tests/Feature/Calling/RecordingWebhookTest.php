<?php

namespace Tests\Feature\Calling;

use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallRecording;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordingWebhookTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_it_rejects_request_without_secret(): void
    {
        $this->postJson('/api/v1/calling/internal/recording-ready', [])
            ->assertStatus(401);
    }

    /** @test */
    public function test_it_upserts_recording_on_valid_webhook(): void
    {
        $tenant = \App\Models\User::factory()->create(['account_type' => 'tenant']);
        $agent  = \App\Models\User::factory()->create(['account_type' => 'employee', 'tenant_id' => $tenant->id]);

        $callId = (string) \Illuminate\Support\Str::uuid();
        CallLog::create([
            'id'        => $callId,
            'tenant_id' => $tenant->id,
            'user_id'   => $agent->id,
            'direction' => 'outbound',
            'to_e164'   => '+966512345678',
            'status'    => 'completed',
        ]);

        config(['calling.internal_secret' => 'test-secret-abc']);

        $this->postJson('/api/v1/calling/internal/recording-ready', [
            'correlation_id' => $callId,
            'path'           => 'recordings/2026/08/' . $callId . '.wav',
            'size'           => 204800,
            'duration'       => 42,
        ], ['X-Taearif-Secret' => 'test-secret-abc'])
            ->assertOk()
            ->assertJson(['message' => 'ok']);

        $this->assertDatabaseHas('call_recordings', [
            'call_log_id'      => $callId,
            'status'           => 'ready',
            'duration_seconds' => 42,
        ]);
    }

    /** @test */
    public function test_it_returns_ok_for_unknown_call_id(): void
    {
        config(['calling.internal_secret' => 'test-secret-abc']);

        $this->postJson('/api/v1/calling/internal/recording-ready', [
            'correlation_id' => '00000000-0000-0000-0000-000000000000',
            'path'           => 'recordings/missing.wav',
        ], ['X-Taearif-Secret' => 'test-secret-abc'])
            ->assertOk()
            ->assertJson(['message' => 'ok']);
    }
}
