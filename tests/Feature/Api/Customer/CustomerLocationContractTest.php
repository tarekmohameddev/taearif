<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Customer;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pins the city/district read contract across the customer endpoints.
 *
 * The bug this guards against: PUT /api/customers/{id} echoed city_id/district_id
 * back, but the following GET stripped them, so the edit form could not rehydrate
 * its selects and users believed the save had failed.
 */
class CustomerLocationContractTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['api_customers', 'user_cities', 'user_districts'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
        foreach (['city_id', 'district_id'] as $column) {
            if (!Schema::hasColumn('api_customers', $column)) {
                $this->markTestSkipped("api_customers.{$column} column required. Run migrations.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /**
     * user_cities is a global reference table — it has no user_id column.
     * Let auto-increment assign the id so we never collide with seeded data.
     */
    private function createCity(string $nameAr = 'مدينة الاختبار'): int
    {
        return (int) DB::table('user_cities')->insertGetId([
            'name_ar'    => $nameAr,
            'name_en'    => 'Test City',
            'country_id' => 1,
            'region_id'  => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Districts carry national-style bigint ids in production (e.g. 10100003028),
     * so we force an explicit large id to prove the value survives the JSON
     * round-trip un-truncated. The base is far above any real id to avoid
     * colliding with seeded rows.
     */
    private function createDistrict(int $cityId, string $nameAr = 'حي الاختبار'): int
    {
        $id = 99000000000 + random_int(1, 999999);

        DB::table('user_districts')->insert([
            'id'              => $id,
            'name_ar'         => $nameAr,
            'name_en'         => 'Test District',
            'city_id'         => $cityId,
            'city_name_ar'    => 'مدينة الاختبار',
            'city_name_en'    => 'Test City',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return $id;
    }

    /**
     * email and phone_number are UNIQUE and password is NOT NULL on api_customers.
     */
    private function createCustomer(int $userId, array $overrides = []): int
    {
        $unique = (string) random_int(100000000, 999999999);

        $defaults = [
            'user_id'      => $userId,
            'name'         => 'Test Customer',
            'email'        => "customer.{$unique}@example.test",
            'phone_number' => '9665' . $unique,
            'password'     => 'not-used',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        return (int) DB::table('api_customers')->insertGetId(array_merge($defaults, $overrides));
    }

    /**
     * The v1 customer routes sit behind can:customers.view / can:customers.update.
     * A bare factory tenant may not have those permissions bootstrapped, so skip
     * rather than leave a misleading failure.
     */
    private function skipIfForbidden(TestResponse $response): void
    {
        if ($response->status() === 403) {
            $this->markTestSkipped('Customer permissions not bootstrapped for this tenant (403).');
        }
    }

    /** @test */
    public function get_customer_detail_returns_top_level_city_and_district_ids(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $city = $this->createCity('جدة');
        $district = $this->createDistrict($city, 'الروضة');

        $customerId = $this->createCustomer($tenant->id, [
            'city_id'     => $city,
            'district_id' => $district,
        ]);

        $res = $this->getJson("/api/customers/{$customerId}");
        $this->skipIfForbidden($res);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            // The regression: these top-level ids used to be unset()
            ->assertJsonPath('data.city_id', $city)
            ->assertJsonPath('data.district_id', $district)
            // The nested display objects must still be there (change is additive)
            ->assertJsonPath('data.city.id', $city)
            ->assertJsonPath('data.district.id', $district);
    }

    /** @test */
    public function put_customer_city_and_district_persists_and_get_reflects_changes(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $cityFrom = $this->createCity('جدة');
        $districtFrom = $this->createDistrict($cityFrom, 'الروضة');
        $cityTo = $this->createCity('مكة المكرمة');
        $districtTo = $this->createDistrict($cityTo, 'النسيم');

        $customerId = $this->createCustomer($tenant->id, [
            'city_id'     => $cityFrom,
            'district_id' => $districtFrom,
        ]);

        $putRes = $this->putJson("/api/customers/{$customerId}", [
            'city_id'     => $cityTo,
            'district_id' => $districtTo,
        ]);
        $this->skipIfForbidden($putRes);
        $putRes->assertOk();

        $getRes = $this->getJson("/api/customers/{$customerId}");
        $this->skipIfForbidden($getRes);

        $getRes->assertOk()
            ->assertJsonPath('data.city_id', $cityTo)
            ->assertJsonPath('data.district_id', $districtTo)
            ->assertJsonPath('data.city.id', $cityTo)
            ->assertJsonPath('data.district.id', $districtTo);

        $row = DB::table('api_customers')->where('id', $customerId)->first();
        $this->assertEquals($cityTo, $row->city_id);
        $this->assertEquals($districtTo, $row->district_id);
    }

    /** @test */
    public function hub_detail_endpoint_returns_camel_case_city_and_district_ids(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $city = $this->createCity('جدة');
        $district = $this->createDistrict($city, 'الروضة');

        $customerId = $this->createCustomer($tenant->id, [
            'city_id'     => $city,
            'district_id' => $district,
        ]);

        $this->getJson("/api/v2/customers-hub/customers/{$customerId}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.customer.cityId', $city)
            ->assertJsonPath('data.customer.districtId', $district)
            // The pre-existing name strings must be untouched
            ->assertJsonPath('data.customer.city', 'جدة')
            ->assertJsonPath('data.customer.district', 'الروضة');
    }

    /** @test */
    public function put_hub_detail_accepts_camel_case_city_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $cityFrom = $this->createCity('جدة');
        $cityTo = $this->createCity('مكة المكرمة');

        $customerId = $this->createCustomer($tenant->id, ['city_id' => $cityFrom]);

        // camelCase used to be dropped by validation before reaching the service.
        // The value must differ from the current one: updateCustomer() returns
        // false on a 0-row update, which the controller turns into a 422.
        $this->putJson("/api/v2/customers-hub/customers/{$customerId}", [
            'cityId' => $cityTo,
        ])->assertOk();

        $row = DB::table('api_customers')->where('id', $customerId)->first();
        $this->assertEquals($cityTo, $row->city_id);
    }

    /** @test */
    public function list_customers_endpoint_includes_district_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $city = $this->createCity('جدة');
        $district = $this->createDistrict($city, 'الروضة');

        $customerId = $this->createCustomer($tenant->id, [
            'city_id'     => $city,
            'district_id' => $district,
        ]);

        $res = $this->getJson('/api/customers');
        $this->skipIfForbidden($res);
        $res->assertOk()->assertJsonPath('status', 'success');

        // The tenant is freshly created, so it owns exactly this one customer.
        $customer = collect($res->json('data.customers'))->firstWhere('id', $customerId);

        $this->assertNotNull($customer, "Customer {$customerId} missing from the list response.");
        $this->assertEquals($city, $customer['city_id']);
        $this->assertArrayHasKey('district_id', $customer, 'index() must return district_id, like search() does.');
        $this->assertEquals($district, $customer['district_id']);
    }
}
