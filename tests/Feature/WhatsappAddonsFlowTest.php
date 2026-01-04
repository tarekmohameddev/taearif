<?php

namespace Tests\Feature;

use App\Domain\Admin\Models\Admin as DomainAdmin;
use App\Models\User;
use App\Models\WhatsappAddon;
use App\Models\WhatsappUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsappAddonsFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantUser(): User
    {
        $id = DB::table('users')->insertGetId([
            'tenant_id' => null,
            'account_type' => 'tenant',
            'active' => true,
            'email' => Str::uuid() . '@example.com',
            'username' => Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::findOrFail($id);
    }

    private function createWhatsappUser(int $userId): WhatsappUser
    {
        $id = DB::table('whatsapp_users')->insertGetId([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+966123456789',
            'status' => 'active',
            'request_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return WhatsappUser::findOrFail($id);
    }

    private function createAdmin(): DomainAdmin
    {
        return DomainAdmin::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'admin_' . Str::random(6),
            'email' => Str::uuid() . '@admin.test',
            'password' => Hash::make('secret'),
            'status' => true,
        ]);
    }

    /** @test */
    public function tenant_can_create_pending_addon(): void
    {
        $tenant = $this->createTenantUser();
        $whatsappUser = $this->createWhatsappUser($tenant->id);

        Sanctum::actingAs($tenant, [], 'sanctum');

        $payload = [
            'whatsapp_user_id' => $whatsappUser->id,
            'qty' => 2,
            'amount' => 19.50,
            'payment_ref' => 'REF-' . Str::random(6),
        ];

        $response = $this->postJson('/api/whatsapp/addons', $payload);

        $response->assertCreated()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_addons', [
            'whatsapp_user_id' => $whatsappUser->id,
            'status' => WhatsappAddon::STATUS_PENDING,
            'payment_ref' => $payload['payment_ref'],
        ]);
    }

    /** @test */
    public function tenant_cannot_create_addon_for_other_tenant(): void
    {
        $tenantA = $this->createTenantUser();
        $tenantB = $this->createTenantUser();
        $otherWhatsappUser = $this->createWhatsappUser($tenantB->id);

        Sanctum::actingAs($tenantA, [], 'sanctum');

        $payload = [
            'whatsapp_user_id' => $otherWhatsappUser->id,
            'qty' => 1,
            'amount' => 9.99,
            'payment_ref' => 'REF-' . Str::random(6),
        ];

        $response = $this->postJson('/api/whatsapp/addons', $payload);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('whatsapp_addons', [
            'whatsapp_user_id' => $otherWhatsappUser->id,
            'payment_ref' => $payload['payment_ref'],
        ]);
    }

    /** @test */
    public function admin_can_approve_pending_addon_and_audit_is_recorded(): void
    {
        $tenant = $this->createTenantUser();
        $whatsappUser = $this->createWhatsappUser($tenant->id);
        $addonId = DB::table('whatsapp_addons')->insertGetId([
            'whatsapp_user_id' => $whatsappUser->id,
            'qty' => 1,
            'amount' => 9.99,
            'status' => WhatsappAddon::STATUS_PENDING,
            'payment_ref' => 'REF-' . Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, [], 'admin-sanctum');

        $url = '/api/' . config('admin-api.prefix') . '/whatsapp-addons/' . $addonId . '/approve';
        $response = $this->postJson($url);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('whatsapp_addons', [
            'id' => $addonId,
            'status' => WhatsappAddon::STATUS_APPROVED,
        ]);

        $this->assertDatabaseHas('whatsapp_addons_audit', [
            'whatsapp_addon_id' => $addonId,
            'new_status' => WhatsappAddon::STATUS_APPROVED,
            'changed_by' => $admin->id,
        ]);
    }

    /** @test */
    public function admin_cannot_transition_non_pending_addon(): void
    {
        $tenant = $this->createTenantUser();
        $whatsappUser = $this->createWhatsappUser($tenant->id);
        $addonId = DB::table('whatsapp_addons')->insertGetId([
            'whatsapp_user_id' => $whatsappUser->id,
            'qty' => 1,
            'amount' => 9.99,
            'status' => WhatsappAddon::STATUS_APPROVED,
            'payment_ref' => 'REF-' . Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->createAdmin();
        Sanctum::actingAs($admin, [], 'admin-sanctum');

        $url = '/api/' . config('admin-api.prefix') . '/whatsapp-addons/' . $addonId . '/approve';
        $response = $this->postJson($url);

        $response->assertStatus(422);
    }
}

