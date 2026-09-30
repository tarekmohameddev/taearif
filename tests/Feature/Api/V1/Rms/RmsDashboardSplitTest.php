<?php

namespace Tests\Feature\Api\V1\Rms;

use App\Services\Rms\DashboardReadService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RmsDashboardSplitTest extends TestCase
{
    private int $ownerId = 987654;
    private int $rentalId;
    private int $contractId;
    private int $propertyId;

    protected function setUp(): void
    {
        parent::setUp();

        if (env('RMS_SPLIT_ALLOW_ISOLATED') !== '1') {
            $this->markTestSkipped('RMS split tests require the explicit isolated MySQL database taearif_rms_split_isolated.');
        }

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'taearif_rms_split_isolated',
        ]);
        DB::purge('mysql');
        DB::beginTransaction();
        $this->propertyId = (int) DB::table('user_properties')->insertGetId([
            'user_id' => $this->ownerId, 'purpose' => 'sale', 'listing_purpose' => 'rent',
            'property_status' => 'for_rent', 'property_type' => 'residential', 'area' => 100,
            'status' => 1, 'completion_status' => 'complete', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('user_languages')->insert([
            'user_id' => $this->ownerId, 'name' => 'English', 'code' => 'en', 'is_default' => 1,
            'rtl' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $languageId = (int) DB::getPdo()->lastInsertId();
        DB::table('user_property_contents')->insert([
            'user_id' => $this->ownerId, 'property_id' => $this->propertyId, 'language_id' => $languageId,
            'category_id' => 1, 'city_id' => 1, 'title' => 'Isolated RMS Unit', 'slug' => 'isolated-rms-unit-' . $this->propertyId,
            'address' => 'Test', 'description' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->rentalId = (int) DB::table('rm_rentals')->insertGetId([
            'user_id' => $this->ownerId, 'unit_id' => $this->propertyId, 'tenant_full_name' => 'RMS Tenant',
            'tenant_phone' => '0500000000', 'currency' => 'SAR', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->contractId = (int) DB::table('rm_contracts')->insertGetId([
            'user_id' => $this->ownerId, 'rental_id' => $this->rentalId,
            'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->addDays(10)->toDateString(),
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('rm_payment_installments')->insert([
            ['user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'contract_id' => $this->contractId,
                'sequence_no' => 1, 'due_date' => now()->subDays(5)->toDateString(), 'amount' => 1000,
                'status' => 'partial', 'paid_amount' => 200, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'contract_id' => $this->contractId,
                'sequence_no' => 2, 'due_date' => now()->addDays(5)->toDateString(), 'amount' => 500,
                'status' => 'pending', 'paid_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('rm_maintenance_tickets')->insert([
            'user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'unit_id' => $this->propertyId,
            'category' => 'electrical', 'priority' => 'high', 'title' => 'RMS test',
            'description' => 'RMS test', 'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    /** @test */
    public function all_split_read_contracts_return_expected_shapes_and_formulas(): void
    {
        $service = app(DashboardReadService::class);
        $stats = $service->stats($this->ownerId);
        $this->assertSame('SAR', $stats['currency']);
        $this->assertSame(1, $stats['counts']['ongoing_rentals']);
        $this->assertSame(800.0, $stats['overdue_payments']['total_overdue_amount']);

        $ongoing = $service->ongoingRentals($this->ownerId, []);
        $this->assertSame('Isolated RMS Unit', $ongoing['items'][0]['property']['name']);
        $this->assertSame(800.0, $ongoing['items'][0]['next_payment']['payment_details']['remaining_amount']);

        $due = $service->paymentsDueList($this->ownerId, []);
        $this->assertSame(800.0, $due['items'][0]['payment_details']['remaining_amount']);
        $this->assertSame(1300.0, $service->paymentsDueSummary($this->ownerId)['this_year']['total_outstanding']);

        $overdue = $service->overduePayments($this->ownerId, []);
        $this->assertSame(800.0, $overdue['items'][0]['remaining_amount']);
        $this->assertSame(1, $service->expiringContracts($this->ownerId, [])['pagination']['total']);
        $this->assertSame(1, $service->maintenance($this->ownerId, [])['pagination']['total']);
    }

    /** @test */
    public function pagination_is_sql_backed_and_beyond_last_page_is_empty(): void
    {
        $service = app(DashboardReadService::class);
        $page = $service->ongoingRentals($this->ownerId, ['page' => 2, 'per_page' => 1]);
        $this->assertSame([], $page['items']->all());
        $this->assertFalse($page['pagination']['has_more']);
    }

    /** @test */
    public function inconsistent_foreign_owner_rows_are_not_exposed(): void
    {
        $otherRental = DB::table('rm_rentals')->insertGetId([
            'user_id' => $this->ownerId + 1, 'unit_id' => $this->propertyId,
            'tenant_full_name' => 'Other', 'tenant_phone' => '0510000000', 'currency' => 'SAR',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('rm_payment_installments')->insert([
            'user_id' => $this->ownerId, 'rental_id' => $otherRental, 'contract_id' => $this->contractId,
            'sequence_no' => 99, 'due_date' => now()->subDay()->toDateString(), 'amount' => 999,
            'status' => 'overdue', 'paid_amount' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $items = app(DashboardReadService::class)->overduePayments($this->ownerId, [])['items'];
        $this->assertCount(1, $items);
        $this->assertSame($this->rentalId, $items[0]['rental_id']);
    }

    /** @test */
    public function ongoing_rentals_does_not_issue_queries_per_returned_rental(): void
    {
        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        app(DashboardReadService::class)->ongoingRentals($this->ownerId, []);

        $this->assertLessThanOrEqual(10, $queries, 'Ongoing rentals exceeded the bounded query budget: ' . $queries);
    }

    /** @test */
    public function capped_payment_values_and_paid_void_rows_follow_shared_overdue_rules(): void
    {
        DB::table('rm_payment_installments')->insert([
            ['user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'contract_id' => $this->contractId,
                'sequence_no' => 10, 'due_date' => now()->subDay()->toDateString(), 'amount' => 100,
                'status' => 'overdue', 'paid_amount' => 500, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'contract_id' => $this->contractId,
                'sequence_no' => 11, 'due_date' => now()->subDay()->toDateString(), 'amount' => 100,
                'status' => 'paid', 'paid_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $this->ownerId, 'rental_id' => $this->rentalId, 'contract_id' => $this->contractId,
                'sequence_no' => 12, 'due_date' => now()->subDay()->toDateString(), 'amount' => 100,
                'status' => 'void', 'paid_amount' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $summary = app(DashboardReadService::class)->overduePaymentsSummary($this->ownerId);
        $this->assertSame(1, $summary['total_overdue_count']);
        $this->assertSame(800.0, $summary['total_overdue_amount']);
    }

    /** @test */
    public function pagination_returns_page_two_for_26_rows_and_is_deterministic(): void
    {
        for ($i = 0; $i < 25; $i++) {
            DB::table('rm_rentals')->insert([
                'user_id' => $this->ownerId, 'tenant_full_name' => 'Tenant ' . $i,
                'tenant_phone' => '050000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'currency' => 'SAR', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $service = app(DashboardReadService::class);
        $first = $service->ongoingRentals($this->ownerId, ['page' => 1, 'per_page' => 25]);
        $second = $service->ongoingRentals($this->ownerId, ['page' => 2, 'per_page' => 25]);

        $this->assertCount(25, $first['items']);
        $this->assertCount(1, $second['items']);
        $this->assertTrue($first['pagination']['has_more']);
        $this->assertFalse($second['pagination']['has_more']);
        $this->assertNotSame($first['items'][24]['id'], $second['items'][0]['id']);
    }

    /** @test */
    public function standalone_maintenance_is_included_and_request_rules_reject_invalid_values(): void
    {
        DB::table('rm_maintenance_tickets')->insert([
            'user_id' => $this->ownerId, 'rental_id' => null, 'unit_id' => null, 'building_id' => null,
            'category' => 'general', 'priority' => 'low', 'title' => 'Standalone',
            'description' => 'Standalone', 'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(DashboardReadService::class);
        $this->assertSame(1, $service->maintenance($this->ownerId, ['status' => 'in_progress'])['pagination']['total']);

        $request = app(\App\Http\Requests\Api\V1\Rms\RmsDashboardPaymentsDueRequest::class);
        $this->assertFalse(Validator::make(['period' => 'bad', 'page' => 0, 'per_page' => 101], $request->rules())->passes());
        $request = app(\App\Http\Requests\Api\V1\Rms\RmsDashboardExpiringContractsRequest::class);
        $this->assertFalse(Validator::make(['days' => 366], $request->rules())->passes());
        $request = app(\App\Http\Requests\Api\V1\Rms\RmsDashboardMaintenanceRequest::class);
        $this->assertFalse(Validator::make(['status' => 'bad'], $request->rules())->passes());
    }

    /** @test */
    public function split_routes_keep_sanctum_and_permission_middleware(): void
    {
        foreach ([
            'rms.dashboard.stats', 'rms.dashboard.ongoing-rentals', 'rms.dashboard.payments-due.summary',
            'rms.dashboard.payments-due', 'rms.dashboard.overdue-payments.summary',
            'rms.dashboard.overdue-payments', 'rms.dashboard.expiring-contracts', 'rms.dashboard.maintenance',
        ] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:sanctum', $middleware);
            $this->assertContains('can:rentals.view', $middleware);
        }
    }

    /** @test */
    public function unauthenticated_requests_are_rejected_by_every_split_route(): void
    {
        foreach ([
            '/api/v1/rms/dashboard/stats',
            '/api/v1/rms/dashboard/ongoing-rentals',
            '/api/v1/rms/dashboard/payments-due/summary',
            '/api/v1/rms/dashboard/payments-due',
            '/api/v1/rms/dashboard/overdue-payments/summary',
            '/api/v1/rms/dashboard/overdue-payments',
            '/api/v1/rms/dashboard/expiring-contracts',
            '/api/v1/rms/dashboard/maintenance',
        ] as $uri) {
            $this->getJson($uri)->assertUnauthorized();
        }
    }

    /** @test */
    public function expiring_contracts_use_inclusive_riyadh_boundaries_and_exclude_expired_rows(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 27, 12, 0, 0, 'Asia/Riyadh'));
        $yesterday = DB::table('rm_contracts')->insertGetId([
            'user_id' => $this->ownerId, 'rental_id' => $this->rentalId,
            'start_date' => '2026-01-01', 'end_date' => '2026-09-26', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $today = DB::table('rm_contracts')->insertGetId([
            'user_id' => $this->ownerId, 'rental_id' => $this->rentalId,
            'start_date' => '2026-01-01', 'end_date' => '2026-09-27', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $edge = DB::table('rm_contracts')->insertGetId([
            'user_id' => $this->ownerId, 'rental_id' => $this->rentalId,
            'start_date' => '2026-01-01', 'end_date' => '2027-09-27', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $items = app(DashboardReadService::class)->expiringContracts($this->ownerId, ['days' => 365])['items'];
        $ids = $items->pluck('id')->all();
        $this->assertNotContains($yesterday, $ids);
        $this->assertContains($today, $ids);
        $this->assertContains($edge, $ids);
        Carbon::setTestNow();
    }

    /** @test */
    public function soft_deleted_rentals_are_excluded_and_non_sar_audit_is_explicit(): void
    {
        DB::table('rm_rentals')->insert([
            'user_id' => $this->ownerId, 'tenant_full_name' => 'Deleted',
            'tenant_phone' => '0520000000', 'currency' => 'SAR', 'status' => 'active',
            'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(1, app(DashboardReadService::class)->stats($this->ownerId)['counts']['ongoing_rentals']);

        DB::table('rm_rentals')->insert([
            'user_id' => $this->ownerId, 'tenant_full_name' => 'Non SAR',
            'tenant_phone' => '0530000000', 'currency' => 'USD', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $audit = DB::table('rm_rentals')
            ->whereNull('deleted_at')
            ->selectRaw("UPPER(COALESCE(NULLIF(TRIM(currency), ''), 'SAR')) currency_code, COUNT(*) rental_count")
            ->groupBy('currency_code')
            ->pluck('rental_count', 'currency_code');
        $this->assertArrayHasKey('USD', $audit->all());
        $this->assertGreaterThan(0, (int) $audit['USD']);
    }
}
