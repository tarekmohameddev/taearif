<?php

namespace Tests\Feature;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_adds_platform_fee_and_water_fee_columns_to_rm_rentals_table()
    {
        // Run the migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144108_add_platform_fee_and_water_fee_to_rm_rentals_table.php']);

        // Assert columns exist
        $this->assertTrue(Schema::hasColumn('rm_rentals', 'platform_fee'));
        $this->assertTrue(Schema::hasColumn('rm_rentals', 'water_fee'));

        // Assert column types
        $platformFeeColumn = Schema::getColumnType('rm_rentals', 'platform_fee');
        $waterFeeColumn = Schema::getColumnType('rm_rentals', 'water_fee');

        $this->assertEquals('decimal', $platformFeeColumn);
        $this->assertEquals('decimal', $waterFeeColumn);
    }

    /** @test */
    public function it_removes_platform_fee_and_water_fee_monthly_columns_from_rm_contracts_table()
    {
        // First, ensure the columns exist (they should from previous migrations)
        // Then run the removal migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144127_remove_platform_fee_and_water_fee_monthly_from_rm_contracts_table.php']);

        // Assert columns are removed
        $this->assertFalse(Schema::hasColumn('rm_contracts', 'platform_fee'));
        $this->assertFalse(Schema::hasColumn('rm_contracts', 'water_fee_monthly'));
    }

    /** @test */
    public function it_migrates_data_from_contracts_to_rentals()
    {
        // Create test data
        $user = User::factory()->create();
        
        $rental = RmRental::create([
            'user_id' => $user->id,
            'tenant_full_name' => 'John Doe',
            'tenant_phone' => '+1234567890',
            'status' => 'active'
        ]);

        // Create multiple contracts with different values
        $contract1 = RmContract::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-06-30',
            'status' => 'expired',
            'platform_fee' => 50.00,
            'water_fee_monthly' => 30.00,
            'created_at' => now()->subDays(10)
        ]);

        $contract2 = RmContract::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'contract_number' => 'CNT-2024-00002',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'platform_fee' => 75.00,
            'water_fee_monthly' => 40.00,
            'created_at' => now()->subDays(5) // More recent
        ]);

        $contract3 = RmContract::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'contract_number' => 'CNT-2024-00003',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'status' => 'pending',
            'platform_fee' => 60.00,
            'water_fee_monthly' => 35.00,
            'created_at' => now()->subDays(1) // Most recent
        ]);

        // Run the data migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144142_migrate_platform_fee_and_water_fee_from_contracts_to_rentals.php']);

        // Refresh the rental model
        $rental->refresh();

        // Assert that the rental has the values from the latest contract (contract3)
        $this->assertEquals(60.00, $rental->platform_fee);
        $this->assertEquals(35.00, $rental->water_fee);
    }

    /** @test */
    public function it_handles_rentals_with_no_contracts()
    {
        // Create a rental without any contracts
        $user = User::factory()->create();
        
        $rental = RmRental::create([
            'user_id' => $user->id,
            'tenant_full_name' => 'Jane Doe',
            'tenant_phone' => '+0987654321',
            'status' => 'active'
        ]);

        // Run the data migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144142_migrate_platform_fee_and_water_fee_from_contracts_to_rentals.php']);

        // Refresh the rental model
        $rental->refresh();

        // Assert that the rental still has null values
        $this->assertNull($rental->platform_fee);
        $this->assertNull($rental->water_fee);
    }

    /** @test */
    public function it_handles_contracts_with_null_values()
    {
        // Create test data with null values
        $user = User::factory()->create();
        
        $rental = RmRental::create([
            'user_id' => $user->id,
            'tenant_full_name' => 'Bob Smith',
            'tenant_phone' => '+1122334455',
            'status' => 'active'
        ]);

        $contract = RmContract::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'platform_fee' => null,
            'water_fee_monthly' => null
        ]);

        // Run the data migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144142_migrate_platform_fee_and_water_fee_from_contracts_to_rentals.php']);

        // Refresh the rental model
        $rental->refresh();

        // Assert that the rental has null values
        $this->assertNull($rental->platform_fee);
        $this->assertNull($rental->water_fee);
    }

    /** @test */
    public function it_handles_contracts_with_partial_values()
    {
        // Create test data with only one field having a value
        $user = User::factory()->create();
        
        $rental = RmRental::create([
            'user_id' => $user->id,
            'tenant_full_name' => 'Alice Johnson',
            'tenant_phone' => '+5566778899',
            'status' => 'active'
        ]);

        $contract = RmContract::create([
            'user_id' => $user->id,
            'rental_id' => $rental->id,
            'contract_number' => 'CNT-2024-00001',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'active',
            'platform_fee' => 50.00,
            'water_fee_monthly' => null
        ]);

        // Run the data migration
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144142_migrate_platform_fee_and_water_fee_from_contracts_to_rentals.php']);

        // Refresh the rental model
        $rental->refresh();

        // Assert that the rental has the correct values
        $this->assertEquals(50.00, $rental->platform_fee);
        $this->assertNull($rental->water_fee);
    }

    /** @test */
    public function it_can_rollback_migrations()
    {
        // Run the migrations
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144108_add_platform_fee_and_water_fee_to_rm_rentals_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2025_09_10_144127_remove_platform_fee_and_water_fee_monthly_from_rm_contracts_table.php']);

        // Rollback the migrations
        $this->artisan('migrate:rollback', ['--step' => 2]);

        // Assert columns are back to original state
        $this->assertFalse(Schema::hasColumn('rm_rentals', 'platform_fee'));
        $this->assertFalse(Schema::hasColumn('rm_rentals', 'water_fee'));
        // Note: We can't easily test the contract columns being restored without knowing the original schema
    }
}
