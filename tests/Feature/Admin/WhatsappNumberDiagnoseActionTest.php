<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappNumberDiagnoseActionTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private const TEST_ACCESS_TOKEN = 'diag-test-access-token-do-not-leak';

    private const TEST_APP_TOKEN = 'diag-test-app-token';

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            if (! Schema::hasTable('whatsapp_users') || ! Schema::hasTable('users')) {
                $this->markTestSkipped(
                    'taearif_testing needs core tables (users, whatsapp_users). Import the application schema into taearif_testing.'
                );
            }

            RefreshDatabaseState::$migrated = true;
            $this->app->make(Kernel::class)->setArtisan(null);
        }

        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-12 14:00:00'));

        config([
            'services.meta.app_token' => self::TEST_APP_TOKEN,
            'services.meta.api_version' => 'v20.0',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function nextSuffix(): string
    {
        return (string) ++self::$sequence;
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

    private function createNumber(int $userId, array $overrides = []): int
    {
        $suffix = $this->nextSuffix();

        return (int) DB::table('whatsapp_users')->insertGetId(array_merge([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+9665' . str_pad($suffix, 8, '0', STR_PAD_LEFT),
            'name' => 'Number ' . $suffix,
            'status' => 'active',
            'request_status' => 'pending',
            'phone_id' => 'phone-id-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createAdmin(): Admin
    {
        return Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);
    }

    private function withoutAdminMiddleware(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\CheckStatus::class,
            \App\Http\Middleware\Demo::class,
            VerifyCsrfToken::class,
        ]);
    }

    private function debugTokenResponse(array $overrides = []): array
    {
        return array_replace_recursive([
            'data' => [
                'is_valid' => true,
                'expires_at' => now()->addDays(30)->timestamp,
                'granular_scopes' => [
                    [
                        'scope' => 'whatsapp_business_management',
                        'target_ids' => ['waba-123'],
                    ],
                ],
            ],
        ], $overrides);
    }

    private function phoneNumbersResponse(array $phones): array
    {
        return [
            'data' => $phones,
        ];
    }

    private function ensureAdminViewData(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        if (DB::table('languages')->exists()) {
            return;
        }

        $languageId = DB::table('languages')->insertGetId([
            'name' => 'English',
            'code' => 'en',
            'is_default' => 1,
            'rtl' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('basic_settings')->insert([
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
            'copyright_text' => 'Taearif',
        ]);

        DB::table('basic_extendeds')->insert([
            'language_id' => $languageId,
        ]);

        $currentLang = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->firstOrFail();

        View::share([
            'bs' => $currentLang->basic_setting,
            'be' => $currentLang->basic_extended,
            'currentLang' => $currentLang,
            'menus' => json_encode([]),
            'rtl' => 0,
            'socials' => collect(),
            'langs' => \App\Models\Language::all(),
            'adminLanguages' => \App\Models\Language::orderBy('is_default', 'desc')->get(),
            'admin_rtl' => false,
            'defaultLang' => $currentLang,
            'adminPermissions' => [],
        ]);
    }

    /** @test */
    public function diagnose_action_redirects_to_detail_and_flashes_diagnostics(): void
    {
        $this->ensureAdminViewData();

        $tenantId = $this->createTenant();
        $phoneId = 'action-healthy-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000001',
                    'verified_name' => 'Healthy Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $admin = $this->createAdmin();
        $this->withoutAdminMiddleware();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.whatsapp-numbers.monitor.diagnose', $numberId));

        $response->assertRedirect(route('admin.whatsapp-numbers.monitor.show', $numberId));
        $response->assertSessionHas('diagnostics');

        $diagnostics = $response->getSession()->get('diagnostics');
        $this->assertIsArray($diagnostics);
        $this->assertSame('ok', $diagnostics['summary']);
        $this->assertIsString($diagnostics['checked_at']);
        $this->assertSame('2026-08-12 14:00', $diagnostics['checked_at']);

        Http::assertSentCount(2);
    }

    /** @test */
    public function diagnose_redirect_renders_results_card_with_check_labels(): void
    {
        $this->ensureAdminViewData();

        $tenantId = $this->createTenant();
        $phoneId = 'action-render-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000002',
                    'verified_name' => 'Render Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $admin = $this->createAdmin();
        $this->withoutAdminMiddleware();

        $response = $this->actingAs($admin, 'admin')
            ->followingRedirects()
            ->post(route('admin.whatsapp-numbers.monitor.diagnose', $numberId));

        $response->assertOk();
        $response->assertSee(__('Meta diagnostics'), false);
        $response->assertSee(__('Access token present'), false);
        $response->assertSee(__('Token validity'), false);
        $response->assertSee(__('Phone number known to Meta'), false);
        $response->assertSee(__('Meta phone numbers'), false);
        $response->assertDontSee(self::TEST_ACCESS_TOKEN, false);
    }

    /** @test */
    public function detail_page_does_not_call_meta_on_load(): void
    {
        $this->ensureAdminViewData();

        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
        ]);

        Http::fake();

        $admin = $this->createAdmin();
        $this->withoutAdminMiddleware();

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.whatsapp-numbers.monitor.show', $numberId));

        $response->assertOk();
        $response->assertDontSee(__('Meta diagnostics'), false);

        Http::assertNothingSent();
    }

    /** @test */
    public function diagnose_action_returns_404_for_nonexistent_number(): void
    {
        $this->ensureAdminViewData();

        Http::fake();

        $admin = $this->createAdmin();
        $this->withoutAdminMiddleware();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.whatsapp-numbers.monitor.diagnose', 999999999));

        $response->assertNotFound();

        Http::assertNothingSent();
    }

    /** @test */
    public function rendered_diagnostics_html_does_not_contain_access_token(): void
    {
        $this->ensureAdminViewData();

        $tenantId = $this->createTenant();
        $phoneId = 'action-no-leak-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000003',
                    'verified_name' => 'No Leak Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $admin = $this->createAdmin();
        $this->withoutAdminMiddleware();

        $response = $this->actingAs($admin, 'admin')
            ->followingRedirects()
            ->post(route('admin.whatsapp-numbers.monitor.diagnose', $numberId));

        $response->assertOk();
        $response->assertDontSee(self::TEST_ACCESS_TOKEN, false);
        $response->assertDontSee(substr(self::TEST_ACCESS_TOKEN, 0, 12), false);
        $response->assertDontSee(substr(self::TEST_ACCESS_TOKEN, -12), false);
    }
}
