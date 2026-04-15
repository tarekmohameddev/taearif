<?php

namespace App\Console\Commands;

use App\Models\Api\Rms\RmRental;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpireRentals extends Command
{
    protected $signature = 'app:expire-rentals';

    protected $description = 'Set active rentals past end_date to inactive and refresh property status';

    public function handle(): int
    {
        $today = Carbon::now('Asia/Riyadh')->toDateString();
        $updated = 0;

        RmRental::query()
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->orderBy('id')
            ->chunkById(100, function ($rentals) use (&$updated) {
                foreach ($rentals as $rental) {
                    $rental->update(['status' => 'inactive']);
                    if ($rental->unit_id) {
                        $property = Property::find($rental->unit_id);
                        $property?->updatePropertyStatus();
                    }
                    $updated++;
                }
            });

        $this->info("Expired {$updated} rental(s) with end_date before {$today}.");

        return Command::SUCCESS;
    }
}
