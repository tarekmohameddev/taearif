<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Api\Rms\RmRental;
use App\Models\User\RealestateManagement\Property;

class FixRentalBuildingIds extends Command
{
    protected $signature = 'rentals:fix-building-ids';
    protected $description = 'Fix missing building_id in rm_rentals table by populating from properties';

    public function handle()
    {
        $this->info('Starting to fix building IDs in rentals...');

        // Get all rentals with null building_id but with unit_id
        $rentals = RmRental::whereNull('building_id')
            ->whereNotNull('unit_id')
            ->get();

        $this->info("Found {$rentals->count()} rentals with missing building_id");

        $updated = 0;
        $skipped = 0;

        foreach ($rentals as $rental) {
            $property = Property::find($rental->unit_id);
            
            if ($property && $property->building_id) {
                $rental->building_id = $property->building_id;
                $rental->save();
                $updated++;
                $this->line("✓ Updated rental #{$rental->id} with building_id: {$property->building_id}");
            } else {
                $skipped++;
                $this->warn("✗ Skipped rental #{$rental->id} - property has no building_id");
            }
        }

        $this->newLine();
        $this->info("Completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Skipped', $skipped],
                ['Total', $rentals->count()],
            ]
        );

        return 0;
    }
}

