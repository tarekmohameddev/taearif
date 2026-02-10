<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyRequestAppointmentsRemindersTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTables(): void
    {
        if (!Schema::hasTable('property_request_appointments') || !Schema::hasTable('property_request_reminders')) {
            $this->markTestSkipped('property_request_appointments and property_request_reminders tables required. Run migrations on test DB.');
        }
    }

    private function createPropertyRequestForUser(int $userId): int
    {
        $id = DB::table('users_property_requests')->insertGetId([
            'full_name' => 'Test Requester',
            'phone' => '+966501234567',
            'user_id' => $userId,
            'region' => 'الرياض',
            'is_active' => 1,
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return (int) $id;
    }

    /** @test */
    public function create_appointment_for_property_request_returns_201_and_appointment(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $datetime = now()->addDays(2)->format('Y-m-d\TH:i:s\Z');

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$prId}/appointments", [
            'type' => 'site_visit',
            'datetime' => $datetime,
            'duration' => 30,
            'notes' => 'Test note',
            'title' => 'معاينة عقار',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.appointment.requestId', "property_request_{$prId}")
            ->assertJsonPath('data.appointment.title', 'معاينة عقار')
            ->assertJsonPath('data.appointment.type', 'site_visit')
            ->assertJsonPath('data.appointment.status', 'scheduled')
            ->assertJsonPath('data.appointment.duration', 30);

        $this->assertDatabaseHas('property_request_appointments', [
            'property_request_id' => $prId,
            'user_id' => $tenant->id,
            'type' => 'site_visit',
            'status' => 'scheduled',
        ]);
    }

    /** @test */
    public function create_reminder_for_property_request_returns_201_and_reminder(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $datetime = now()->addDays(5)->format('Y-m-d\TH:i:s\Z');

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$prId}/reminders", [
            'title' => 'متابعة طلب العقار',
            'description' => 'Follow up',
            'datetime' => $datetime,
            'priority' => 'high',
            'type' => 'follow_up',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reminder.requestId', "property_request_{$prId}")
            ->assertJsonPath('data.reminder.title', 'متابعة طلب العقار')
            ->assertJsonPath('data.reminder.status', 'pending')
            ->assertJsonPath('data.reminder.isOverdue', false);

        $daysUntilDue = $res->json('data.reminder.daysUntilDue');
        $this->assertIsInt($daysUntilDue);
        $this->assertGreaterThanOrEqual(0, $daysUntilDue);

        $this->assertDatabaseHas('property_request_reminders', [
            'property_request_id' => $prId,
            'user_id' => $tenant->id,
            'title' => 'متابعة طلب العقار',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function list_includes_appointments_and_reminders_for_property_request(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $now = now();
        $future = $now->copy()->addDays(1);

        DB::table('property_request_appointments')->insert([
            'user_id' => $tenant->id,
            'property_request_id' => $prId,
            'title' => 'Apt 1',
            'type' => 'site_visit',
            'datetime' => $future,
            'duration' => 30,
            'status' => 'scheduled',
            'priority' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('property_request_reminders')->insert([
            'user_id' => $tenant->id,
            'property_request_id' => $prId,
            'title' => 'Rem 1',
            'datetime' => $future,
            'priority' => 1,
            'type' => 'follow_up',
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['property_request'],
            'limit' => 20,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $actions = $res->json('data.actions');
        $this->assertIsArray($actions);

        $propertyRequestAction = null;
        foreach ($actions as $a) {
            if (($a['id'] ?? '') === "property_request_{$prId}") {
                $propertyRequestAction = $a;
                break;
            }
        }
        $this->assertNotNull($propertyRequestAction, 'Property request action should be in list');
        $this->assertArrayHasKey('appointments', $propertyRequestAction);
        $this->assertArrayHasKey('reminders', $propertyRequestAction);
        $this->assertCount(1, $propertyRequestAction['appointments']);
        $this->assertCount(1, $propertyRequestAction['reminders']);
        $this->assertEquals('Apt 1', $propertyRequestAction['appointments'][0]['title']);
        $this->assertEquals('Rem 1', $propertyRequestAction['reminders'][0]['title']);
    }

    /** @test */
    public function show_includes_appointments_and_reminders_for_property_request(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $now = now();
        $future = $now->copy()->addDays(1);
        DB::table('property_request_appointments')->insert([
            'user_id' => $tenant->id,
            'property_request_id' => $prId,
            'title' => 'Show Apt',
            'type' => 'office_meeting',
            'datetime' => $future,
            'duration' => 60,
            'status' => 'scheduled',
            'priority' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$prId}");

        $res->assertOk()->assertJsonPath('status', 'success');
        $action = $res->json('data.action');
        $this->assertArrayHasKey('appointments', $action);
        $this->assertArrayHasKey('reminders', $action);
        $this->assertCount(1, $action['appointments']);
        $this->assertEquals('Show Apt', $action['appointments'][0]['title']);
    }

    /** @test */
    public function invalid_request_id_returns_404_invalid_request_id(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/property_request_99999/appointments', [
            'type' => 'site_visit',
            'datetime' => now()->addDays(1)->toIso8601String(),
        ]);

        $res->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INVALID_REQUEST_ID');
    }

    /** @test */
    public function past_datetime_returns_422_invalid_datetime(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$prId}/appointments", [
            'type' => 'site_visit',
            'datetime' => now()->subDay()->toIso8601String(),
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INVALID_DATETIME');
    }

    /** @test */
    public function non_property_request_actions_have_empty_appointments_and_reminders(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 5,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions') ?? [];
        foreach ($actions as $a) {
            $this->assertArrayHasKey('appointments', $a);
            $this->assertArrayHasKey('reminders', $a);
            if (($a['objectType'] ?? '') !== 'property_request') {
                $this->assertSame([], $a['appointments']);
                $this->assertSame([], $a['reminders']);
            }
        }
    }
}
