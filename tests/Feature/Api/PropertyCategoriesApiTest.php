<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\User\RealestateManagement\ApiUserCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyCategoriesApiTest extends TestCase
{
    use DatabaseTransactions;

    private const TEST_USER_ID = 1430;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skipIfMissingSchema();

        $this->user = User::find(self::TEST_USER_ID);
        if (! $this->user) {
            $this->markTestSkipped('Test user (id ' . self::TEST_USER_ID . ') not found in DB.');
        }
    }

    protected function skipIfMissingSchema(): void
    {
        foreach (['users', 'api_user_categories'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing table: {$table}. Restore taearif_testing from dump or run migrations.");
            }
        }
    }

    /** @test */
    public function it_returns_global_property_categories_including_duplex_townhouse_room(): void
    {
        $new = [
            'duplex' => 'دوبلكس',
            'townhouse' => 'تاون هاوس',
            'room' => 'غرفة',
        ];

        $existingBefore = ApiUserCategory::query()
            ->where('is_active', true)
            ->where('type', 'property')
            ->whereNotIn('slug', array_keys($new))
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        foreach ($new as $slug => $name) {
            ApiUserCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'type' => 'property', 'is_active' => 1, 'icon' => null]
            );
        }

        Cache::forget('api_property_categories_list');

        Sanctum::actingAs($this->user);
        $resp = $this->getJson('/api/properties/categories');

        $resp->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJsonPath('success', true);

        $names = collect($resp->json('data'))->pluck('name')->filter()->values()->all();

        foreach (array_values($new) as $expectedName) {
            $this->assertContains($expectedName, $names);
        }

        if (count($existingBefore) > 0) {
            $this->assertContains($existingBefore[0], $names);
        }

        // Smoke-check the public direct route uses the same controller method.
        Cache::forget('api_property_categories_list');
        $direct = $this->getJson('/api/v1/tenant-website/acme/properties/categories/direct');
        $direct->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJsonPath('success', true);

        $directNames = collect($direct->json('data'))->pluck('name')->filter()->values()->all();
        foreach (array_values($new) as $expectedName) {
            $this->assertContains($expectedName, $directNames);
        }
    }

    /** @test */
    public function it_is_idempotent_by_slug_and_does_not_create_duplicates(): void
    {
        $rows = [
            ['slug' => 'duplex', 'name' => 'دوبلكس'],
            ['slug' => 'townhouse', 'name' => 'تاون هاوس'],
            ['slug' => 'room', 'name' => 'غرفة'],
        ];

        foreach ([1, 2] as $pass) {
            foreach ($rows as $row) {
                ApiUserCategory::updateOrCreate(
                    ['slug' => $row['slug']],
                    ['name' => $row['name'], 'type' => 'property', 'is_active' => 1, 'icon' => null]
                );
            }
        }

        $this->assertSame(
            3,
            ApiUserCategory::query()->whereIn('slug', ['duplex', 'townhouse', 'room'])->count()
        );
    }
}

