<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdempotencyKeyScopeUpgradeTest extends TestCase
{
    use DatabaseTransactions;

    private function requireIdempotencySchema(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            $this->markTestSkipped('idempotency_keys table is required.');
        }

        if (!Schema::hasColumn('idempotency_keys', 'endpoint')) {
            $this->markTestSkipped('idempotency_keys.endpoint column is required.');
        }
    }

    private function createTenantUser(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    /** @test */
    public function same_user_and_key_with_different_endpoints_are_allowed(): void
    {
        $this->requireIdempotencySchema();

        $user = $this->createTenantUser();
        $key = 'IDEMP-KEY-1';

        DB::table('idempotency_keys')->insert([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'endpoint' => 'POST:/v1/messages/send',
            'request_hash' => 'hash-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('idempotency_keys')->insert([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'endpoint' => 'POST:/v1/sms-campaigns/{id}/send',
            'request_hash' => 'hash-2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            2,
            DB::table('idempotency_keys')
                ->where('user_id', $user->id)
                ->where('idempotency_key', $key)
                ->count()
        );
    }

    /** @test */
    public function same_user_key_and_endpoint_is_rejected_as_duplicate(): void
    {
        $this->requireIdempotencySchema();

        $user = $this->createTenantUser();
        $key = 'IDEMP-KEY-2';
        $endpoint = 'POST:/v1/messages/send';

        DB::table('idempotency_keys')->insert([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'endpoint' => $endpoint,
            'request_hash' => 'hash-1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('idempotency_keys')->insert([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'endpoint' => $endpoint,
            'request_hash' => 'hash-2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function different_users_can_use_same_key_and_endpoint(): void
    {
        $this->requireIdempotencySchema();

        $userA = $this->createTenantUser();
        $userB = $this->createTenantUser();
        $key = 'IDEMP-KEY-3';
        $endpoint = 'POST:/v1/messages/send';

        DB::table('idempotency_keys')->insert([
            'user_id' => $userA->id,
            'idempotency_key' => $key,
            'endpoint' => $endpoint,
            'request_hash' => 'hash-a',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('idempotency_keys')->insert([
            'user_id' => $userB->id,
            'idempotency_key' => $key,
            'endpoint' => $endpoint,
            'request_hash' => 'hash-b',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            2,
            DB::table('idempotency_keys')
                ->where('idempotency_key', $key)
                ->where('endpoint', $endpoint)
                ->count()
        );
    }

    /** @test */
    public function endpoint_defaults_to_unknown_and_row_is_queryable(): void
    {
        $this->requireIdempotencySchema();

        $user = $this->createTenantUser();
        $id = DB::table('idempotency_keys')->insertGetId([
            'user_id' => $user->id,
            'idempotency_key' => 'IDEMP-KEY-4',
            'request_hash' => 'hash-unknown',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('idempotency_keys')->where('id', $id)->first();
        $this->assertNotNull($row);
        $this->assertSame('unknown', $row->endpoint);
    }
}

