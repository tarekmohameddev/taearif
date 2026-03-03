<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\markting\CreditPackage;
use App\Models\Api\markting\CreditTransaction;
use App\Models\Api\markting\UserCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditArbPaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function requireCreditPaymentTables(): void
    {
        foreach (['users', 'credit_packages', 'credit_transactions', 'user_credits', 'payment_gateways', 'languages', 'basic_extendeds'] as $table) {
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
                    'resource_key' => '12345678901234567890123456789012',
                    'mode' => 'test',
                    'test_bank_hosted_endpoint' => 'https://arb.test/init',
                    'live_bank_hosted_endpoint' => 'https://arb.live/init',
                ], JSON_UNESCAPED_SLASHES),
            ]
        );
    }

    private function createPackage(): CreditPackage
    {
        return CreditPackage::create([
            'name' => 'Starter Credits',
            'name_ar' => 'باقة البداية',
            'description' => 'Starter package',
            'description_ar' => 'باقة تجريبية',
            'credits' => 100,
            'price' => 50,
            'currency' => 'SAR',
            'discount_percentage' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 1,
            'supports_marketing_channels' => true,
            'marketing_priority' => 1,
        ]);
    }

    private function createPendingTransaction(User $user, int $credits = 120): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'credit_package_id' => null,
            'transaction_type' => 'purchase',
            'credits_amount' => $credits,
            'amount_paid' => 75,
            'currency' => 'SAR',
            'payment_method' => 'arb',
            'status' => 'pending',
            'reference_number' => 'CT-TEST-' . uniqid(),
            'description' => 'Test pending credit purchase',
            'metadata' => ['source' => 'phpunit'],
        ]);
    }

    /** @test */
    public function arb_purchase_initialization_sends_credit_callback_urls_and_returns_redirect(): void
    {
        $this->requireCreditPaymentTables();
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

        $package = $this->createPackage();

        $response = $this->postJson('/api/v1/credits/purchase', [
            'package_id' => $package->id,
            'payment_method' => 'arb',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.payment_status', 'redirect_required');

        $transactionId = (int) $response->json('data.transaction_id');
        $this->assertGreaterThan(0, $transactionId);

        $expectedSuccessUrl = route('api.credits.payment.success', [
            'transaction_id' => $transactionId,
            'gateway' => 'arb',
        ]);
        $expectedCancelUrl = route('api.credits.payment.cancel', [
            'transaction_id' => $transactionId,
            'gateway' => 'arb',
        ]);

        Http::assertSent(function ($request) use ($expectedSuccessUrl, $expectedCancelUrl): bool {
            $payload = json_decode($request->body(), true);

            return $request->url() === 'https://arb.test/init'
                && is_array($payload)
                && isset($payload[0]['responseURL'], $payload[0]['errorURL'])
                && $payload[0]['responseURL'] === $expectedSuccessUrl
                && $payload[0]['errorURL'] === $expectedCancelUrl;
        });

        $this->assertDatabaseHas('credit_transactions', [
            'id' => $transactionId,
            'status' => 'pending',
            'payment_method' => 'arb',
        ]);
    }

    /** @test */
    public function payment_success_marks_transaction_completed_and_adds_credits(): void
    {
        $this->requireCreditPaymentTables();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
        ]);

        $transaction = $this->createPendingTransaction($tenant, 120);

        $response = $this->postJson(
            "/api/v1/credits/payment/success/{$transaction->id}/arb",
            ['payment_id' => 'ARB-PAY-001']
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Payment successful and credits added')
            ->assertJsonPath('credits_added', 120)
            ->assertJsonPath('new_balance', 130);

        $transaction->refresh();
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('ARB-PAY-001', $transaction->payment_transaction_id);

        $credit = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(130, (int) $credit->total_credits);
    }

    /** @test */
    public function payment_success_with_failed_result_marks_transaction_failed_and_does_not_add_credits(): void
    {
        $this->requireCreditPaymentTables();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 40,
            'used_credits' => 0,
        ]);

        $transaction = $this->createPendingTransaction($tenant, 200);

        $response = $this->getJson(
            "/api/v1/credits/payment/success/{$transaction->id}/arb?result=NOT%20CAPTURED"
        );

        $response->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('message', 'Payment was cancelled');

        $transaction->refresh();
        $this->assertSame('failed', $transaction->status);

        $credit = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(40, (int) $credit->total_credits);
    }

    /** @test */
    public function payment_cancel_marks_transaction_failed_and_does_not_add_credits(): void
    {
        $this->requireCreditPaymentTables();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 55,
            'used_credits' => 0,
        ]);

        $transaction = $this->createPendingTransaction($tenant, 150);

        $response = $this->postJson(
            "/api/v1/credits/payment/cancel/{$transaction->id}/arb",
            ['reason' => 'user_cancelled']
        );

        $response->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('message', 'Payment was cancelled');

        $transaction->refresh();
        $this->assertSame('failed', $transaction->status);

        $credit = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(55, (int) $credit->total_credits);
    }

    /** @test */
    public function success_and_cancel_callbacks_do_not_reprocess_completed_transaction(): void
    {
        $this->requireCreditPaymentTables();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 300,
            'used_credits' => 0,
        ]);

        $transaction = CreditTransaction::create([
            'user_id' => $tenant->id,
            'credit_package_id' => null,
            'transaction_type' => 'purchase',
            'credits_amount' => 90,
            'amount_paid' => 45,
            'currency' => 'SAR',
            'payment_method' => 'arb',
            'status' => 'completed',
            'reference_number' => 'CT-COMPLETE-' . uniqid(),
            'description' => 'Already completed',
        ]);

        $successResponse = $this->getJson("/api/v1/credits/payment/success/{$transaction->id}/arb");
        $successResponse->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Payment already processed');

        $cancelResponse = $this->getJson("/api/v1/credits/payment/cancel/{$transaction->id}/arb");
        $cancelResponse->assertOk()
            ->assertJsonPath('status', 'info')
            ->assertJsonPath('message', 'Payment was already completed');

        $transaction->refresh();
        $this->assertSame('completed', $transaction->status);

        $credit = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(300, (int) $credit->total_credits);
    }
}
