<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Payment\MyFatoorahController;
use App\Models\Api\marketing\CreditPackage;
use App\Models\Api\marketing\CreditTransaction;
use App\Models\Api\marketing\UserCredit;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Services\Payment\MyFatoorahGatewayService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class CreditMyFatoorahPaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'users',
            'credit_packages',
            'credit_transactions',
            'user_credits',
            'payment_gateways',
            'languages',
            'basic_extendeds',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }

        $this->ensureGatewayAndLanguage();
    }

    private function ensureGatewayAndLanguage(): void
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
            ['keyword' => 'myfatoorah'],
            [
                'name' => 'MyFatoorah',
                'subtitle' => 'MyFatoorah',
                'title' => 'MyFatoorah',
                'details' => 'MyFatoorah Gateway',
                'type' => 'automatic',
                'status' => 1,
                'information' => json_encode([
                    'token' => 'test-token',
                    'sandbox_status' => 1,
                ]),
            ]
        );
    }

    private function createUser(int $balance = 10): User
    {
        $user = User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'active' => true,
            'status' => 1,
        ]);

        UserCredit::getOrCreateForUser($user->id)->update([
            'total_credits' => $balance,
            'used_credits' => 0,
        ]);

        return $user;
    }

    private function createPackage(): CreditPackage
    {
        return CreditPackage::create([
            'name' => 'MyFatoorah Credits',
            'name_ar' => 'رصيد ماي فاتورة',
            'description' => 'MyFatoorah callback package',
            'description_ar' => 'باقة اختبار',
            'credits' => 80,
            'price' => 40,
            'currency' => 'SAR',
            'discount_percentage' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 1,
            'supports_marketing_channels' => true,
            'marketing_priority' => 1,
        ]);
    }

    private function createTransaction(
        User $user,
        ?CreditPackage $package = null,
        string $status = 'pending'
    ): CreditTransaction
    {
        $package = $package ?? $this->createPackage();

        return CreditTransaction::create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'transaction_type' => 'purchase',
            'credits_amount' => $package->credits,
            'amount_paid' => $package->discounted_price,
            'currency' => $package->currency,
            'payment_method' => 'myfatoorah',
            'status' => $status,
            'reference_number' => 'CT' . now()->format('YmdHis') . random_int(1000, 9999),
            'description' => 'MyFatoorah credit callback test',
        ]);
    }

    private function fakePaymentStatus(string $paymentId, array $data): void
    {
        $service = Mockery::mock(MyFatoorahGatewayService::class);
        $service->shouldReceive('getPaymentStatus')
            ->once()
            ->with(Mockery::type(PaymentGateway::class), $paymentId)
            ->andReturn([
                'IsSuccess' => true,
                'Data' => $data,
            ]);

        $this->app->instance(MyFatoorahGatewayService::class, $service);
    }

    private function callbackUrl(CreditTransaction $transaction): string
    {
        return "/api/v1/credits/payment/success/{$transaction->id}/myfatoorah";
    }

    /** @test */
    public function purchase_initialization_returns_myfatoorah_redirect_url(): void
    {
        $user = $this->createUser();
        $package = $this->createPackage();
        Sanctum::actingAs($user);

        $service = Mockery::mock(MyFatoorahGatewayService::class);
        $service->shouldReceive('sendPayment')
            ->once()
            ->with(
                Mockery::type(PaymentGateway::class),
                Mockery::on(function (array $params): bool {
                    return str_contains($params['success_url'], '/payment/success/')
                        && str_contains($params['cancel_url'], '/payment/failed/')
                        && str_starts_with($params['customer_reference'], 'CT');
                })
            )
            ->andReturn([
                'IsSuccess' => true,
                'Data' => [
                    'InvoiceURL' => 'https://apitest.myfatoorah.com/pay/INV-123',
                    'InvoiceId' => 'INV-123',
                ],
            ]);
        $this->app->instance(MyFatoorahGatewayService::class, $service);

        $this->postJson('/api/v1/credits/purchase', [
            'package_id' => $package->id,
            'payment_method' => 'myfatoorah',
        ])->assertOk()
            ->assertJsonPath('data.payment_status', 'redirect_required')
            ->assertJsonPath('data.redirect_url', 'https://apitest.myfatoorah.com/pay/INV-123');
    }

    /** @test */
    public function legacy_membership_payment_process_still_returns_sdk_redirect(): void
    {
        $request = Request::create('/membership/pay', 'POST', [
            'first_name' => 'Test',
            'last_name' => 'Member',
            'phone' => '500000000',
        ]);
        $request->setLaravelSession(app('session')->driver());
        Session::put('paymentFor', 'membership');

        $sdk = Mockery::mock();
        $sdk->shouldReceive('sendPayment')
            ->once()
            ->andReturn([
                'IsSuccess' => true,
                'Data' => ['InvoiceURL' => 'https://apitest.myfatoorah.com/pay/MEMBER-1'],
            ]);

        $controller = new MyFatoorahController();
        $controller->myfatoorah = $sdk;

        $response = $controller->paymentProcess(
            $request,
            40,
            'https://example.test/success',
            'https://example.test/cancel',
            'Membership',
            null
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame(
            'https://apitest.myfatoorah.com/pay/MEMBER-1',
            $response->getTargetUrl()
        );
    }

    /** @test */
    public function paid_callback_with_matching_reference_and_amount_grants_credits_once(): void
    {
        $user = $this->createUser(15);
        $transaction = $this->createTransaction($user);
        $this->fakePaymentStatus('MF-PAID-001', [
            'InvoiceStatus' => 'Paid',
            'CustomerReference' => $transaction->reference_number,
            'UserDefinedField' => (string) $transaction->id,
            'InvoiceValue' => (float) $transaction->amount_paid,
        ]);

        $this->postJson($this->callbackUrl($transaction), ['paymentId' => 'MF-PAID-001'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('transaction_id', $transaction->reference_number);

        $this->assertSame('completed', $transaction->fresh()->status);
        $this->assertSame('MF-PAID-001', $transaction->fresh()->payment_transaction_id);
        $this->assertSame(95, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
        $this->assertSame(
            1,
            CreditTransaction::where('user_id', $user->id)
                ->where('credit_package_id', $transaction->credit_package_id)
                ->where('transaction_type', 'purchase')
                ->count()
        );
    }

    /** @test */
    public function paid_callback_recovers_failed_and_cancelled_transactions(): void
    {
        foreach (['failed', 'cancelled'] as $priorStatus) {
            $user = $this->createUser(20);
            $transaction = $this->createTransaction($user, null, $priorStatus);
            $paymentId = 'MF-RECOVER-' . strtoupper($priorStatus);
            $this->fakePaymentStatus($paymentId, [
                'InvoiceStatus' => 'Paid',
                'CustomerReference' => $transaction->reference_number,
                'UserDefinedField' => (string) $transaction->id,
                'InvoiceValue' => (float) $transaction->amount_paid,
            ]);

            $this->postJson($this->callbackUrl($transaction), ['paymentId' => $paymentId])
                ->assertOk()
                ->assertJsonPath('status', 'success');

            $this->assertSame('completed', $transaction->fresh()->status);
            $this->assertSame(
                20 + (int) $transaction->credits_amount,
                (int) UserCredit::where('user_id', $user->id)->value('total_credits')
            );
        }
    }

    /** @test */
    public function paid_callback_with_wrong_customer_binding_fails_without_credits(): void
    {
        $user = $this->createUser(25);
        $transaction = $this->createTransaction($user);
        $this->fakePaymentStatus('MF-WRONG-REF', [
            'InvoiceStatus' => 'Paid',
            'CustomerReference' => 'CT-WRONG',
            'UserDefinedField' => '999999',
            'InvoiceValue' => (float) $transaction->amount_paid,
        ]);

        $this->postJson($this->callbackUrl($transaction), ['paymentId' => 'MF-WRONG-REF'])
            ->assertOk()
            ->assertJsonPath('status', 'failed');

        $this->assertSame('failed', $transaction->fresh()->status);
        $this->assertSame(25, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
    }

    /** @test */
    public function paid_callback_with_mismatched_amount_fails_without_credits(): void
    {
        $user = $this->createUser(35);
        $transaction = $this->createTransaction($user);
        $this->fakePaymentStatus('MF-WRONG-AMOUNT', [
            'InvoiceStatus' => 'Paid',
            'CustomerReference' => $transaction->reference_number,
            'InvoiceValue' => 1.0,
        ]);

        $this->postJson($this->callbackUrl($transaction), ['paymentId' => 'MF-WRONG-AMOUNT'])
            ->assertOk()
            ->assertJsonPath('status', 'failed');

        $this->assertSame('failed', $transaction->fresh()->status);
        $this->assertSame(35, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
    }

    /** @test */
    public function unpaid_invoice_returns_pending_and_leaves_transaction_pending(): void
    {
        $user = $this->createUser(45);
        $transaction = $this->createTransaction($user);
        $this->fakePaymentStatus('MF-PENDING-001', [
            'InvoiceStatus' => 'Pending',
            'CustomerReference' => $transaction->reference_number,
            'InvoiceValue' => (float) $transaction->amount_paid,
        ]);

        $this->getJson($this->callbackUrl($transaction) . '?paymentId=MF-PENDING-001')
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(45, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
    }

    /** @test */
    public function cancel_callback_with_pending_verification_leaves_transaction_pending(): void
    {
        $user = $this->createUser(50);
        $transaction = $this->createTransaction($user);
        $this->fakePaymentStatus('MF-PENDING-CANCEL', [
            'InvoiceStatus' => 'InProgress',
            'CustomerReference' => $transaction->reference_number,
            'InvoiceValue' => (float) $transaction->amount_paid,
        ]);

        $this->getJson(
            "/api/v1/credits/payment/cancel/{$transaction->id}/myfatoorah"
            . '?paymentId=MF-PENDING-CANCEL'
        )
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(50, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
    }

    /** @test */
    public function myfatoorah_api_failure_is_pending_instead_of_marking_the_transaction_failed(): void
    {
        $user = $this->createUser(55);
        $transaction = $this->createTransaction($user);

        $service = Mockery::mock(MyFatoorahGatewayService::class);
        $service->shouldReceive('getPaymentStatus')
            ->once()
            ->andReturn(['IsSuccess' => false, 'Message' => 'Temporary gateway error']);
        $this->app->instance(MyFatoorahGatewayService::class, $service);

        $this->postJson($this->callbackUrl($transaction), ['paymentId' => 'MF-TEMPORARY'])
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(
            55,
            (int) UserCredit::where('user_id', $user->id)->value('total_credits')
        );
    }
}
