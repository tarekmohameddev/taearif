<?php

use App\Services\Property\PropertyStatusSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $metrics = [
            'updated' => 0,
            'null_listing_purpose' => 0,
            'violations' => 0,
        ];

        DB::table('user_properties')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$metrics) {
                foreach ($rows as $row) {
                    $listingPurpose = PropertyStatusSyncService::resolveListingPurposeFromLegacy(
                        $row->purpose,
                        $row->property_status
                    );
                    $unitStatus = PropertyStatusSyncService::resolveUnitStatusFromLegacy(
                        $row->purpose,
                        $row->property_status
                    );
                    $publishStatus = PropertyStatusSyncService::resolvePublishStatusFromLegacy(
                        $row->completion_status,
                        $row->status
                    );

                    if ($listingPurpose === null) {
                        $metrics['null_listing_purpose']++;
                    }

                    if (
                        ($listingPurpose === 'sale' && $unitStatus === 'rented')
                        || ($listingPurpose === 'rent' && $unitStatus === 'sold')
                    ) {
                        $metrics['violations']++;
                    }

                    DB::table('user_properties')
                        ->where('id', $row->id)
                        ->update([
                            'listing_purpose' => $listingPurpose,
                            'unit_status' => $unitStatus,
                            'publish_status' => $publishStatus,
                        ]);

                    $metrics['updated']++;
                }
            });

        Log::info('Property status backfill completed', $metrics);
    }

    public function down(): void
    {
        DB::table('user_properties')->update([
            'listing_purpose' => null,
            'unit_status' => null,
            'publish_status' => null,
        ]);
    }
};
