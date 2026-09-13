<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Reports;

use App\Models\User;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportsModuleFixesTest extends TestCase
{
    use DatabaseTransactions;

    private function skipUnlessTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'username' => 'report-tenant-' . uniqid(),
        ], $overrides));
    }

    private function createEmployee(User $tenant, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => 1,
        ], $overrides));
    }

    private function insertProperty(User $tenant, array $overrides = []): int
    {
        $row = array_merge([
            'user_id' => $tenant->id,
            'status' => 1,
            'purpose' => 'sale',
            'price' => 100000,
            'property_type' => 'residential',
            'featured' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);

        if (Schema::hasColumn('user_properties', 'listing_purpose')) {
            $row['listing_purpose'] = $row['listing_purpose'] ?? ($row['purpose'] ?? 'sale');
        } else {
            unset($row['listing_purpose']);
        }

        if (! Schema::hasColumn('user_properties', 'category_id')) {
            unset($row['category_id']);
        }

        return (int) DB::table('user_properties')->insertGetId($row);
    }

    private function insertCategory(string $slug, string $name): int
    {
        $existing = DB::table('api_user_categories')->where('slug', $slug)->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $row = [
            'name' => $name,
            'slug' => $slug,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('api_user_categories', 'type')) {
            $row['type'] = 'property';
        }

        return (int) DB::table('api_user_categories')->insertGetId($row);
    }

    public function test_platform_employees_returns_200_and_search_filters(): void
    {
        $this->skipUnlessTables(['users']);

        $tenant = $this->createTenant();
        $this->createEmployee($tenant, ['first_name' => 'Heureka', 'last_name' => 'Agent']);
        $this->createEmployee($tenant, ['first_name' => 'Other', 'last_name' => 'Person']);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/reports/platform/employees?preset=month')
            ->assertOk()
            ->assertJsonPath('status', true);

        $search = $this->getJson('/api/v1/reports/platform/employees?preset=month&search=heu')
            ->assertOk();

        $names = collect($search->json('data.data'))->pluck('name')->implode(' ');
        $this->assertStringContainsStringIgnoringCase('Heureka', $names);
        $this->assertStringNotContainsStringIgnoringCase('Other Person', $names);

        $this->getJson('/api/v1/reports/platform/employees?preset=month&search=zzz-no-match')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);
    }

    public function test_properties_summary_changes_with_purpose_and_type_filters(): void
    {
        $this->skipUnlessTables(['users', 'user_properties', 'api_user_categories']);

        $tenant = $this->createTenant();
        $villaId = $this->insertCategory('villa', 'Villa');
        $aptId = $this->insertCategory('apartment', 'Apartment');

        $this->insertProperty($tenant, [
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'category_id' => $aptId,
            'price' => 200000,
        ]);
        $this->insertProperty($tenant, [
            'purpose' => 'rent',
            'listing_purpose' => 'rent',
            'category_id' => $villaId,
            'price' => 30000,
        ]);
        $this->insertProperty($tenant, [
            'purpose' => 'rent',
            'listing_purpose' => 'rent',
            'category_id' => $villaId,
            'price' => 40000,
        ]);

        Sanctum::actingAs($tenant);

        $all = $this->getJson('/api/v1/reports/properties/summary?preset=year')->assertOk();
        $sale = $this->getJson('/api/v1/reports/properties/summary?preset=year&purpose=sale')->assertOk();
        $rent = $this->getJson('/api/v1/reports/properties/summary?preset=year&purpose=rent')->assertOk();
        $villa = $this->getJson('/api/v1/reports/properties/summary?preset=year&type=villa')->assertOk();

        $this->assertSame(3, $all->json('data.total_properties'));
        $this->assertSame(1, $sale->json('data.total_properties'));
        $this->assertSame(2, $rent->json('data.total_properties'));
        $this->assertSame(2, $villa->json('data.total_properties'));
        $this->assertNotEquals($sale->json('data.total_properties'), $rent->json('data.total_properties'));
        $this->assertIsInt($all->json('data.draft_count'));
    }

    public function test_agent_performance_includes_views_and_inquiries_and_hides_roster_when_filtered(): void
    {
        $this->skipUnlessTables([
            'users',
            'user_properties',
            'user_property_contents',
            'pageview_analytics',
            'users_property_requests',
            'api_user_categories',
        ]);

        $tenant = $this->createTenant();
        $agent = $this->createEmployee($tenant, ['first_name' => 'Listing', 'last_name' => 'Agent']);
        $idle = $this->createEmployee($tenant, ['first_name' => 'Idle', 'last_name' => 'Agent']);
        $villaId = $this->insertCategory('villa', 'Villa');

        $propertyId = $this->insertProperty($tenant, [
            'purpose' => 'rent',
            'listing_purpose' => 'rent',
            'category_id' => $villaId,
            'created_by' => $agent->id,
            'status' => 1,
            'price' => 50000,
        ]);

        $content = [
            'property_id' => $propertyId,
            'title' => 'Villa for rent',
            'slug' => 'villa-for-rent-' . $propertyId,
            'address' => 'Riyadh',
            'description' => 'Test villa',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('user_property_contents', 'user_id')) {
            $content['user_id'] = $tenant->id;
        }
        if (Schema::hasColumn('user_property_contents', 'language_id')) {
            $langId = 1;
            if (Schema::hasTable('user_languages')) {
                $langId = DB::table('user_languages')->where('user_id', $tenant->id)->value('id');
                if (! $langId) {
                    $langRow = [
                        'user_id' => $tenant->id,
                        'name' => 'Arabic',
                        'code' => 'ar',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    if (Schema::hasColumn('user_languages', 'is_default')) {
                        $langRow['is_default'] = 1;
                    }
                    if (Schema::hasColumn('user_languages', 'rtl')) {
                        $langRow['rtl'] = 1;
                    }
                    $langId = DB::table('user_languages')->insertGetId($langRow);
                }
            }
            $content['language_id'] = (int) $langId;
        }
        DB::table('user_property_contents')->insert($content);

        DB::table('pageview_analytics')->insert([
            'tenant_id' => $tenant->username,
            'page_slug' => $content['slug'],
            'page_path' => '/property/' . $content['slug'],
            'page_type' => 'property',
            'views_count' => 7,
            'sessions_count' => 3,
            'users_count' => 2,
            'date_bucket' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $requestRow = [
            'user_id' => $tenant->id,
            'initial_property_id' => $propertyId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users_property_requests', 'full_name')) {
            $requestRow['full_name'] = 'Buyer';
        }
        if (Schema::hasColumn('users_property_requests', 'phone')) {
            $requestRow['phone'] = '+966501234567';
        }
        if (Schema::hasColumn('users_property_requests', 'is_active')) {
            $requestRow['is_active'] = 1;
        }
        if (Schema::hasColumn('users_property_requests', 'is_read')) {
            $requestRow['is_read'] = 0;
        }
        if (Schema::hasColumn('users_property_requests', 'source')) {
            $requestRow['source'] = 'whatsapp';
        }
        DB::table('users_property_requests')->insert($requestRow);

        Sanctum::actingAs($tenant);

        $unfiltered = $this->getJson('/api/v1/reports/properties/tables/agent-performance?preset=year')
            ->assertOk();
        $names = collect($unfiltered->json('data.data'))->pluck('agent_name');
        $this->assertTrue($names->contains(fn ($n) => str_contains((string) $n, 'Listing')));
        $this->assertTrue($names->contains(fn ($n) => str_contains((string) $n, 'Idle')));

        $listing = collect($unfiltered->json('data.data'))->first(fn ($row) => ($row['agent_id'] ?? null) === $agent->id);
        $this->assertNotNull($listing);
        $this->assertGreaterThan(0, $listing['active_listings_count']);
        $this->assertSame(7, $listing['total_views_generated']);
        $this->assertSame(1, $listing['inquiries_received']);

        $filtered = $this->getJson('/api/v1/reports/properties/tables/agent-performance?preset=year&purpose=rent&type=villa')
            ->assertOk();
        $filteredIds = collect($filtered->json('data.data'))->pluck('agent_id');
        $this->assertTrue($filteredIds->contains($agent->id));
        $this->assertFalse($filteredIds->contains($idle->id));
    }

    public function test_whatsapp_number_filter_restricts_number_performance(): void
    {
        $this->skipUnlessTables(['users', 'wa_numbers']);

        $tenant = $this->createTenant();
        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+6289687246387',
            'status' => 'active',
            'name' => 'Primary',
        ]);
        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966592960339',
            'status' => 'active',
            'name' => 'Secondary',
        ]);

        Sanctum::actingAs($tenant);

        $all = $this->getJson('/api/v1/reports/whatsapp/tables/number-performance?preset=month')->assertOk();
        $this->assertSame(2, count($all->json('data.data')));

        $filtered = $this->getJson('/api/v1/reports/whatsapp/tables/number-performance?preset=month&number=%2B6289687246387')
            ->assertOk();
        $this->assertSame(1, count($filtered->json('data.data')));
        $this->assertSame('+6289687246387', $filtered->json('data.data.0.phone_number'));
    }

    public function test_whatsapp_summary_maps_approved_status_and_nulls_unlimited_quota(): void
    {
        $this->skipUnlessTables(['users', 'wa_templates', 'user_credits', 'wa_conversation_states']);

        $tenant = $this->createTenant();

        WaTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'welcome-' . uniqid(),
            'content' => 'Hello',
            'status' => 'APPROVED',
            'is_active' => true,
        ]);

        $credit = [
            'user_id' => $tenant->id,
            'total_credits' => 0,
            'used_credits' => 0,
            'monthly_limit' => 2147483647,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('user_credits', 'average_cost_per_credit')) {
            $credit['average_cost_per_credit'] = 0.05;
        }
        DB::table('user_credits')->insert($credit);

        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/v1/reports/whatsapp/summary?preset=month')->assertOk();
        $this->assertSame(1, $res->json('data.templates_total'));
        $this->assertSame(1, $res->json('data.templates_by_status.approved'));
        $this->assertNull($res->json('data.credit_quota_limit'));
    }

    public function test_platform_new_read_endpoints_respond(): void
    {
        $this->skipUnlessTables(['users', 'user_properties']);

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/reports/platform/properties/stats?preset=month')->assertOk();
        $this->getJson('/api/v1/reports/platform/performance/kpis?preset=month')->assertOk();
        $this->getJson('/api/v1/reports/platform/activity-log?preset=month')->assertOk();
        $this->getJson('/api/v1/reports/platform/messages?preset=month')->assertOk();
    }
}
