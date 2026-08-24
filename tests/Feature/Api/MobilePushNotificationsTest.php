<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Notifications\ApnsHttp2Client;
use App\Domain\Notifications\DevicePushTokenService;
use App\Domain\Notifications\FcmHttpV1Client;
use App\Domain\Notifications\NotificationOrchestrator;
use App\Domain\Notifications\PushSender;
use App\Jobs\SendPushNotificationJob;
use App\Models\Api\UserPropertyRequest;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MobilePushNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'users', 'device_push_tokens', 'notification_preferences', 'app_notifications',
            'app_notification_recipients', 'users_property_requests', 'contact_messages',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}. Run migrations on the test DB.");
            }
        }
    }

    public function test_token_upsert_reassign_and_deactivate_api(): void
    {
        $tenant = $this->tenant();
        $other = $this->tenant();
        Sanctum::actingAs($tenant);

        $payload = [
            'token' => 'fcm-token-a',
            'provider' => 'fcm',
            'platform' => 'android',
            'device_id' => 'device-1',
            'app_version' => '1.0.0',
        ];
        $this->postJson('/api/v1/devices/push-tokens', $payload)->assertCreated();
        $this->assertDatabaseHas('device_push_tokens', [
            'user_id' => $tenant->id, 'device_id' => 'device-1', 'active' => 1,
        ]);

        Sanctum::actingAs($other);
        $this->postJson('/api/v1/devices/push-tokens', $payload)->assertCreated();
        $this->assertDatabaseHas('device_push_tokens', [
            'user_id' => $tenant->id, 'device_id' => 'device-1', 'active' => 0,
        ]);
        $this->deleteJson('/api/v1/devices/push-tokens', ['device_id' => 'device-1'])
            ->assertOk()->assertJsonPath('deactivated', 1);
    }

    public function test_preferences_default_all_on_and_gate_push_dispatch(): void
    {
        Queue::fake();
        $tenant = $this->tenant();
        $this->grant($tenant, $tenant, 'customers_hub_requests.view');
        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/notifications/preferences')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.PROPERTY_REQUEST', true);
        $this->putJson('/api/v1/notifications/preferences', ['PROPERTY_REQUEST' => false])
            ->assertOk()->assertJsonPath('data.PROPERTY_REQUEST', false);

        app(DevicePushTokenService::class)->upsert($tenant->id, $tenant->id, [
            'token' => 'token', 'provider' => 'fcm', 'platform' => 'android', 'device_id' => 'd1',
        ]);
        $request = UserPropertyRequest::query()->create([
            'user_id' => $tenant->id, 'full_name' => 'Gated', 'phone' => '+966500000001',
            'source' => 'public_form', 'is_active' => true, 'is_read' => false, 'is_archived' => false,
        ]);
        app(NotificationOrchestrator::class)->propertyRequestCreated($tenant->id, $request);

        $this->assertDatabaseHas('app_notifications', [
            'source_id' => $request->id, 'type' => NotificationOrchestrator::PROPERTY_REQUEST_CREATED,
        ]);
        Queue::assertNothingPushed();
    }

    public function test_inbox_is_tenant_isolated_and_can_mark_read(): void
    {
        $tenant = $this->tenant();
        $other = $this->tenant();
        $this->grant($tenant, $tenant, 'customers_hub_requests.view');
        $this->grant($other, $other, 'customers_hub_requests.view');
        $request = UserPropertyRequest::query()->create([
            'user_id' => $tenant->id, 'full_name' => 'Inbox', 'phone' => '+966500000002',
            'source' => 'public_form', 'is_active' => true, 'is_read' => false, 'is_archived' => false,
        ]);
        $notificationId = app(NotificationOrchestrator::class)
            ->propertyRequestCreated($tenant->id, $request);

        Sanctum::actingAs($other);
        $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('data.total', 0);
        $this->patchJson("/api/v1/notifications/{$notificationId}/read")->assertNotFound();

        Sanctum::actingAs($tenant);
        $this->getJson('/api/v1/notifications')->assertOk()
            ->assertJsonPath('data.items.0.id', $notificationId)
            ->assertJsonPath('data.items.0.deepLink', "taearif://customers-hub/requests/property_request_{$request->id}");
        $this->patchJson("/api/v1/notifications/{$notificationId}/read")->assertOk();
        $this->getJson('/api/v1/notifications/unread-count')->assertJsonPath('unreadCount', 0);
    }

    public function test_public_property_interest_and_contact_creates_persist_and_queue(): void
    {
        Queue::fake();
        $tenant = $this->tenant();
        $this->grant($tenant, $tenant, 'customers_hub_requests.view');
        $this->grant($tenant, $tenant, 'contact_messages.view');
        app(DevicePushTokenService::class)->upsert($tenant->id, $tenant->id, [
            'token' => 'token', 'provider' => 'fcm', 'platform' => 'android', 'device_id' => 'd1',
        ]);

        $public = $this->postJson('/api/v1/property-requests/public', [
            'tenant_username' => $tenant->username,
            'full_name' => 'Public Lead',
            'phone' => '+966500000003',
        ])->assertCreated();
        $this->assertDatabaseHas('app_notifications', [
            'source_id' => $public->json('data.id'),
            'type' => NotificationOrchestrator::PROPERTY_REQUEST_CREATED,
            'title' => 'طلب عقار جديد',
            'body' => 'تم إرسال طلب عقار جديد بواسطة Public Lead.',
        ]);
        $this->assertDatabaseMissing('app_notifications', [
            'source_id' => $public->json('data.id'),
            'title' => 'New property request',
        ]);

        if (! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('user_properties table is required for the interest hook.');
        }
        $property = Property::query()->create([
            'user_id' => $tenant->id, 'featured_image' => 'test.jpg', 'purpose' => 'sale',
            'property_status' => 'available', 'area' => 100, 'completion_status' => 'complete',
            'status' => 1, 'property_type' => 'residential',
        ]);
        $interest = $this->postJson('/api/v1/property-requests/interest', [
            'tenant_username' => $tenant->username, 'property_id' => $property->id,
            'full_name' => 'Interest Lead', 'phone' => '+966500000004',
        ])->assertCreated();
        $this->assertDatabaseHas('app_notifications', [
            'source_id' => $interest->json('data.request_id'),
            'type' => NotificationOrchestrator::PROPERTY_REQUEST_CREATED,
            'title' => 'طلب عقار جديد',
            'body' => 'تم إرسال طلب عقار جديد بواسطة Interest Lead.',
        ]);

        $contact = $this->postJson("/api/v1/tenant-website/{$tenant->username}/contact-messages", [
            'source' => 'contact_form_section', 'customer_name' => 'Contact Lead',
            'message' => 'Please contact me about a property.',
        ])->assertCreated();
        $this->assertDatabaseHas('app_notifications', [
            'source_type' => 'contact_message',
            'source_id' => $contact->json('data.id'),
            'request_id' => 'contact_message_'.$contact->json('data.id'),
            'title' => 'رسالة تواصل جديدة',
            'body' => 'تم استلام رسالة تواصل جديدة من Contact Lead.',
        ]);
        $this->assertDatabaseMissing('app_notifications', [
            'source_id' => $contact->json('data.id'),
            'title' => 'New contact message',
        ]);
        Queue::assertPushed(SendPushNotificationJob::class, 3);

        Sanctum::actingAs($tenant);
        $inbox = $this->getJson('/api/v1/notifications')->assertOk();
        $titles = collect($inbox->json('data.items') ?? [])->pluck('title')->all();
        $this->assertContains('طلب عقار جديد', $titles);
        $this->assertContains('رسالة تواصل جديدة', $titles);
        $this->assertNotContains('New property request', $titles);
        $this->assertNotContains('New contact message', $titles);
        $bodies = collect($inbox->json('data.items') ?? [])->pluck('body')->implode(' ');
        $this->assertStringNotContainsString('A new property request', $bodies);
        $this->assertStringNotContainsString('A new contact message', $bodies);
    }

    public function test_invalid_provider_token_is_deactivated(): void
    {
        $tenant = $this->tenant();
        $row = app(DevicePushTokenService::class)->upsert($tenant->id, $tenant->id, [
            'token' => 'invalid', 'provider' => 'fcm', 'platform' => 'android', 'device_id' => 'd1',
        ]);
        $fcm = Mockery::mock(FcmHttpV1Client::class);
        $fcm->shouldReceive('send')->once()->andReturn(['ok' => false, 'invalid' => true, 'status' => 404]);
        $sender = new PushSender($fcm, Mockery::mock(ApnsHttp2Client::class), app(DevicePushTokenService::class));
        $sender->send((object) $row, [
            'id' => 1, 'type' => 'SYSTEM', 'category' => 'SYSTEM', 'title' => 't', 'body' => 'b',
            'deepLink' => 'taearif://test', 'entityType' => 'test', 'entityId' => 1,
            'requestId' => 'test_1', 'customerId' => null,
        ], ['sound' => true, 'badge' => true]);
        $this->assertDatabaseHas('device_push_tokens', ['id' => $row['id'], 'active' => 0]);
    }

    private function tenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant', 'tenant_id' => null, 'username' => 'push-'.Str::random(10),
        ]);
    }

    private function grant(User $user, User $tenant, string $name): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();
        try {
            $permission = Permission::findByName($name, 'sanctum');
        } catch (\Throwable $e) {
            $permission = Permission::create([
                'name' => $name, 'guard_name' => 'sanctum', 'team_id' => $tenant->id,
            ]);
        }
        $user->givePermissionTo($permission);
        $registrar->forgetCachedPermissions();
    }
}
