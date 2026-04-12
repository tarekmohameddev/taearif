<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        $this->swapSlugUniqueIndex();

        $templates = DB::table('property_request_statuses')
            ->whereNull('user_id')
            ->whereIn('slug', ['in_progress', 'waiting'])
            ->get();

        if ($templates->isEmpty()) {
            return;
        }

        $tenants = DB::table('users')
            ->where('account_type', 'tenant')
            ->pluck('id');

        foreach ($tenants as $userId) {
            foreach ($templates as $tpl) {
                $exists = DB::table('property_request_statuses')
                    ->where('user_id', $userId)
                    ->where('slug', $tpl->slug)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('property_request_statuses')->insert([
                    'user_id' => $userId,
                    'name_ar' => $tpl->name_ar,
                    'name_en' => $tpl->name_en,
                    'slug' => $tpl->slug,
                    'display_order' => $tpl->display_order,
                    'is_active' => $tpl->is_active,
                    'is_system' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        foreach ($templates as $tpl) {
            $rows = DB::table('users_property_requests as upr')
                ->join('users', 'users.id', '=', 'upr.user_id')
                ->where('upr.status_id', $tpl->id)
                ->select('upr.id as req_id', DB::raw('COALESCE(users.tenant_id, users.id) as owner_id'))
                ->get();

            foreach ($rows as $row) {
                $newStatusId = DB::table('property_request_statuses')
                    ->where('user_id', $row->owner_id)
                    ->where('slug', $tpl->slug)
                    ->value('id');

                if ($newStatusId) {
                    DB::table('users_property_requests')
                        ->where('id', $row->req_id)
                        ->update(['status_id' => $newStatusId]);
                }
            }
        }

        DB::table('property_request_statuses')
            ->whereNull('user_id')
            ->whereIn('slug', ['in_progress', 'waiting'])
            ->delete();
    }

    public function down(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        $this->restoreSlugUniqueIndex();
    }

    private function swapSlugUniqueIndex(): void
    {
        $indexNames = $this->indexNamesOn('property_request_statuses');

        if ($indexNames->contains('property_request_statuses_slug_unique')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        }

        $indexNamesAfter = $this->indexNamesOn('property_request_statuses');
        if (!$indexNamesAfter->contains('property_request_statuses_user_id_slug_unique')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->unique(['user_id', 'slug']);
            });
        }
    }

    private function restoreSlugUniqueIndex(): void
    {
        $indexNames = $this->indexNamesOn('property_request_statuses');

        if ($indexNames->contains('property_request_statuses_user_id_slug_unique')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'slug']);
            });
        }

        $indexNamesAfter = $this->indexNamesOn('property_request_statuses');
        if (!$indexNamesAfter->contains('property_request_statuses_slug_unique')) {
            Schema::table('property_request_statuses', function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function indexNamesOn(string $table): \Illuminate\Support\Collection
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return collect(DB::select('SHOW INDEX FROM `'.$table.'`'))
                ->pluck('Key_name')
                ->unique()
                ->values();
        }

        if ($driver === 'pgsql') {
            return collect(DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ?',
                [$table]
            ))->pluck('indexname');
        }

        return collect();
    }
};
