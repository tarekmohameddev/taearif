<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_property_requests')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('users_property_requests', 'initial_property_id')) {
                $table->unsignedBigInteger('initial_property_id')->nullable()->after('property_ids');
                $table->index('initial_property_id');
                $table->foreign('initial_property_id')
                    ->references('id')
                    ->on('user_properties')
                    ->nullOnDelete();
            }
        });

        $this->backfillInitialPropertyIds();
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_property_requests')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'initial_property_id')) {
                $table->dropForeign(['initial_property_id']);
                $table->dropIndex(['initial_property_id']);
                $table->dropColumn('initial_property_id');
            }
        });
    }

    private function backfillInitialPropertyIds(): void
    {
        if (! Schema::hasColumn('users_property_requests', 'initial_property_id')) {
            return;
        }

        DB::table('users_property_requests')
            ->where('source', 'property_interest')
            ->whereNull('initial_property_id')
            ->whereNotNull('property_ids')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $propertyIds = json_decode($row->property_ids, true);
                    if (! is_array($propertyIds) || $propertyIds === []) {
                        continue;
                    }

                    $firstId = (int) ($propertyIds[0] ?? 0);
                    if ($firstId <= 0) {
                        continue;
                    }

                    $belongsToTenant = DB::table('user_properties')
                        ->where('id', $firstId)
                        ->where('user_id', $row->user_id)
                        ->exists();

                    if (! $belongsToTenant) {
                        continue;
                    }

                    DB::table('users_property_requests')
                        ->where('id', $row->id)
                        ->whereNull('initial_property_id')
                        ->update(['initial_property_id' => $firstId]);
                }
            });
    }
};
