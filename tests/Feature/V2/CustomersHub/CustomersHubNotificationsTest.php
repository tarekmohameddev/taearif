<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Domain\CustomersHub\Services\CustomersHubNotificationService;
use App\Domain\CustomersHub\Services\CustomersHubPropertyRequestNotifier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CustomersHubNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('Required tables missing in test DB. Run migrations on test DB.');
        }
    }

    private function requireNotificationTables(): void
    {
        if (
            !Schema::hasTable('app_notifications')
            || !Schema::hasTable('app_notification_recipients')
            || !Schema::hasTable('users_property_requests')
        ) {
            $this->markTestSkipped('Notification tables missing. Run migrations on test DB.');
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function createPropertyRequestForUser(int $userId): int
    {
        return (int) DB::table('users_property_requests')->insertGetId([
            'full_name' => 'Notification Test Customer',
            'phone' => '+9665' . random_int(10000000, 99999999),
            'user_id' => $userId,
            'source' => 'website',
            'region' => 'الرياض',
            'purpose' => 'sale',
            'property_type' => 'residential',
            'budget_from' => 100000,
            'budget_to' => 250000,
            'seriousness' => 'خلال 3 أشهر',
            'is_active' => 1,
            'is_read' => 0,
            'is_archived' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function unread_endpoint_returns_only_current_viewer_unread_notifications(): void
    {
        $this->requireNotificationTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee1 = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);
        $employee2 = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employee1, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employee2, $tenant, ['customers_hub_requests.view']);

        $propertyRequestId = $this->createPropertyRequestForUser($tenant->id);

        /** @var CustomersHubPropertyRequestNotifier $notifier */
        $notifier = app(CustomersHubPropertyRequestNotifier::class);
        $notifier->notifyStageChanged($tenant->id, $propertyRequestId, 'new_lead', 'follow_up', $tenant->id);

        Sanctum::actingAs($employee1);
        $res1 = $this->getJson('/api/v2/customers-hub/notifications/unread?sourceType=property_request');
        $res1->assertOk();
        $res1->assertJsonPath('status', 'success');
        $this->assertGreaterThanOrEqual(1, count($res1->json('data.items') ?? []));

        Sanctum::actingAs($employee2);
        $notificationId = (int) DB::table('app_notifications')->orderByDesc('id')->value('id');
        $this->patchJson("/api/v2/customers-hub/notifications/{$notificationId}/read")->assertOk();

        Sanctum::actingAs($employee1);
        $resEmployee1 = $this->getJson('/api/v2/customers-hub/notifications/unread?sourceType=property_request');
        $resEmployee1->assertOk();
        $this->assertGreaterThanOrEqual(1, count($resEmployee1->json('data.items') ?? []));

        Sanctum::actingAs($employee2);
        $resEmployee2 = $this->getJson('/api/v2/customers-hub/notifications/unread?sourceType=property_request');
        $resEmployee2->assertOk();
        $this->assertSame(0, count($resEmployee2->json('data.items') ?? []));
    }

    /** @test */
    public function users_without_permission_do_not_receive_recipient_rows(): void
    {
        $this->requireNotificationTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employeeWithPerm = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);
        $employeeWithoutPerm = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employeeWithPerm, $tenant, ['customers_hub_requests.view']);

        $propertyRequestId = $this->createPropertyRequestForUser($tenant->id);

        app(CustomersHubPropertyRequestNotifier::class)->notifyUpdated(
            $tenant->id,
            $propertyRequestId,
            ['notes'],
            $tenant->id
        );

        $notificationId = (int) DB::table('app_notifications')->orderByDesc('id')->value('id');

        $this->assertTrue(
            DB::table('app_notification_recipients')
                ->where('notification_id', $notificationId)
                ->where('recipient_user_id', $employeeWithPerm->id)
                ->exists()
        );

        $this->assertFalse(
            DB::table('app_notification_recipients')
                ->where('notification_id', $notificationId)
                ->where('recipient_user_id', $employeeWithoutPerm->id)
                ->exists()
        );
    }

    /** @test */
    public function creating_appointment_creates_property_request_notification(): void
    {
        $this->requireNotificationTables();

        if (!Schema::hasTable('property_request_appointments')) {
            $this->markTestSkipped('property_request_appointments table missing.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);
        Sanctum::actingAs($tenant);

        $beforeCount = DB::table('app_notifications')->count();

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$prId}/appointments", [
            'type' => 'site_visit',
            'datetime' => now()->addDay()->format('Y-m-d H:i:s'),
            'duration' => 30,
            'title' => 'Site visit',
        ]);

        $res->assertStatus(201);

        $this->assertSame($beforeCount + 1, DB::table('app_notifications')->count());
        $this->assertDatabaseHas('app_notifications', [
            'tenant_user_id' => $tenant->id,
            'type' => CustomersHubNotificationService::TYPE_APPOINTMENT_CREATED,
            'source_type' => 'property_request',
            'source_id' => $prId,
        ]);
    }

    /** @test */
    public function stage_update_creates_property_request_notification(): void
    {
        $this->requireNotificationTables();

        if (!Schema::hasTable('customers_hub_stages')) {
            $this->markTestSkipped('customers_hub_stages table missing.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);

        $stageId = DB::table('customers_hub_stages')
            ->where('is_active', true)
            ->value('stage_id');

        if (!$stageId) {
            $this->markTestSkipped('No active customers_hub_stages row available.');
        }

        Sanctum::actingAs($tenant);

        $beforeCount = DB::table('app_notifications')->count();

        $res = $this->patchJson("/api/v2/customers-hub/requests/property_request_{$prId}", [
            'stage_id' => $stageId,
        ]);

        $res->assertOk();
        $this->assertSame($beforeCount + 1, DB::table('app_notifications')->count());
        $this->assertDatabaseHas('app_notifications', [
            'type' => CustomersHubNotificationService::TYPE_STAGE_CHANGED,
            'source_id' => $prId,
        ]);
    }

    /** @test */
    public function due_reminder_command_is_idempotent(): void
    {
        $this->requireNotificationTables();

        if (!Schema::hasTable('property_request_reminders')) {
            $this->markTestSkipped('property_request_reminders table missing.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);
        $datetime = now()->addMinutes(10)->format('Y-m-d H:i:s');

        $reminderId = (int) DB::table('property_request_reminders')->insertGetId([
            'user_id' => $tenant->id,
            'property_request_id' => $prId,
            'customer_id' => null,
            'title' => 'Follow up call',
            'description' => null,
            'datetime' => $datetime,
            'priority' => 1,
            'type' => 'follow_up',
            'status' => 'pending',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('customers-hub:notify-due-reminders');
        $countAfterFirst = DB::table('app_notifications')
            ->where('type', CustomersHubNotificationService::TYPE_REMINDER_DUE)
            ->whereJsonContains('payload->reminderId', $reminderId)
            ->count();

        Artisan::call('customers-hub:notify-due-reminders');
        $countAfterSecond = DB::table('app_notifications')
            ->where('type', CustomersHubNotificationService::TYPE_REMINDER_DUE)
            ->whereJsonContains('payload->reminderId', $reminderId)
            ->count();

        $this->assertSame(1, $countAfterFirst);
        $this->assertSame(1, $countAfterSecond);
    }

    /** @test */
    public function stage_change_actor_does_not_receive_own_notification_as_unread(): void
    {
        $this->requireNotificationTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employee, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);

        app(CustomersHubPropertyRequestNotifier::class)->notifyStageChanged(
            $tenant->id,
            $prId,
            'new_lead',
            'follow_up',
            (int) $tenant->id
        );

        $notificationService = app(CustomersHubNotificationService::class);

        $this->assertSame(0, $notificationService->unreadCountForViewer($tenant->id, 'property_request'));
        $this->assertSame(1, $notificationService->unreadCountForViewer($employee->id, 'property_request'));

        Sanctum::actingAs($tenant);
        $actorUnread = $this->getJson('/api/v2/customers-hub/notifications/unread?sourceType=property_request');
        $actorUnread->assertOk();
        $this->assertSame(0, count($actorUnread->json('data.items') ?? []));

        $actorHistory = $this->getJson('/api/v2/customers-hub/notifications?sourceType=property_request');
        $actorHistory->assertOk();
        $this->assertGreaterThanOrEqual(1, count($actorHistory->json('data.items') ?? []));

        $actorList = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 25,
            'offset' => 0,
            'objectTypes' => ['property_request'],
        ]);
        $actorList->assertOk();
        $actorMatch = collect($actorList->json('data.actions') ?? [])
            ->first(fn ($a) => ($a['sourceId'] ?? null) === $prId);
        $this->assertNotNull($actorMatch);
        $this->assertFalse($actorMatch['isUnread'] ?? true);

        Sanctum::actingAs($employee);
        $employeeUnread = $this->getJson('/api/v2/customers-hub/notifications/unread?sourceType=property_request');
        $employeeUnread->assertOk();
        $this->assertGreaterThanOrEqual(1, count($employeeUnread->json('data.items') ?? []));

        $employeeList = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 25,
            'offset' => 0,
            'objectTypes' => ['property_request'],
        ]);
        $employeeList->assertOk();
        $employeeMatch = collect($employeeList->json('data.actions') ?? [])
            ->first(fn ($a) => ($a['sourceId'] ?? null) === $prId);
        $this->assertNotNull($employeeMatch);
        $this->assertTrue($employeeMatch['isUnread'] ?? false);
    }

    /** @test */
    public function list_includes_is_unread_on_property_request_actions(): void
    {
        $this->requireNotificationTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employee, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);

        app(CustomersHubPropertyRequestNotifier::class)->notifyStageChanged(
            $tenant->id,
            $prId,
            'new_lead',
            'follow_up',
            (int) $tenant->id
        );

        Sanctum::actingAs($employee);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 25,
            'offset' => 0,
            'objectTypes' => ['property_request'],
        ]);

        $res->assertOk();
        $actions = collect($res->json('data.actions') ?? []);
        $match = $actions->first(fn ($a) => ($a['sourceId'] ?? null) === $prId);

        $this->assertNotNull($match);
        $this->assertTrue($match['isUnread'] ?? false);
    }

    /** @test */
    public function show_marks_notifications_read_and_returns_unread_categories(): void
    {
        $this->requireNotificationTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $this->grantPermissions($tenant, $tenant, ['customers_hub_requests.view']);
        $this->grantPermissions($employee, $tenant, ['customers_hub_requests.view']);

        $prId = $this->createPropertyRequestForUser($tenant->id);
        $notifier = app(CustomersHubPropertyRequestNotifier::class);
        $notifier->notifyStageChanged($tenant->id, $prId, 'new_lead', 'follow_up', (int) $employee->id);
        $notifier->notifyAppointmentCreated(
            $tenant->id,
            $prId,
            1,
            'Site visit',
            now()->addDay()->toDateTimeString(),
            (int) $employee->id
        );

        Sanctum::actingAs($tenant);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$prId}");
        $res->assertOk();
        $res->assertJsonPath('data.action.isUnread', false);
        $res->assertJsonPath('data.action.unreadCategories.stageChange', true);
        $res->assertJsonPath('data.action.unreadCategories.appointment', true);

        $this->assertSame(
            0,
            app(CustomersHubNotificationService::class)->unreadCountForViewer($tenant->id, 'property_request')
        );
    }
}
