<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Payment\ArbController;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use ReflectionClass;
use Tests\TestCase;

class MembershipArbPaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private const ARB_RESOURCE_KEY = '12345678901234567890123456789012';

    private function requireMembershipPaymentTables(): void
    {
        foreach (['users', 'packages', 'memberships', 'payment_gateways', 'languages', 'basic_extendeds'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function ensureArbGatewayAndLanguage(): void
    {
        $languageId = DB::table('languages')->where('is_default', 1)->value('id');

        if (!$languageId) {
            $languageId = DB::table('languages')->insertGetId([
                'user_id' => null,
                'name' => 'English',
                'code' => 'en',
                'is_default' => 1,
                'rtl' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (!DB::table('basic_extendeds')->where('language_id', $languageId)->exists()) {
            DB::table('basic_extendeds')->insert([
                'language_id' => $languageId,
                'cookie_alert_status' => 1,
                'default_language_direction' => 'ltr',
                'is_smtp' => 0,
                'base_currency_symbol' => 'SAR',
                'base_currency_symbol_position' => 'left',
                'base_currency_text' => 'SAR',
                'base_currency_text_position' => 'right',
                'base_currency_rate' => 1,
                'is_whatsapp' => 1,
                'whatsapp_popup' => 1,
                'expiration_reminder' => 3,
                'welcome_message_email_enabled' => 1,
                'subscription_expiration_email_enabled' => 1,
                'subscription_expired_email_enabled' => 1,
                'email_notifications_enabled' => 1,
            ]);
        }

        DB::table('payment_gateways')->updateOrInsert(
            ['keyword' => 'arb'],
            [
                'name' => 'Arb',
                'subtitle' => 'Arb',
                'title' => 'Arb',
                'details' => 'ARB Gateway',
                'type' => 'automatic',
                'status' => 1,
                'information' => json_encode([
                    'tranportal_id' => 'TID123',
                    'tranportal_password' => 'PWD123',
                    'resource_key' => self::ARB_RESOURCE_KEY,
                    'mode' => 'test',
                    'test_bank_hosted_endpoint' => 'https://arb.test/init',
                    'live_bank_hosted_endpoint' => 'https://arb.live/init',
                ], JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function createMembershipPackage(array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'title' => 'Monthly Pro',
            'slug' => 'monthly-pro-' . Str::random(6),
            'price' => 99,
            'term' => 'monthly',
            'status' => '1',
            'is_active' => true,
        ], $overrides));
    }

    private function encryptTrandata(array $paymentRecord): string
    {
        $controller = new ArbController();
        $reflection = new ReflectionClass($controller);

        $wrapMethod = $reflection->getMethod('wrapData');
        $wrapMethod->setAccessible(true);

        $encryptMethod = $reflection->getMethod('encryption');
        $encryptMethod->setAccessible(true);

        $wrapped = $wrapMethod->invoke($controller, $paymentRecord);

        return $encryptMethod->invoke($controller, $wrapped, self::ARB_RESOURCE_KEY);
    }

    private function buildPaymentRecord(User $user, Package $package, array $overrides = []): array
    {
        return array_merge([
            'result' => 'CAPTURED',
            'transId' => 'ARB-MEM-' . uniqid(),
            'amt' => 99.0,
            'udf1' => (string) $package->id,
            'udf2' => (string) $user->id,
            'udf3' => 'MEMBERSHIP',
            'udf4' => '0',
            'udf5' => '1',
        ], $overrides);
    }

    /** @test */
    public function make_payment_sends_membership_callback_urls_to_arb(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        Http::fake([
            'https://arb.test/init' => Http::response([
                [
                    'status' => '1',
                    'result' => 'PID123:IGNORED://pay.example/checkout',
                ],
            ], 200),
        ]);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        Sanctum::actingAs($tenant);

        $package = $this->createMembershipPackage();

        $response = $this->postJson('/api/make-payment', [
            'package_id' => $package->id,
            'period' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['payment_url']);

        $expectedSuccessUrl = route('api.membership.payment.success', ['gateway' => 'arb']);
        $expectedFailedUrl = route('api.membership.payment.failed', ['gateway' => 'arb']);

        Http::assertSent(function ($request) use ($expectedSuccessUrl, $expectedFailedUrl): bool {
            $payload = json_decode($request->body(), true);

            return $request->url() === 'https://arb.test/init'
                && is_array($payload)
                && isset($payload[0]['responseURL'], $payload[0]['errorURL'])
                && $payload[0]['responseURL'] === $expectedSuccessUrl
                && $payload[0]['errorURL'] === $expectedFailedUrl;
        });
    }

    /** @test */
    public function captured_payment_creates_membership_and_returns_payment_success_html(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage();
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'transId' => 'ARB-CAPTURED-001',
            'amt' => 99.0,
        ]);

        $response = $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        );

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('payment_success', $response->getContent());

        $this->assertDatabaseHas('memberships', [
            'user_id' => $tenant->id,
            'package_id' => $package->id,
            'transaction_id' => 'ARB-CAPTURED-001',
            'payment_method' => 'arb',
        ]);
    }

    /** @test */
    public function not_captured_payment_returns_payment_failed_html_without_membership(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage();
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'result' => 'NOT CAPTURED',
            'transId' => 'ARB-NOT-CAP-001',
        ]);

        $response = $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        );

        $response->assertOk();
        $this->assertStringContainsString('payment_failed', $response->getContent());

        $this->assertDatabaseMissing('memberships', [
            'transaction_id' => 'ARB-NOT-CAP-001',
        ]);
    }

    /** @test */
    public function canceled_payment_on_failed_callback_returns_payment_failed_html(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage();
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'result' => 'CANCELED',
            'transId' => 'ARB-CANCEL-001',
        ]);

        $response = $this->post(
            '/api/v1/membership/payment/failed/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        );

        $response->assertOk();
        $this->assertStringContainsString('payment_failed', $response->getContent());

        $this->assertDatabaseMissing('memberships', [
            'transaction_id' => 'ARB-CANCEL-001',
        ]);
    }

    /** @test */
    public function arb_error_field_returns_payment_failed_html(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage();
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'result' => 'NOT AUTHENTICATED',
            'error' => 'IPAY0100357',
            'transId' => 'ARB-3DS-FAIL-001',
        ]);

        $response = $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        );

        $response->assertOk();
        $this->assertStringContainsString('payment_failed', $response->getContent());

        $this->assertDatabaseMissing('memberships', [
            'transaction_id' => 'ARB-3DS-FAIL-001',
        ]);
    }

    /** @test */
    public function duplicate_trans_id_is_idempotent_and_does_not_create_second_membership(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage();
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'transId' => 'ARB-DUP-001',
        ]);

        $trandata = $this->encryptTrandata($paymentRecord);

        $first = $this->post('/api/v1/membership/payment/success/arb', ['trandata' => $trandata]);
        $first->assertOk();
        $this->assertStringContainsString('payment_success', $first->getContent());

        $second = $this->post('/api/v1/membership/payment/success/arb', ['trandata' => $trandata]);
        $second->assertOk();
        $this->assertStringContainsString('payment_success', $second->getContent());

        $this->assertSame(
            1,
            Membership::where('transaction_id', 'ARB-DUP-001')->count()
        );
    }

    /** @test */
    public function checkout_rejects_inactive_package(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        Sanctum::actingAs($tenant);

        $package = $this->createMembershipPackage(['is_active' => false]);

        $this->postJson('/api/make-payment', [
            'package_id' => $package->id,
            'period' => 1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['package_id']);
    }

    /** @test */
    public function amount_mismatch_does_not_create_membership(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage(['price' => 99]);
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'transId' => 'ARB-MISMATCH-001',
            'amt' => 1.0,
        ]);

        $response = $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        );

        $response->assertOk();
        $this->assertStringContainsString('payment_failed', $response->getContent());
        $this->assertDatabaseMissing('memberships', [
            'transaction_id' => 'ARB-MISMATCH-001',
        ]);
    }

    /** @test */
    public function affiliate_commission_is_recorded_only_when_program_is_active(): void
    {
        $this->requireMembershipPaymentTables();
        $this->ensureArbGatewayAndLanguage();

        if (!\Illuminate\Support\Facades\Schema::hasTable('api_affiliate_users')) {
            $this->markTestSkipped('api_affiliate_users table required.');
        }

        $affiliateOwner = User::factory()->create(['account_type' => 'tenant']);
        $affiliate = \App\Models\Api\ApiAffiliateUser::query()->create([
            'user_id' => $affiliateOwner->id,
            'fullname' => 'Affiliate User',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '123456',
            'iban' => 'SA123',
            'commission_percentage' => 0.10,
            'pending_amount' => 0,
            'request_status' => 'approved',
            'to_date_value' => now()->addMonth()->toDateString(),
        ]);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'referred_by' => $affiliateOwner->id,
            'active' => true,
            'status' => 1,
        ]);
        $package = $this->createMembershipPackage(['price' => 100]);
        $paymentRecord = $this->buildPaymentRecord($tenant, $package, [
            'transId' => 'ARB-AFF-ACTIVE-001',
            'amt' => 100.0,
        ]);

        $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecord)]
        )->assertOk();

        $affiliate->refresh();
        $this->assertSame(10.0, (float) $affiliate->pending_amount);

        $expiredAffiliate = \App\Models\Api\ApiAffiliateUser::query()->create([
            'user_id' => User::factory()->create(['account_type' => 'tenant'])->id,
            'fullname' => 'Expired Affiliate',
            'bank_name' => 'Test Bank',
            'bank_account_number' => '654321',
            'iban' => 'SA654',
            'commission_percentage' => 0.10,
            'pending_amount' => 0,
            'request_status' => 'approved',
            'to_date_value' => now()->subDay()->toDateString(),
        ]);

        $tenantTwo = User::factory()->create([
            'account_type' => 'tenant',
            'referred_by' => $expiredAffiliate->user_id,
            'active' => true,
            'status' => 1,
        ]);
        $paymentRecordTwo = $this->buildPaymentRecord($tenantTwo, $package, [
            'transId' => 'ARB-AFF-EXPIRED-001',
            'amt' => 100.0,
        ]);

        $this->post(
            '/api/v1/membership/payment/success/arb',
            ['trandata' => $this->encryptTrandata($paymentRecordTwo)]
        )->assertOk();

        $expiredAffiliate->refresh();
        $this->assertSame(0.0, (float) $expiredAffiliate->pending_amount);
    }
}
