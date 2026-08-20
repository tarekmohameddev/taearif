<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Payment\ArbController;
use App\Models\Api\marketing\CreditPackage;
use App\Models\Api\marketing\CreditTransaction;
use App\Models\Api\marketing\UserCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use ReflectionClass;
use Tests\TestCase;

class CreditArbPaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private const ARB_RESOURCE_KEY = '12345678901234567890123456789012';

    private function requireCreditPaymentTables(): void
    {
        foreach (['users', 'credit_packages', 'credit_transactions', 'user_credits', 'payment_gateways'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->requireCreditPaymentTables();
        $this->ensureArbGateway();
    }

    private function ensureArbGateway(): void
    {
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
                ], JSON_UNESCAPED_SLASHES),
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

    private function createPackage(array $overrides = []): CreditPackage
    {
        return CreditPackage::create(array_merge([
            'name' => 'Iframe Credits',
            'name_ar' => 'رصيد الإطار',
            'description' => 'Iframe callback package',
            'description_ar' => 'باقة اختبار',
            'credits' => 100,
            'price' => 50,
            'currency' => 'SAR',
            'discount_percentage' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 1,
            'supports_marketing_channels' => true,
            'marketing_priority' => 1,
        ], $overrides));
    }

    private function createTransaction(
        User $user,
        ?CreditPackage $package = null,
        string $status = 'pending',
        array $overrides = []
    ): CreditTransaction {
        $package = $package ?? $this->createPackage();

        return CreditTransaction::create(array_merge([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'transaction_type' => 'purchase',
            'credits_amount' => $package->credits,
            'amount_paid' => $package->discounted_price,
            'currency' => 'SAR',
            'payment_method' => 'arb',
            'status' => $status,
            'reference_number' => 'CT' . now()->format('YmdHis') . random_int(1000, 9999),
            'description' => 'Credit callback test',
            'metadata' => ['source' => 'phpunit'],
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

        return $encryptMethod->invoke(
            $controller,
            $wrapMethod->invoke($controller, $paymentRecord),
            self::ARB_RESOURCE_KEY
        );
    }

    private function capturedRecord(
        CreditTransaction $transaction,
        string $gatewayId,
        array $overrides = []
    ): array {
        return array_merge([
            'result' => 'CAPTURED',
            'transId' => $gatewayId,
            'amt' => (float) $transaction->amount_paid,
            'udf1' => (string) $transaction->credit_package_id,
            'udf2' => (string) $transaction->user_id,
            'udf3' => 'CREDIT_PURCHASE',
            'udf4' => '0',
            'udf5' => (string) $transaction->id,
        ], $overrides);
    }

    private function callbackUrl(string $entryPoint, CreditTransaction $transaction): string
    {
        return "/api/v1/credits/payment/{$entryPoint}/{$transaction->id}/arb";
    }

    private function assertGrantedOnce(CreditTransaction $transaction, int $startingBalance): void
    {
        $transaction->refresh();

        $this->assertSame('completed', $transaction->status);
        $this->assertSame(
            $startingBalance + (int) $transaction->credits_amount,
            (int) UserCredit::where('user_id', $transaction->user_id)->value('total_credits')
        );
        $this->assertSame(
            1,
            CreditTransaction::where('user_id', $transaction->user_id)
                ->where('credit_package_id', $transaction->credit_package_id)
                ->where('transaction_type', 'purchase')
                ->count(),
            'The pending purchase row must be completed in place, not duplicated.'
        );
    }

    /** @test */
    public function browser_get_and_post_captured_callbacks_return_embeddable_html_and_grant_once(): void
    {
        $user = $this->createUser(10);
        $transaction = $this->createTransaction($user);
        $trandata = $this->encryptTrandata($this->capturedRecord($transaction, 'ARB-HTML-001'));
        $url = $this->callbackUrl('success', $transaction);

        $get = $this->get($url . '?' . http_build_query(['trandata' => $trandata]));

        $get->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $get->assertHeaderMissing('X-Frame-Options');
        $this->assertStringContainsString('frame-ancestors', (string) $get->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('taearif-payment', $get->getContent());
        $this->assertStringContainsString('"status":"success"', $get->getContent());
        $this->assertStringContainsString('تمت العملية بنجاح', $get->getContent());
        $this->assertStringContainsString('#FFFFFF', $get->getContent());
        $this->assertStringContainsString('#4F9E8E', $get->getContent());

        $post = $this->post($url, ['trandata' => $trandata]);
        $post->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('"status":"success"', $post->getContent());

        $this->assertSame('ARB-HTML-001', $transaction->fresh()->payment_transaction_id);
        $this->assertGrantedOnce($transaction, 10);
    }

    /** @test */
    public function accept_json_returns_normalized_success_payload_with_ct_reference(): void
    {
        $user = $this->createUser(20);
        $transaction = $this->createTransaction($user);
        $trandata = $this->encryptTrandata($this->capturedRecord($transaction, 'ARB-JSON-001'));

        $this->postJson($this->callbackUrl('success', $transaction), ['trandata' => $trandata])
            ->assertOk()
            ->assertJsonPath('source', 'taearif-payment')
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('transaction_id', $transaction->reference_number)
            ->assertJsonPath('reference_number', $transaction->reference_number);

        $this->assertGrantedOnce($transaction, 20);
    }

    /** @test */
    public function api_callback_path_does_not_force_a_default_get_request_to_json(): void
    {
        $user = $this->createUser();
        $transaction = $this->createTransaction($user);
        $trandata = $this->encryptTrandata($this->capturedRecord($transaction, 'ARB-DEFAULT-HTML'));

        $this->get($this->callbackUrl('success', $transaction) . '?' . http_build_query(['trandata' => $trandata]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /** @test */
    public function captured_result_recovers_from_cancel_and_failed_callback_urls(): void
    {
        foreach (['cancel', 'failed'] as $entryPoint) {
            $user = $this->createUser(5);
            $transaction = $this->createTransaction($user);
            $gatewayId = 'ARB-RECOVERY-' . strtoupper($entryPoint);
            $trandata = $this->encryptTrandata($this->capturedRecord($transaction, $gatewayId));

            $this->postJson($this->callbackUrl($entryPoint, $transaction), ['trandata' => $trandata])
                ->assertOk()
                ->assertJsonPath('status', 'success');

            $this->assertGrantedOnce($transaction, 5);
        }
    }

    /** @test */
    public function captured_callback_upgrades_failed_and_cancelled_transactions(): void
    {
        foreach (['failed', 'cancelled'] as $priorStatus) {
            $user = $this->createUser(30);
            $transaction = $this->createTransaction($user, null, $priorStatus);
            $trandata = $this->encryptTrandata(
                $this->capturedRecord($transaction, 'ARB-UPGRADE-' . strtoupper($priorStatus))
            );

            $this->postJson($this->callbackUrl('success', $transaction), ['trandata' => $trandata])
                ->assertOk()
                ->assertJsonPath('status', 'success');

            $this->assertGrantedOnce($transaction, 30);
        }
    }

    /** @test */
    public function cancel_without_trandata_returns_cancelled_html_and_json_without_credits(): void
    {
        $htmlUser = $this->createUser(40);
        $htmlTransaction = $this->createTransaction($htmlUser);

        $html = $this->get($this->callbackUrl('cancel', $htmlTransaction));
        $html->assertOk()->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('"status":"cancelled"', $html->getContent());
        $this->assertSame('cancelled', $htmlTransaction->fresh()->status);
        $this->assertSame(40, (int) UserCredit::where('user_id', $htmlUser->id)->value('total_credits'));

        $jsonUser = $this->createUser(50);
        $jsonTransaction = $this->createTransaction($jsonUser);

        $this->postJson($this->callbackUrl('cancel', $jsonTransaction))
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');
        $this->assertSame('cancelled', $jsonTransaction->fresh()->status);
        $this->assertSame(50, (int) UserCredit::where('user_id', $jsonUser->id)->value('total_credits'));
    }

    /** @test */
    public function missing_or_undecryptable_trandata_on_success_marks_failed_without_credits(): void
    {
        foreach ([[], ['trandata' => 'not-valid-ciphertext']] as $payload) {
            $user = $this->createUser(60);
            $transaction = $this->createTransaction($user);

            $this->postJson($this->callbackUrl('success', $transaction), $payload)
                ->assertOk()
                ->assertJsonPath('status', 'failed');

            $this->assertSame('failed', $transaction->fresh()->status);
            $this->assertSame(60, (int) UserCredit::where('user_id', $user->id)->value('total_credits'));
        }
    }

    /** @test */
    public function captured_callback_requires_transaction_binding_amount_and_gateway_id(): void
    {
        foreach ([
            ['gateway_id' => 'ARB-WRONG-BINDING', 'overrides' => ['udf5' => '999999']],
            ['gateway_id' => 'ARB-WRONG-AMOUNT', 'overrides' => ['amt' => 1.0]],
            ['gateway_id' => '', 'overrides' => []],
        ] as $case) {
            $user = $this->createUser(65);
            $transaction = $this->createTransaction($user);
            $trandata = $this->encryptTrandata(
                $this->capturedRecord($transaction, $case['gateway_id'], $case['overrides'])
            );

            $this->postJson($this->callbackUrl('success', $transaction), ['trandata' => $trandata])
                ->assertOk()
                ->assertJsonPath('status', 'failed');

            $this->assertSame('failed', $transaction->fresh()->status);
            $this->assertSame(
                65,
                (int) UserCredit::where('user_id', $user->id)->value('total_credits')
            );
        }
    }

    /** @test */
    public function one_gateway_payment_cannot_complete_two_transactions_for_the_same_user(): void
    {
        $user = $this->createUser(75);
        $first = $this->createTransaction($user);
        $second = $this->createTransaction($user);
        $gatewayId = 'ARB-UNIQUE-OWNER';

        $this->postJson($this->callbackUrl('success', $first), [
            'trandata' => $this->encryptTrandata($this->capturedRecord($first, $gatewayId)),
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->postJson($this->callbackUrl('success', $second), [
            'trandata' => $this->encryptTrandata($this->capturedRecord($second, $gatewayId)),
        ])->assertOk()->assertJsonPath('status', 'failed');

        $this->assertSame('completed', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame(
            75 + (int) $first->credits_amount,
            (int) UserCredit::where('user_id', $user->id)->value('total_credits')
        );
    }

    /** @test */
    public function two_sequential_captured_posts_are_idempotent(): void
    {
        $user = $this->createUser(70);
        $transaction = $this->createTransaction($user);
        $trandata = $this->encryptTrandata($this->capturedRecord($transaction, 'ARB-DOUBLE-POST'));

        foreach ([1, 2] as $attempt) {
            $this->postJson($this->callbackUrl('success', $transaction), ['trandata' => $trandata])
                ->assertOk()
                ->assertJsonPath('status', 'success');
        }

        $this->assertGrantedOnce($transaction, 70);
    }

    /** @test */
    public function authenticated_polling_maps_statuses_and_enforces_transaction_ownership(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        Sanctum::actingAs($user);

        $transactions = [];
        foreach ([
            'pending' => 'pending',
            'completed' => 'success',
            'cancelled' => 'cancelled',
            'failed' => 'failed',
        ] as $databaseStatus => $apiStatus) {
            $transactions[$databaseStatus] = $this->createTransaction($user, null, $databaseStatus);
            $identifier = $databaseStatus === 'pending'
                ? (string) $transactions[$databaseStatus]->id
                : $transactions[$databaseStatus]->reference_number;

            $this->getJson("/api/v1/credits/payment/status/{$identifier}")
                ->assertOk()
                ->assertJsonPath('status', $apiStatus)
                ->assertJsonPath('transaction_id', $transactions[$databaseStatus]->reference_number);
        }

        $this->getJson(
            '/api/v1/credits/payment/status/' . $transactions['completed']->id
        )->assertOk()->assertJsonPath('status', 'success');

        $otherTransaction = $this->createTransaction($otherUser);
        $this->getJson(
            "/api/v1/credits/payment/status/{$otherTransaction->id}"
        )->assertNotFound();
        $this->getJson(
            "/api/v1/credits/payment/status/{$otherTransaction->reference_number}"
        )->assertNotFound();
    }
}
