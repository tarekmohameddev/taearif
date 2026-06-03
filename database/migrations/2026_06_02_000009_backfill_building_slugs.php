<?php

use App\Models\Building;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('buildings', 'slug')) {
            return;
        }

        Building::query()
            ->where(function ($query) {
                $query->whereNull('slug')->orWhere('slug', '');
            })
            ->orderBy('id')
            ->chunkById(100, function ($buildings): void {
                foreach ($buildings as $building) {
                    if (filled($building->name) && $building->user_id) {
                        $building->slug = Building::generateUniqueSlug(
                            $building->name,
                            (int) $building->user_id,
                            $building->id
                        );
                    } else {
                        $building->slug = 'building-' . $building->id;
                    }

                    $building->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        // Slugs are left in place on rollback.
    }
};
