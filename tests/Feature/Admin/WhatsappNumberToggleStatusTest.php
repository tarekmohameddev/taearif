<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Regression coverage for WhatsappNumberController::toggleStatus().
 *
 * The audit columns whatsapp_addons_audit.old_status/new_status were originally
 * ENUM('pending','approved','rejected') — the addon vocabulary — while this
 * controller writes whatsapp_users statuses ('active','inactive','blocked',
 * 'not_linked'). Under STRICT_TRANS_TABLES the insert threw and the surrounding
 * transaction rolled back, silently reverting the status change itself; on a
 * permissive server it stored empty strings. Migration
 * 2026_08_12_000001_widen_whatsapp_addons_audit_status_columns widened both
 * columns to VARCHAR(32).
 *
 * These tests assert the status values actually survive the round trip, so the
 * columns cannot silently narrow again.
 */
class WhatsappNumberToggleStatusTest extends TestCase
{
    use DatabaseTransactions;

    private static int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'whatsapp_users', 'whatsapp_addons_audit'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped(
                    "taearif_testing is missing the '{$table}' table. Import the application schema into taearif_testing."
                );
            }
        }

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckStatus::class,
            \App\Http\Middleware\Demo::class,
            VerifyCsrfToken::class,
        ]);
    }

    private function createTenant(): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => null,
            'account_type' => 'tenant',
            'active' => true,
            'email' => 'tenant-' . Str::uuid() . '@example.com',
            'username' => 'tenant-' . Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNumber(int $userId, string $status = 'active'): int
    {
        $suffix = (string) ++self::$sequence;

        return (int) DB::table('whatsapp_users')->insertGetId([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+9665' . str_pad($suffix, 8, '0', STR_PAD_LEFT),
            'name' => 'Toggle Number ' . $suffix,
            'status' => $status,
            'request_status' => 'pending',
            'phone_id' => 'toggle-phone-id-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function admin(): Admin
    {
        return Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);
    }

    private function latestAuditFor(int $numberId): ?object
    {
        return DB::table('whatsapp_addons_audit')
            ->where('whatsapp_number_id', $numberId)
            ->orderByDesc('id')
            ->first();
    }

    /** @test */
    public function it_flips_an_active_number_to_inactive_and_audits_the_real_status_values(): void
    {
        $numberId = $this->createNumber($this->createTenant(), 'active');
        $admin = $this->admin();

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.whatsapp-numbers.toggle-status', $numberId));

        $response->assertOk();
        $response->assertJson(['success' => true, 'new_status' => 'inactive']);

        // The status change must actually persist — under the old schema the
        // failing audit insert rolled this back.
        $this->assertSame(
            'inactive',
            DB::table('whatsapp_users')->where('id', $numberId)->value('status'),
            'The number status should have been flipped and committed.'
        );

        $audit = $this->latestAuditFor($numberId);

        $this->assertNotNull($audit, 'toggleStatus() should write an audit row for the number.');
        $this->assertSame('number', $audit->entity_type);
        $this->assertSame($admin->id, (int) $audit->changed_by);

        // The regression itself: these were '' (or the row was absent entirely).
        $this->assertSame('active', $audit->old_status);
        $this->assertSame('inactive', $audit->new_status);
    }

    /** @test */
    public function it_flips_an_inactive_number_back_to_active_and_audits_it(): void
    {
        $numberId = $this->createNumber($this->createTenant(), 'inactive');

        $response = $this->actingAs($this->admin(), 'admin')
            ->postJson(route('admin.whatsapp-numbers.toggle-status', $numberId));

        $response->assertOk();
        $response->assertJson(['success' => true, 'new_status' => 'active']);

        $this->assertSame(
            'active',
            DB::table('whatsapp_users')->where('id', $numberId)->value('status')
        );

        $audit = $this->latestAuditFor($numberId);

        $this->assertNotNull($audit);
        $this->assertSame('inactive', $audit->old_status);
        $this->assertSame('active', $audit->new_status);
    }

    /** @test */
    public function audit_status_columns_are_wide_enough_for_every_whatsapp_user_status(): void
    {
        // Guards the schema directly: whatsapp_users.status accepts four values,
        // and every one of them must round-trip through the audit columns. A
        // narrowed column would truncate (or throw) instead of storing the value.
        foreach (['active', 'inactive', 'blocked', 'not_linked'] as $status) {
            $id = DB::table('whatsapp_addons_audit')->insertGetId([
                'whatsapp_addon_id' => null,
                'whatsapp_number_id' => null,
                'entity_type' => 'number',
                'changed_by' => null,
                'old_status' => $status,
                'new_status' => $status,
                'note' => 'schema width check',
                'changed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('whatsapp_addons_audit')->where('id', $id)->first();

            $this->assertSame($status, $row->old_status, "old_status truncated '{$status}'.");
            $this->assertSame($status, $row->new_status, "new_status truncated '{$status}'.");
        }
    }
}
