<?php

namespace Tests\Feature\Admin;

use App\Models\Api\ApiThemeSettings;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThemesValidationTest extends TestCase
{
    use WithoutMiddleware;

    private static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$migrated) {
            // Ensure themes table exists in the test DB.
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2025_03_26_211215_create_api_themes_settings_table.php',
                '--force' => true,
            ]);

            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_01_08_165056_add_theme_pricing_fields_to_api_themes_settings_table.php',
                '--force' => true,
            ]);

            self::$migrated = true;
        }
    }

    /** @test */
    public function admin_theme_update_requires_description()
    {
        $theme = ApiThemeSettings::create([
            'theme_id' => 'test_' . Str::random(8),
            'name' => 'Test Theme',
            'description' => 'Initial description',
            'thumbnail' => 'themes/test/thumb.png',
            'category' => 'test',
            'active' => false,
            'popular' => false,
            'is_free' => true,
            'is_enabled' => true,
            'price' => null,
            'currency' => 'SAR',
        ]);

        $response = $this
            ->from('/admin/themes/' . $theme->theme_id . '/edit')
            ->post(route('admin.themes.update'), [
                'theme_id' => $theme->theme_id,
                'name' => 'Updated Theme',
                'description' => '',
                'thumbnail' => 'themes/test/thumb.png',
                'category' => 'test',
                'is_free' => 1,
                'is_enabled' => 1,
                'popular' => 0,
                'currency' => 'SAR',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['description']);
    }

    /** @test */
    public function admin_theme_store_requires_description()
    {
        $response = $this
            ->from('/admin/themes/create')
            ->post(route('admin.themes.store'), [
                'theme_id' => 'test_' . Str::random(8),
                'name' => 'Test Theme',
                'description' => '',
                'thumbnail' => 'themes/test/thumb.png',
                'category' => 'test',
                'is_free' => 1,
                'is_enabled' => 1,
                'popular' => 0,
                'currency' => 'SAR',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['description']);
    }

    /** @test */
    public function paid_theme_requires_price_on_update()
    {
        $theme = ApiThemeSettings::create([
            'theme_id' => 'paid_' . Str::random(8),
            'name' => 'Paid Theme',
            'description' => 'Initial description',
            'thumbnail' => 'themes/test/thumb.png',
            'category' => 'test',
            'active' => false,
            'popular' => false,
            'is_free' => false,
            'is_enabled' => true,
            'price' => 10.00,
            'currency' => 'SAR',
        ]);

        $response = $this
            ->from('/admin/themes/' . $theme->theme_id . '/edit')
            ->post(route('admin.themes.update'), [
                'theme_id' => $theme->theme_id,
                'name' => 'Paid Theme Updated',
                'description' => 'Still valid',
                'thumbnail' => 'themes/test/thumb.png',
                'category' => 'test',
                'is_free' => 0,
                'is_enabled' => 1,
                'popular' => 0,
                'price' => null,
                'currency' => 'SAR',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['price']);
    }
}

