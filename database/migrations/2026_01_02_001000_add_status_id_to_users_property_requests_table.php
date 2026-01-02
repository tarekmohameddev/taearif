<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->foreignId('status_id')
                ->nullable()
                ->after('status')
                ->constrained('property_request_statuses')
                ->nullOnDelete();
        });

        $statusLookup = DB::table('property_request_statuses')
            ->pluck('id', 'name_ar')
            ->mapWithKeys(fn ($id, $name) => [trim($name) => $id]);

        DB::table('users_property_requests')
            ->select('id', 'status')
            ->orderBy('id')
            ->chunkById(500, function ($requests) use ($statusLookup) {
                foreach ($requests as $request) {
                    $statusName = trim((string) $request->status);
                    $statusId = $statusLookup[$statusName] ?? null;

                    if ($statusId) {
                        DB::table('users_property_requests')
                            ->where('id', $request->id)
                            ->update(['status_id' => $statusId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('status_id');
        });
    }
};

