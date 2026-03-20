<?php

namespace Tests\Feature\Api\Onboarding;

use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Models\User\Language;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OnboardingWebsiteLayoutColorsTest extends TestCase
{
    public function test_onboarding_merges_branding_colors_into_existing_website_layout_data(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('user_languages')
            || !Schema::hasTable('tenant_website_layouts')
            || !Schema::hasTable('tenant_global_components')) {
            $this->markTestSkipped('Required tables are missing for onboarding layout color test.');
        }

        config(['app.tenant_website_api_url' => 'https://tenant-template.test/defaults']);

        Http::fake([
            'https://tenant-template.test/defaults' => Http::response([
                'componentSettings' => [
                    'home' => [],
                ],
                'globalComponentsData' => [],
                // Intentionally no WebsiteLayout to test merge into existing row.
            ], 200),
        ]);

        $user = User::factory()->tenant()->create([
            'email' => 'onboarding-layout-test-' . uniqid('', true) . '@example.com',
        ]);

        Language::query()->create([
            'user_id' => $user->id,
            'name' => 'Arabic',
            'code' => 'ar',
            'is_default' => 1,
            'rtl' => 1,
        ]);

        TenantWebsiteLayout::query()->create([
            'user_id' => $user->id,
            'data' => [
                'existing' => ['keep' => true],
                'branding' => [
                    'logo' => 'https://old-logo.example/logo.png',
                ],
            ],
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Acme Realty',
            'category' => 'realestate',
            'colors' => [
                'primary' => '#111111',
                'secondary' => '#222222',
                'accent' => '#333333',
            ],
            'logo' => 'https://cdn.example.com/new-logo.png',
            'favicon' => 'https://cdn.example.com/favicon.png',
            'address' => 'Riyadh',
            'email' => 'contact@acme-realty.test',
            'phone' => '+966500000000',
            'workingHours' => '9-5',
            'valLicense' => 'ABC-123',
        ];

        $response = $this->postJson('/api/onboarding', $payload);
        $response->assertOk();

        $layout = TenantWebsiteLayout::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($layout);
        $this->assertIsArray($layout->data);

        $this->assertSame(true, data_get($layout->data, 'existing.keep'));
        $this->assertSame('#111111', data_get($layout->data, 'branding.colors.primary'));
        $this->assertSame('#222222', data_get($layout->data, 'branding.colors.secondary'));
        $this->assertSame('#333333', data_get($layout->data, 'branding.colors.accent'));
        $this->assertSame('Riyadh', data_get($layout->data, 'companyInfo.address'));
        $this->assertSame('9-5', data_get($layout->data, 'companyInfo.workingHours'));
        $this->assertSame('contact@acme-realty.test', data_get($layout->data, 'companyInfo.email'));
        $this->assertSame('+966500000000', data_get($layout->data, 'companyInfo.phone'));
        $this->assertSame('ABC-123', data_get($layout->data, 'companyInfo.valLicense'));

        $globals = TenantGlobalComponent::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($globals);
        $this->assertSame(
            'contact@acme-realty.test',
            data_get($globals->data, 'footer.content.contactInfo.email')
        );
    }
}

