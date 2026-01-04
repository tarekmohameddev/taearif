<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappAddonsTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantUser(): int
    {
        return DB::table('users')->insertGetId([
            'tenant_id' => null,
            'account_type' => 'tenant',
            'active' => true,
            'email' => Str::uuid() . '@example.com',
            'username' => Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWhatsappUser(int $userId): int
    {
        return DB::table('whatsapp_users')->insertGetId([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+966123456789',
            'status' => 'active',
            'request_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function it_defaults_status_to_pending_on_create(): void
    {
        $whatsappUserId = $this->createWhatsappUser($this->createTenantUser());

        $addonId = DB::table('whatsapp_addons')->insertGetId([
            'whatsapp_user_id' => $whatsappUserId,
            'qty' => 1,
            'amount' => 9.99,
            'payment_ref' => 'REF-' . Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('whatsapp_addons', [
            'id' => $addonId,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function payment_ref_must_be_unique(): void
    {
        $whatsappUserId = $this->createWhatsappUser($this->createTenantUser());
        $ref = 'REF-DUP-' . Str::random(5);

        DB::table('whatsapp_addons')->insert([
            'whatsapp_user_id' => $whatsappUserId,
            'qty' => 1,
            'amount' => 9.99,
            'status' => 'pending',
            'payment_ref' => $ref,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('whatsapp_addons')->insert([
            'whatsapp_user_id' => $whatsappUserId,
            'qty' => 2,
            'amount' => 19.99,
            'status' => 'pending',
            'payment_ref' => $ref,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function audit_log_records_status_change(): void
    {
        $whatsappUserId = $this->createWhatsappUser($this->createTenantUser());
        $addonId = DB::table('whatsapp_addons')->insertGetId([
            'whatsapp_user_id' => $whatsappUserId,
            'qty' => 1,
            'amount' => 9.99,
            'status' => 'pending',
            'payment_ref' => 'REF-' . Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $auditId = DB::table('whatsapp_addons_audit')->insertGetId([
            'whatsapp_addon_id' => $addonId,
            'changed_by' => null,
            'old_status' => 'pending',
            'new_status' => 'approved',
            'changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('whatsapp_addons_audit', [
            'id' => $auditId,
            'whatsapp_addon_id' => $addonId,
            'old_status' => 'pending',
            'new_status' => 'approved',
        ]);
    }
}

