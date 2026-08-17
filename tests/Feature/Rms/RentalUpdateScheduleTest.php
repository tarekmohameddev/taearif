<?php

namespace Tests\Feature\Rms;

use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmRental;
use App\Http\Requests\Rms\Rental\RenewRentalRequest;
use App\Http\Requests\Rms\Rental\StoreRentalRequest;
use App\Http\Requests\Rms\Rental\UpdateRentalRequest;
use App\Services\Rms\ContractService;
use App\Services\Rms\InstallmentService;
use App\Services\Rms\RentalService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalUpdateScheduleTest extends TestCase
{
    private object $user;
    private array $createdTables = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->createIsolatedTables();
        $this->user = (object) ['id' => 991001];
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('rental_cost_items')) {
            \DB::table('rental_cost_items')->where('user_id', $this->user->id)->delete();
        }
        if (Schema::hasTable('rm_payment_installments')) {
            \DB::table('rm_payment_installments')->where('user_id', $this->user->id)->delete();
        }
        if (Schema::hasTable('rm_contracts')) {
            \DB::table('rm_contracts')->where('user_id', $this->user->id)->delete();
        }
        if (Schema::hasTable('rm_rentals')) {
            \DB::table('rm_rentals')->where('user_id', $this->user->id)->delete();
        }

        foreach (array_reverse($this->createdTables) as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_create_derives_total_from_base_and_total_wins_when_both_are_sent(): void
    {
        $service = app(RentalService::class);

        $fromBase = $service->createRental($this->user->id, $this->rentalData([
            'tenant_full_name' => 'Base only',
            'base_rent_amount' => 5000,
        ]));
        $this->assertSame('5000.00', $fromBase->base_rent_amount);
        $this->assertSame('60000.00', $fromBase->total_rental_amount);
        $this->assertCount(12, $fromBase->installments);
        $this->assertSame([5000.0], $fromBase->installments->pluck('amount')->map(
            fn ($amount) => (float) $amount
        )->unique()->values()->all());

        $both = $service->createRental($this->user->id, $this->rentalData([
            'tenant_full_name' => 'Both amounts',
            'rental_duration' => 2,
            'base_rent_amount' => 416.66,
            'total_rental_amount' => 10000,
        ]));
        $this->assertSame('417.00', $both->base_rent_amount);
        $this->assertSame('10000.00', $both->total_rental_amount);
        $this->assertCount(24, $both->installments);
        $this->assertEquals(417, $both->installments->first()->amount);
        $this->assertEquals(409, $both->installments->last()->amount);
        $this->assertEquals(10000, $both->installments->sum('amount'));
    }

    public function test_amount_requests_accept_dot_zero_and_reject_fractional_values(): void
    {
        $storeData = $this->rentalData(['total_rental_amount' => '5000.00']);
        $this->assertFalse($this->validatorFor(StoreRentalRequest::class, $storeData)->fails());

        $storeData['total_rental_amount'] = 10000.5;
        $this->assertTrue($this->validatorFor(StoreRentalRequest::class, $storeData)->fails());

        $this->assertFalse($this->validatorFor(UpdateRentalRequest::class, [
            'base_rent_amount' => '5000.00',
        ])->fails());
        $this->assertTrue($this->validatorFor(UpdateRentalRequest::class, [
            'base_rent_amount' => 416.66,
        ])->fails());
        $this->assertFalse($this->validatorFor(UpdateRentalRequest::class, [
            'total_rental_amount' => 10000,
            'base_rent_amount' => 416.66,
        ])->fails());

        $renewData = [
            'rental_type' => 'annual',
            'rental_duration' => 1,
            'paying_plan' => 'monthly',
            'total_rental_amount' => '5000.00',
        ];
        $this->assertFalse($this->validatorFor(RenewRentalRequest::class, $renewData)->fails());

        $renewData['total_rental_amount'] = 10000.5;
        $this->assertTrue($this->validatorFor(RenewRentalRequest::class, $renewData)->fails());
        $renewData['total_rental_amount'] = 0;
        $this->assertTrue($this->validatorFor(RenewRentalRequest::class, $renewData)->fails());
    }

    public function test_renew_persists_whole_base_and_uneven_schedule(): void
    {
        $oldRental = $this->makeRental([
            'tenant_full_name' => 'Renew whole amount',
            'status' => 'ended',
        ]);

        $result = app(RentalService::class)->renewRental($this->user->id, $oldRental->id, [
            'rental_type' => 'annual',
            'rental_duration' => 2,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 10000,
            'currency' => 'SAR',
        ]);

        $renewed = RmRental::findOrFail($result['id']);
        $installments = $renewed->installments()->orderBy('sequence_no')->get();

        $this->assertSame('417.00', $renewed->base_rent_amount);
        $this->assertSame('10000.00', $renewed->total_rental_amount);
        $this->assertCount(24, $installments);
        $this->assertEquals(417, $installments->first()->amount);
        $this->assertEquals(409, $installments->last()->amount);
        $this->assertEquals(10000, $installments->sum('amount'));
    }

    public function test_generate_schedule_uses_modern_and_legacy_terms_and_rejects_duplicates(): void
    {
        $service = app(InstallmentService::class);

        foreach (['monthly' => 12, 'quarterly' => 4, 'annual' => 1] as $plan => $count) {
            $rental = $this->makeRental([
                'tenant_full_name' => "Modern {$plan}",
                'paying_plan' => $plan,
                'base_rent_amount' => null,
            ]);
            $contract = $this->makeContract($rental);
            $service->generateSchedule($contract);

            $this->assertCount($count, $contract->installments);
            $this->assertEquals(60000, $contract->installments()->sum('amount'));
        }

        $legacy = $this->makeRental([
            'tenant_full_name' => 'Legacy',
            'rental_type' => null,
            'rental_duration' => null,
            'rental_period' => 12,
            'paying_plan' => 'quarterly',
        ]);
        $legacyContract = $this->makeContract($legacy);
        $service->generateSchedule($legacyContract);
        $this->assertCount(4, $legacyContract->installments);

        $this->expectException(ValidationException::class);
        $service->generateSchedule($legacyContract);
    }

    public function test_generate_schedule_splits_uneven_total_into_whole_amounts(): void
    {
        $rental = $this->makeRental([
            'tenant_full_name' => 'Uneven generated schedule',
            'rental_duration' => 2,
            'base_rent_amount' => null,
            'total_rental_amount' => 10000,
        ]);
        $contract = $this->makeContract($rental);

        app(InstallmentService::class)->generateSchedule($contract);

        $installments = $contract->installments()->orderBy('sequence_no')->get();
        $this->assertSame('417.00', $rental->fresh()->base_rent_amount);
        $this->assertCount(24, $installments);
        $this->assertEquals(417, $installments->first()->amount);
        $this->assertEquals(409, $installments->last()->amount);
        $this->assertEquals(10000, $installments->sum('amount'));
    }

    public function test_contract_create_only_generates_when_requested(): void
    {
        $withoutSchedule = $this->makeRental(['tenant_full_name' => 'No schedule']);
        $contract = app(ContractService::class)->createContract(
            $withoutSchedule->id,
            $this->contractData(['generate_schedule' => false]),
            $this->user->id
        );
        $this->assertCount(0, $contract->installments);

        $withSchedule = $this->makeRental(['tenant_full_name' => 'With schedule']);
        $contract = app(ContractService::class)->createContract(
            $withSchedule->id,
            $this->contractData(['generate_schedule' => true]),
            $this->user->id
        );
        $this->assertCount(12, $contract->installments);
    }

    public function test_explicit_regeneration_keeps_survivors_and_history_and_appends_sequence_and_dates(): void
    {
        $rental = $this->makeRental();
        $contract = $this->makeContract($rental);
        $paid = $this->installment($rental, $contract, 1, '2026-01-01', 4000, 'paid', 4000);
        $partialPending = $this->installment($rental, $contract, 2, '2026-02-01', 4000, 'pending', 500);
        $void = $this->installment($rental, $contract, 3, '2026-03-01', 4000, 'void', 0);
        $cancelled = $this->installment($rental, $contract, 4, '2026-04-01', 4000, 'cancelled', 0);
        $this->installment($rental, $contract, 5, '2026-05-01', 4000, 'overdue', 0);

        app(InstallmentService::class)->regenerateSchedule($rental->id, $this->user->id);

        $this->assertDatabaseHas('rm_payment_installments', ['id' => $paid->id]);
        $this->assertDatabaseHas('rm_payment_installments', ['id' => $partialPending->id]);
        $this->assertDatabaseHas('rm_payment_installments', ['id' => $void->id, 'status' => 'void']);
        $this->assertDatabaseHas('rm_payment_installments', ['id' => $cancelled->id, 'status' => 'cancelled']);

        $rebuilt = $contract->installments()->where('sequence_no', '>', 5)->orderBy('sequence_no')->get();
        $this->assertCount(10, $rebuilt);
        $this->assertSame('2026-03-01', $rebuilt->first()->due_date->format('Y-m-d'));
        $this->assertEquals(5000, $rebuilt->first()->amount);
        $this->assertSame('58000.00', $rental->fresh()->total_rental_amount);
    }

    public function test_regeneration_rounds_fractional_stored_base_and_rebuilds_unpaid_rows(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(400);
        $originalIds = $contract->installments()->pluck('id')->all();
        $rental->update([
            'base_rent_amount' => 416.67,
            'total_rental_amount' => 5000.04,
        ]);

        $updated = app(InstallmentService::class)->regenerateSchedule(
            $rental->id,
            $this->user->id
        );

        $rebuilt = $contract->installments()->orderBy('sequence_no')->get();
        $this->assertSame('417.00', $updated->base_rent_amount);
        $this->assertSame('5004.00', $updated->total_rental_amount);
        $this->assertCount(12, $rebuilt);
        $this->assertNotSame($originalIds, $rebuilt->pluck('id')->all());
        $this->assertSame([417.0], $rebuilt->pluck('amount')->map(
            fn ($amount) => (float) $amount
        )->unique()->values()->all());
    }

    public function test_patch_base_amount_auto_regenerates_unpaid_schedule(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule();

        $updated = app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['base_rent_amount' => 5000, 'paying_plan' => 'monthly', 'currency' => 'SAR']
        );

        $this->assertSame('5000.00', $updated->base_rent_amount);
        $this->assertSame('60000.00', $updated->total_rental_amount);
        $this->assertCount(12, $contract->installments);
        $this->assertEquals(60000, $contract->installments()->sum('amount'));
        $this->assertEquals(5000, $updated->next_payment_amount);
    }

    public function test_mid_lease_amount_plan_and_duration_changes_follow_full_term_count_rules(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(4000);
        $paid = $contract->installments()->orderBy('sequence_no')->limit(3)->get();
        foreach ($paid as $item) {
            $item->update(['status' => 'paid', 'paid_amount' => 4000]);
        }

        $updated = app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['base_rent_amount' => 5000]
        );
        $this->assertSame('57000.00', $updated->total_rental_amount);
        $this->assertCount(9, $contract->installments()->where('status', 'pending')->get());
        $this->assertEquals(13, $contract->installments()->where('status', 'pending')->min('sequence_no'));

        $updated = app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['paying_plan' => 'quarterly']
        );
        $this->assertCount(1, $contract->installments()->where('status', 'pending')->get());

        $this->expectException(ValidationException::class);
        app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['rental_type' => 'monthly', 'rental_duration' => 3]
        );
    }

    public function test_duration_extension_rebuilds_twenty_one_after_three_survivors(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(4000);
        foreach ($contract->installments()->orderBy('sequence_no')->limit(3)->get() as $item) {
            $item->update(['status' => 'paid', 'paid_amount' => 4000]);
        }

        app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['rental_type' => 'annual', 'rental_duration' => 2]
        );

        $this->assertCount(21, $contract->installments()->where('status', 'pending')->get());
    }

    public function test_total_amount_is_a_notional_rate_base_and_tenant_only_update_does_not_regenerate(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(4000);
        foreach ($contract->installments()->orderBy('sequence_no')->limit(3)->get() as $item) {
            $item->update(['status' => 'paid', 'paid_amount' => 4000]);
        }

        $updated = app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['base_rent_amount' => 416.66, 'total_rental_amount' => 10000]
        );
        $this->assertSame('833.00', $updated->base_rent_amount);
        $this->assertSame('19497.00', $updated->total_rental_amount);
        $this->assertSame([833.0], $contract->installments()
            ->where('status', 'pending')
            ->pluck('amount')
            ->map(fn ($amount) => (float) $amount)
            ->unique()
            ->values()
            ->all());

        $ids = $contract->installments()->pluck('id')->all();
        app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['tenant_full_name' => 'Changed only']
        );
        $this->assertSame($ids, $contract->installments()->pluck('id')->all());
    }

    public function test_explicit_patch_rebuilds_without_field_change_and_equal_decimal_does_not(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(5000);
        $originalIds = $contract->installments()->pluck('id')->all();

        app(RentalService::class)->updateRental(
            $this->user->id,
            $rental->id,
            ['base_rent_amount' => '5000.00']
        );
        $this->assertSame($originalIds, $contract->installments()->pluck('id')->all());

        app(RentalService::class)->updateRental($this->user->id, $rental->id, [], true);
        $this->assertNotSame($originalIds, $contract->installments()->pluck('id')->all());
    }

    public function test_fully_invoiced_update_is_rejected_and_other_owner_is_not_found(): void
    {
        [$rental, $contract] = $this->rentalWithSchedule(5000);
        $contract->installments()->update(['status' => 'paid', 'paid_amount' => 5000]);

        try {
            app(RentalService::class)->updateRental(
                $this->user->id,
                $rental->id,
                ['base_rent_amount' => 6000]
            );
            $this->fail('Expected a fully invoiced validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('schedule', $exception->errors());
        }

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(RentalService::class)->updateRental(991002, $rental->id, ['tenant_full_name' => 'No']);
    }

    public function test_overdue_unpaid_is_next_payment_and_payments_cannot_mix_with_schedule_changes(): void
    {
        $rental = $this->makeRental();
        $contract = $this->makeContract($rental);
        $this->installment($rental, $contract, 1, now()->subMonth()->toDateString(), 5000, 'overdue', 0);

        $this->assertEquals(5000, $rental->fresh()->next_payment_amount);

        $request = \App\Http\Requests\Rms\Rental\UpdateRentalRequest::create('/', 'PATCH', [
            'payments' => [],
            'base_rent_amount' => 6000,
        ]);
        $validator = validator($request->request->all(), $request->rules());
        $request->withValidator($validator);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('payments', $validator->errors()->toArray());
    }

    private function rentalWithSchedule(float $base = 5000): array
    {
        $rental = $this->makeRental([
            'base_rent_amount' => $base,
            'total_rental_amount' => $base * 12,
        ]);
        $contract = $this->makeContract($rental);
        app(InstallmentService::class)->generateSchedule($contract);

        return [$rental->fresh(), $contract->fresh()];
    }

    private function makeRental(array $overrides = []): RmRental
    {
        return RmRental::create(array_merge([
            'user_id' => $this->user->id,
            'tenant_full_name' => 'Schedule Test',
            'tenant_phone' => '0500000000',
            'move_in_date' => '2026-01-01',
            'rental_type' => 'annual',
            'rental_duration' => 1,
            'rental_period' => null,
            'paying_plan' => 'monthly',
            'base_rent_amount' => 5000,
            'total_rental_amount' => 60000,
            'currency' => 'SAR',
            'status' => 'active',
        ], $overrides));
    }

    private function makeContract(RmRental $rental): RmContract
    {
        return RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $rental->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'grace_period_months' => 0,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function installment(
        RmRental $rental,
        RmContract $contract,
        int $sequence,
        string $dueDate,
        float $amount,
        string $status,
        float $paidAmount
    ): RmPaymentInstallment {
        return RmPaymentInstallment::create([
            'user_id' => $this->user->id,
            'rental_id' => $rental->id,
            'contract_id' => $contract->id,
            'sequence_no' => $sequence,
            'due_date' => $dueDate,
            'amount' => $amount,
            'status' => $status,
            'paid_amount' => $paidAmount,
            'payment_type' => 'none',
            'payment_status' => 'not_due',
        ]);
    }

    private function rentalData(array $overrides = []): array
    {
        return array_merge([
            'tenant_full_name' => 'Create Test',
            'tenant_phone' => '0500000001',
            'move_in_date' => '2026-01-01',
            'rental_type' => 'annual',
            'rental_duration' => 1,
            'paying_plan' => 'monthly',
            'currency' => 'SAR',
        ], $overrides);
    }

    private function contractData(array $overrides = []): array
    {
        return array_merge([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
            'grace_period_months' => 0,
        ], $overrides);
    }

    private function validatorFor(string $requestClass, array $data): \Illuminate\Validation\Validator
    {
        $request = $requestClass::create('/', 'POST', $data);
        $validator = validator($request->request->all(), $request->rules());

        if (method_exists($request, 'withValidator')) {
            $request->withValidator($validator);
        }

        return $validator;
    }

    private function createIsolatedTables(): void
    {
        $this->createTable('user_properties', function (Blueprint $table) {
            $table->id();
        });
        $this->createTable('user_projects', function (Blueprint $table) {
            $table->id();
        });
        $this->createTable('buildings', function (Blueprint $table) {
            $table->id();
        });

        $this->createTable('rm_rentals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->string('tenant_full_name', 150);
            $table->string('tenant_phone', 32);
            $table->string('tenant_email')->nullable();
            $table->string('tenant_job_title')->nullable();
            $table->string('tenant_social_status')->nullable();
            $table->string('tenant_national_id')->nullable();
            $table->decimal('base_rent_amount', 15, 2)->nullable();
            $table->decimal('total_rental_amount', 15, 2)->nullable();
            $table->char('currency', 3)->default('SAR');
            $table->string('rental_type')->nullable();
            $table->integer('rental_duration')->nullable();
            $table->integer('rental_period')->nullable();
            $table->string('paying_plan')->nullable();
            $table->string('contract_number')->nullable();
            $table->date('move_in_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->string('termination_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->createTable('rm_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rental_id')->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->index();
            $table->string('termination_reason')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('property_name')->nullable();
            $table->string('project_name')->nullable();
            $table->integer('grace_period_months')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->createTable('rm_payment_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rental_id')->index();
            $table->unsignedBigInteger('contract_id')->index();
            $table->integer('sequence_no');
            $table->date('due_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending')->index();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('notes')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'sequence_no']);
        });

        $this->createTable('rental_cost_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rental_id');
            $table->string('name')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('type')->nullable();
            $table->string('payer')->nullable();
            $table->string('payment_frequency')->nullable();
            $table->decimal('percentage_of', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function createTable(string $name, \Closure $definition): void
    {
        if (!Schema::hasTable($name)) {
            Schema::create($name, $definition);
            $this->createdTables[] = $name;
        }
    }
}
