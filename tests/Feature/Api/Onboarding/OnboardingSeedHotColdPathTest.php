<?php

namespace Tests\Feature\Api\Onboarding;

use App\Jobs\ReseedTenantWebsiteJob;
use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Models\User\Language;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Hot path: pages+globals+layout exist → sync merge/inject, no ReseedTenantWebsiteJob / Mandhoor.
 * Cold path: incomplete website → ReseedTenantWebsiteJob still dispatched (afterCommit).
 *
 * Intentionally avoids DatabaseTransactions so DB::afterCommit callbacks fire.
 */
class OnboardingSeedHotColdPathTest extends TestCase
{
    private function onboardingPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Hot Path Realty',
            'category' => 'realestate',
            'colors' => [
                'primary' => '#aa1111',
                'secondary' => '#bb2222',
                'accent' => '#cc3333',
            ],
            'logo' => 'https://cdn.example.com/hot-path-logo.png',
            'favicon' => 'https://cdn.example.com/favicon.png',
            'address' => 'Jeddah',
            'email' => 'hotpath@example.test',
            'phone' => '+966511122233',
            'workingHours' => '8-6',
            'valLicense' => 'HOT-99',
        ], $overrides);
    }

    private function createTenantWithLanguage(): User
    {
        $user = User::factory()->tenant()->create([
            'email' => 'onboarding-path-' . uniqid('', true) . '@example.com',
            'phone' => '+9665' . substr((string) random_int(10000000, 99999999), 0, 8),
        ]);

        Language::query()->create([
            'user_id' => $user->id,
            'name' => 'Arabic',
            'code' => 'ar',
            'is_default' => 1,
            'rtl' => 1,
        ]);

        return $user;
    }

    public function test_onboarding_hot_path_merges_injects_without_mandhoor_or_reseed_job(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('user_languages')
            || !Schema::hasTable('tenant_pages')
            || !Schema::hasTable('tenant_global_components')
            || !Schema::hasTable('tenant_website_layouts')) {
            $this->markTestSkipped('Required tables are missing for onboarding hot path test.');
        }

        config(['app.tenant_website_api_url' => 'https://tenant-template.test/defaults']);

        Http::fake([
            'https://tenant-template.test/defaults' => Http::response(['should_not_be_called' => true], 200),
        ]);

        Bus::fake([ReseedTenantWebsiteJob::class]);

        $user = $this->createTenantWithLanguage();

        TenantPage::query()->create([
            'user_id' => $user->id,
            'page_id' => 'home',
            'components' => [
                [
                    'id' => 'hero-1',
                    'position' => 0,
                    'type' => 'hero',
                    'data' => [
                        'logo' => [
                            'image' => 'https://old.example/logo.png',
                            'text' => 'تعاريف العقارية',
                        ],
                    ],
                ],
            ],
        ]);

        TenantGlobalComponent::query()->create([
            'user_id' => $user->id,
            'data' => [
                'header' => [
                    'logo' => [
                        'image' => 'https://old.example/header-logo.png',
                        'text' => 'تعاريف العقارية',
                    ],
                ],
                'footer' => [
                    'content' => [
                        'contactInfo' => [
                            'email' => 'old@example.test',
                        ],
                    ],
                ],
            ],
        ]);

        TenantWebsiteLayout::query()->create([
            'user_id' => $user->id,
            'data' => [
                'existing' => ['keep' => true],
                'branding' => [
                    'logo' => 'https://old.example/layout-logo.png',
                ],
                'companyInfo' => [
                    'logo' => 'https://old.example/company-logo.png',
                ],
            ],
        ]);

        Sanctum::actingAs($user);

        $payload = $this->onboardingPayload([
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        $response = $this->postJson('/api/onboarding', $payload);
        $response->assertOk();

        Http::assertNotSent(function ($request) {
            return str_contains($request->url(), 'tenant-template.test');
        });

        Bus::assertNotDispatched(ReseedTenantWebsiteJob::class);

        $layout = TenantWebsiteLayout::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($layout);
        $this->assertSame(true, data_get($layout->data, 'existing.keep'));
        $this->assertSame('#aa1111', data_get($layout->data, 'branding.colors.primary'));
        $this->assertSame('#bb2222', data_get($layout->data, 'branding.colors.secondary'));
        $this->assertSame('#cc3333', data_get($layout->data, 'branding.colors.accent'));
        $this->assertSame('Jeddah', data_get($layout->data, 'companyInfo.address'));
        $this->assertSame('8-6', data_get($layout->data, 'companyInfo.workingHours'));
        $this->assertSame($user->email, data_get($layout->data, 'companyInfo.email'));
        $this->assertSame('HOT-99', data_get($layout->data, 'companyInfo.valLicense'));
        $this->assertSame(
            'https://cdn.example.com/hot-path-logo.png',
            data_get($layout->data, 'companyInfo.logo')
        );

        $globals = TenantGlobalComponent::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($globals);
        $this->assertSame(
            $user->email,
            data_get($globals->data, 'footer.content.contactInfo.email')
        );
        $this->assertSame(
            'https://cdn.example.com/hot-path-logo.png',
            data_get($globals->data, 'header.logo.image')
        );
        $this->assertSame(
            'Hot Path Realty',
            data_get($globals->data, 'header.logo.text')
        );

        $page = TenantPage::query()->where('user_id', $user->id)->where('page_id', 'home')->first();
        $this->assertNotNull($page);
        $this->assertSame(
            'https://cdn.example.com/hot-path-logo.png',
            data_get($page->components, '0.data.logo.image')
        );
        $this->assertSame(
            'Hot Path Realty',
            data_get($page->components, '0.data.logo.text')
        );
    }

    public function test_onboarding_cold_path_dispatches_reseed_when_website_incomplete(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('user_languages')
            || !Schema::hasTable('tenant_website_layouts')) {
            $this->markTestSkipped('Required tables are missing for onboarding cold path test.');
        }

        config(['app.tenant_website_api_url' => 'https://tenant-template.test/defaults']);

        Http::fake([
            'https://tenant-template.test/defaults' => Http::response([
                'componentSettings' => ['home' => []],
                'globalComponentsData' => [],
            ], 200),
        ]);

        Bus::fake([ReseedTenantWebsiteJob::class]);

        $user = $this->createTenantWithLanguage();

        // Layout only → incomplete (no pages/globals) → cold path.
        TenantWebsiteLayout::query()->create([
            'user_id' => $user->id,
            'data' => ['existing' => ['keep' => true]],
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/onboarding', $this->onboardingPayload([
            'email' => $user->email,
            'phone' => $user->phone,
            'title' => 'Cold Path Realty',
        ]));
        $response->assertOk();

        Bus::assertDispatched(ReseedTenantWebsiteJob::class, function (ReseedTenantWebsiteJob $job) use ($user) {
            return $job->userId === $user->id;
        });
    }
}
