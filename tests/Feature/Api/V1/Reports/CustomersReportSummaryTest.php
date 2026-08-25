<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomersReportSummaryTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach ([
            'users',
            'api_customers',
            'users_property_requests',
            'property_request_appointments',
            'reminders',
            'customers_hub_stages',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        if (! Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
            $this->markTestSkipped('api_customers.customers_hub_stage_id column required.');
        }
    }

    private function ensureClosingStage(): void
    {
        if (DB::table('customers_hub_stages')->where('stage_id', 'closing')->exists()) {
            return;
        }

        $row = [
            'stage_id' => 'closing',
            'stage_name_ar' => 'إغلاق',
            'stage_name_en' => 'Closing',
            'color' => '#22c55e',
            'order' => 99,
            'description' => 'Closing',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('customers_hub_stages', 'user_id')) {
            $row['user_id'] = null;
        }
        if (Schema::hasColumn('customers_hub_stages', 'is_system')) {
            $row['is_system'] = true;
        }

        DB::table('customers_hub_stages')->insert($row);
    }

    private function insertClosingCustomer(int $userId, $createdAt, $updatedAt): void
    {
        $unique = (string) random_int(100000000, 999999999);
        $customer = [
            'user_id' => $userId,
            'name' => 'Closing Customer',
            'email' => "closing.{$unique}@example.test",
            'phone_number' => '9665' . $unique,
            'customers_hub_stage_id' => 'closing',
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
        if (Schema::hasColumn('api_customers', 'password')) {
            $customer['password'] = 'not-used';
        }
        DB::table('api_customers')->insert($customer);
    }

    /** @test */
    public function test_it_returns_customers_summary_when_avg_days_to_close_is_numeric_string(): void
    {
        $this->skipIfMissingSchema();
        $this->ensureClosingStage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);

        $this->insertClosingCustomer($tenant->id, now()->subDays(4), now());
        $this->insertClosingCustomer($tenant->id, now()->subDays(400), now()->subYear());

        $avgRaw = DB::table('api_customers')
            ->where('user_id', $tenant->id)
            ->whereIn('customers_hub_stage_id', ['closing'])
            ->whereBetween('updated_at', [now()->startOfMonth(), now()->endOfMonth()->endOfDay()])
            ->avg(DB::raw('DATEDIFF(NOW(), created_at)'));

        $this->assertNotNull($avgRaw);
        $this->assertTrue(is_numeric($avgRaw));

        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/v1/reports/customers/summary?preset=month')->assertOk();

        $avgDays = $res->json('data.avg_days_to_close');
        $this->assertTrue(
            is_float($avgDays) || is_int($avgDays),
            'avg_days_to_close must be a number, got ' . get_debug_type($avgDays)
        );
        $this->assertEqualsWithDelta((float) $avgRaw, (float) $avgDays, 0.15);
    }
}
