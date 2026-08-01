<?php

namespace Tests\Feature\Calling;

use App\Domain\Calling\Contracts\AmiClientInterface;
use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Services\FakeAmiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PlaceCallTest extends TestCase
{
    use RefreshDatabase;

    private FakeAmiClient $fakeAmi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeAmi = new FakeAmiClient();
        $this->app->instance(AmiClientInterface::class, $this->fakeAmi);
    }

    /** @test */
    public function test_it_places_a_call_and_returns_201(): void
    {
        [$tenant, $employee, $trunk, $line, $ext] = $this->seedCallingFixtures();

        $response = $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/calling/calls', [
                'to' => '0512345678',
            ]);

        $response->assertStatus(201);
        $this->fakeAmi->assertOriginateCount(1);

        $this->assertDatabaseHas('call_logs', [
            'tenant_id' => $tenant->id,
            'user_id'   => $employee->id,
            'status'    => 'initiated',
            'to_e164'   => '+966512345678',
        ]);
    }

    /** @test */
    public function test_it_returns_403_when_calling_disabled(): void
    {
        [$tenant, $employee] = $this->seedCallingFixtures();

        // Disable calling
        CallSetting::where('tenant_id', $tenant->id)->update(['enabled' => false]);

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/calling/calls', ['to' => '0512345678'])
            ->assertStatus(403);

        $this->fakeAmi->assertOriginateCount(0);
    }

    /** @test */
    public function test_it_returns_422_for_invalid_phone(): void
    {
        [$tenant, $employee] = $this->seedCallingFixtures();

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/calling/calls', ['to' => '123'])
            ->assertStatus(422);
    }

    /** @test */
    public function test_it_returns_409_when_no_active_extension(): void
    {
        [$tenant, $employee, $trunk, $line] = $this->seedCallingFixtures();

        // Deactivate the extension
        CallAgentExtension::where('user_id', $employee->id)->update(['is_active' => false]);

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/v1/calling/calls', ['to' => '0512345678'])
            ->assertStatus(409);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function seedCallingFixtures(): array
    {
        // Create tenant owner
        $tenant = \App\Models\User::factory()->create(['account_type' => 'tenant']);

        // Create employee
        $employee = \App\Models\User::factory()->create([
            'account_type' => 'employee',
            'tenant_id'    => $tenant->id,
        ]);

        // Calling settings
        $settings = CallSetting::create([
            'tenant_id'        => $tenant->id,
            'enabled'          => true,
            'record_by_default'=> false,
            'max_channels'     => 5,
        ]);

        // Trunk + SIM line
        $trunk = CallTrunk::create([
            'tenant_id'                => $tenant->id,
            'name'                     => 'Test Trunk',
            'type'                     => 'yeastar_gsm',
            'ownership'                => 'company_hosted',
            'registration_mode'        => 'register',
            'asterisk_endpoint_prefix' => 'trunk_test',
            'status'                   => 'registered',
        ]);

        $line = CallSimLine::create([
            'tenant_id'         => $tenant->id,
            'trunk_id'          => $trunk->id,
            'label'             => 'GSM Port 1',
            'msisdn'            => '+966501111111',
            'asterisk_endpoint' => 'trunk_test_gsm1',
            'port_index'        => 1,
            'is_active'         => true,
        ]);

        // Agent extension
        $ext = CallAgentExtension::create([
            'tenant_id'            => $tenant->id,
            'user_id'              => $employee->id,
            'sip_username'         => "agent_{$tenant->id}_{$employee->id}",
            'sip_password_encrypted' => Crypt::encryptString('testpassword123'),
            'extension'            => (string) $employee->id,
            'asterisk_context'     => 'taearif-out',
            'is_active'            => true,
        ]);

        // Grant calling.place permission to employee
        $employee->givePermissionTo('calling.place');

        return [$tenant, $employee, $trunk, $line, $ext];
    }
}
