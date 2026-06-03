<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Schema;

trait EnsuresPropertyStatusColumns
{
    private static bool $propertyStatusColumnsEnsured = false;

    protected function ensurePropertyStatusColumns(): void
    {
        if (self::$propertyStatusColumnsEnsured) {
            return;
        }

        if (! Schema::hasColumn('user_properties', 'listing_purpose')) {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_05_31_000001_add_listing_status_columns_to_user_properties.php',
                '--force' => true,
            ]);
        }

        if (Schema::hasColumn('user_properties', 'listing_purpose')
            && ! $this->propertyStatusBackfillApplied()) {
            $this->artisan('migrate', [
                '--path' => 'database/migrations/2026_05_31_000002_backfill_listing_status_columns.php',
                '--force' => true,
            ]);
        }

        self::$propertyStatusColumnsEnsured = true;
    }

    private function propertyStatusBackfillApplied(): bool
    {
        return \Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', '2026_05_31_000002_backfill_listing_status_columns')
            ->exists();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();
    }
}
