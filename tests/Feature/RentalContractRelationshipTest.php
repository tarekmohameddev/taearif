<?php

namespace Tests\Feature;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalContractRelationshipTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $rental;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->rental = RmRental::create([
            'user_id' => $this->user->id,
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'status' => 'active'
        ]);
    }

    /** @test */
    public function rental_has_many_contracts()
    {
        // Create multiple contracts for the rental
        $contract1 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired'
        ]);

        $contract2 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        // Test the relationship
        $this->assertCount(2, $this->rental->contracts);
        $this->assertTrue($this->rental->contracts->contains($contract1));
        $this->assertTrue($this->rental->contracts->contains($contract2));
    }

    /** @test */
    public function rental_has_one_active_contract()
    {
        // Create contracts with different statuses
        RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired'
        ]);

        $activeContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        // Test the active contract relationship
        $this->assertNotNull($this->rental->activeContract);
        $this->assertEquals($activeContract->id, $this->rental->activeContract->id);
        $this->assertEquals('active', $this->rental->activeContract->status);
    }

    /** @test */
    public function rental_has_latest_contract()
    {
        // Create contracts with different creation times
        $contract1 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired',
            'created_at' => now()->subDays(10)
        ]);

        $contract2 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'created_at' => now()->subDays(5)
        ]);

        $latestContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00003',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'pending',
            'created_at' => now()->subDays(1)
        ]);

        // Test the latest contract relationship
        $this->assertNotNull($this->rental->latestContract);
        $this->assertEquals($latestContract->id, $this->rental->latestContract->id);
    }

    /** @test */
    public function rental_has_expired_contracts()
    {
        // Create contracts with different statuses
        $expiredContract1 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired'
        ]);

        $expiredContract2 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'status' => 'expired'
        ]);

        RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00003',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'active'
        ]);

        // Test the expired contracts relationship
        $expiredContracts = $this->rental->expiredContracts;
        $this->assertCount(2, $expiredContracts);
        $this->assertTrue($expiredContracts->contains($expiredContract1));
        $this->assertTrue($expiredContracts->contains($expiredContract2));
    }

    /** @test */
    public function rental_has_pending_contracts()
    {
        // Create contracts with different statuses
        RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired'
        ]);

        $pendingContract1 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'pending'
        ]);

        $pendingContract2 = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00003',
            'start_date' => '2025-06-01',
            'end_date' => '2026-05-31',
            'status' => 'pending'
        ]);

        // Test the pending contracts relationship
        $pendingContracts = $this->rental->pendingContracts;
        $this->assertCount(2, $pendingContracts);
        $this->assertTrue($pendingContracts->contains($pendingContract1));
        $this->assertTrue($pendingContracts->contains($pendingContract2));
    }

    /** @test */
    public function contract_belongs_to_rental()
    {
        $contract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        // Test the relationship
        $this->assertNotNull($contract->rental);
        $this->assertEquals($this->rental->id, $contract->rental->id);
        $this->assertEquals($this->rental->tenant_full_name, $contract->rental->tenant_full_name);
    }

    /** @test */
    public function contract_status_helper_methods()
    {
        $activeContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        $expiredContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'status' => 'expired'
        ]);

        $pendingContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00003',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'pending'
        ]);

        $terminatedContract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00004',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'terminated'
        ]);

        // Test status helper methods
        $this->assertTrue($activeContract->isActive());
        $this->assertFalse($activeContract->isExpired());
        $this->assertFalse($activeContract->isPending());
        $this->assertFalse($activeContract->isTerminated());

        $this->assertTrue($expiredContract->isExpired());
        $this->assertFalse($expiredContract->isActive());
        $this->assertFalse($expiredContract->isPending());
        $this->assertFalse($expiredContract->isTerminated());

        $this->assertTrue($pendingContract->isPending());
        $this->assertFalse($pendingContract->isActive());
        $this->assertFalse($pendingContract->isExpired());
        $this->assertFalse($pendingContract->isTerminated());

        $this->assertTrue($terminatedContract->isTerminated());
        $this->assertFalse($terminatedContract->isActive());
        $this->assertFalse($terminatedContract->isExpired());
        $this->assertFalse($terminatedContract->isPending());
    }

    /** @test */
    public function rental_with_contracts_can_be_loaded_eagerly()
    {
        // Create contracts
        RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2023-01-01',
            'end_date' => '2023-12-31',
            'status' => 'expired'
        ]);

        // Test eager loading
        $rentalWithContracts = RmRental::with(['contracts', 'activeContract', 'latestContract', 'expiredContracts', 'pendingContracts'])
            ->find($this->rental->id);

        $this->assertNotNull($rentalWithContracts);
        $this->assertCount(2, $rentalWithContracts->contracts);
        $this->assertNotNull($rentalWithContracts->activeContract);
        $this->assertNotNull($rentalWithContracts->latestContract);
        $this->assertCount(1, $rentalWithContracts->expiredContracts);
        $this->assertCount(0, $rentalWithContracts->pendingContracts);
    }

    /** @test */
    public function contract_can_access_rental_data()
    {
        $contract = RmContract::create([
            'user_id' => $this->user->id,
            'rental_id' => $this->rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active'
        ]);

        // Test accessing rental data through contract
        $this->assertEquals($this->rental->tenant_full_name, $contract->rental->tenant_full_name);
        $this->assertEquals($this->rental->tenant_phone, $contract->rental->tenant_phone);
        $this->assertEquals($this->rental->status, $contract->rental->status);
    }
}
