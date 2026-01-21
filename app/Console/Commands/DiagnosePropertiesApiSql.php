<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Compare SQL counts for properties (complete vs drafts) with the logic used by
 * GET api/properties (index) and GET api/properties/drafts (listDrafts).
 *
 * Usage:
 *   php artisan app:diagnose-properties-api-sql --user-id=1037 --as-owner
 *   php artisan app:diagnose-properties-api-sql --token="3047|uahD3zAkkIoIgCayvGoFcrqT6tPGGa1Yz3CGvK1f14a54d22"
 *   php artisan app:diagnose-properties-api-sql --user-id=1037 --as-owner --clear-cache --show-ids
 */
class DiagnosePropertiesApiSql extends Command
{
    protected $signature = 'app:diagnose-properties-api-sql
                            {--user-id= : Assume this user; with --as-owner treat as ownerId}
                            {--token= : Bearer token to resolve auth user (format: id|plainTextToken)}
                            {--as-owner : With --user-id, treat user as ownerId}
                            {--compare-user-id= : User ID for "your SQL" and restricted counts (default: ownerId)}
                            {--clear-cache : Clear properties_list_* cache before (uses Cache::flush if no Redis pattern)}
                            {--show-ids : List property IDs that differ (complete without content; drafts pending_review/NULL)}';

    protected $description = 'Compare API (index/drafts) counts with raw SQL for properties; diagnose mismatches';

    public function handle(): int
    {
        $userId = $this->option('user-id');
        $token = $this->option('token');
        $asOwner = $this->option('as-owner');
        $compareUserId = $this->option('compare-user-id') ? (int) $this->option('compare-user-id') : null;

        if (!$userId && !$token) {
            $this->error('Provide either --user-id= or --token=.');
            return self::FAILURE;
        }

        $user = null;
        $ownerId = null;
        $allowedUserIds = [];

        if ($token) {
            $plain = str_contains($token, '|') ? substr($token, strpos($token, '|') + 1) : $token;
            $pat = PersonalAccessToken::findToken($plain);
            if (!$pat || !$pat->tokenable) {
                $this->error('Token invalid or expired.');
                return self::FAILURE;
            }
            $user = $pat->tokenable;
            if (!$user instanceof User) {
                $this->error('Token does not belong to a User.');
                return self::FAILURE;
            }
            $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
            $ownerId = (int) $owner->id;
            $allowedUserIds = $this->getAllowedUserIds($ownerId);
        } else {
            $user = User::find((int) $userId);
            if (!$user) {
                $this->error("User --user-id={$userId} not found.");
                return self::FAILURE;
            }
            if ($asOwner) {
                $ownerId = (int) $user->id;
                $allowedUserIds = $this->getAllowedUserIds($ownerId);
            } else {
                $owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
                $ownerId = (int) $owner->id;
                $allowedUserIds = $this->getAllowedUserIds($ownerId);
            }
        }

        if ($compareUserId === null) {
            $compareUserId = $ownerId;
        }
        if (!in_array($compareUserId, $allowedUserIds, true)) {
            $this->warn("--compare-user-id={$compareUserId} is not in allowedUserIds; counts for 'your SQL' and 'user_id=compare' may be empty or not comparable.");
        }

        if ($this->option('clear-cache')) {
            $this->clearPropertiesListCache($ownerId);
        }

        $contentExists = function ($q) {
            $q->whereNotNull('title')
                ->where('title', '!=', '')
                ->whereNotNull('address')
                ->where('address', '!=', '');
        };

        // 1) Index-equivalent (complete, all allowedUserIds)
        $countIndexAll = Property::whereIn('user_id', $allowedUserIds)
            ->where('completion_status', 'complete')
            ->whereHas('contents', $contentExists)
            ->count();

        // 2) Index-equivalent (complete, user_id = compareUserId only)
        $countIndexCompare = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'complete')
            ->whereHas('contents', $contentExists)
            ->count();

        // 3) Your "complete" SQL (user_id = compareUserId, completion_status = 'complete', no content filter)
        $countYourComplete = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'complete')
            ->count();

        // 4) Drafts-equivalent (all)
        $countDraftsAll = Property::whereIn('user_id', $allowedUserIds)
            ->where(function ($q) {
                $q->where('completion_status', '!=', 'complete')
                    ->orWhereNull('completion_status');
            })
            ->count();

        // 5) Drafts-equivalent (user_id = compareUserId)
        $countDraftsCompare = Property::where('user_id', $compareUserId)
            ->where(function ($q) {
                $q->where('completion_status', '!=', 'complete')
                    ->orWhereNull('completion_status');
            })
            ->count();

        // 6) Your "incomplete" SQL (user_id = compareUserId, completion_status = 'incomplete' only)
        $countYourIncomplete = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'incomplete')
            ->count();

        $authId = $user ? (int) $user->id : null;
        $this->info('Context');
        $this->table(
            ['Key', 'Value'],
            [
                ['owner_id', (string) $ownerId],
                ['auth_user_id', $authId !== null ? (string) $authId : 'n/a'],
                ['allowed_user_ids', implode(', ', $allowedUserIds)],
                ['compare_user_id', (string) $compareUserId],
            ]
        );
        $this->newLine();

        $this->info('Counts');
        $this->table(
            ['Label', 'Count'],
            [
                ['complete (index-equivalent, all allowedUserIds)', (string) $countIndexAll],
                ['complete (index-equivalent, user_id=' . $compareUserId . ')', (string) $countIndexCompare],
                ['complete (your SQL: user_id=' . $compareUserId . ', completion_status=complete)', (string) $countYourComplete],
                ['drafts (drafts-equivalent, all)', (string) $countDraftsAll],
                ['drafts (drafts-equivalent, user_id=' . $compareUserId . ')', (string) $countDraftsCompare],
                ['incomplete (your SQL: user_id=' . $compareUserId . ', completion_status=incomplete)', (string) $countYourIncomplete],
            ]
        );

        if ($this->option('show-ids')) {
            $this->newLine();
            $this->showIdDiffs($compareUserId, $contentExists);
        }

        $this->newLine();
        $this->info('Compare pagination.total from GET /api/properties?page=1 with "complete (index-equivalent, all)".');
        $this->info('Compare pagination.total from GET /api/properties/drafts with "drafts (drafts-equivalent, all)".');
        $this->info('If index shows fewer than your SQL, some complete properties lack non-empty title+address in user_property_contents.');

        return self::SUCCESS;
    }

    protected function getAllowedUserIds(int $ownerId): array
    {
        $employeeIds = \App\Models\User::where('tenant_id', $ownerId)
            ->where('account_type', 'employee')
            ->pluck('id')
            ->toArray();
        return array_values(array_unique(array_merge([$ownerId], $employeeIds)));
    }

    protected function clearPropertiesListCache(int $ownerId): void
    {
        $store = Cache::getStore();
        if (method_exists($store, 'connection')) {
            $conn = $store->connection();
            if (is_object($conn) && method_exists($conn, 'keys')) {
                $keys = @$conn->keys('*properties_list*') ?: [];
                $count = 0;
                foreach ($keys as $key) {
                    @$conn->del($key);
                    $count++;
                }
                $this->info("Cleared {$count} properties_list_* cache key(s).");
                return;
            }
        }
        Cache::flush();
        $this->warn('Cleared full cache (Redis pattern delete not available). Run GET /api/properties after this to repopulate.');
    }

    protected function showIdDiffs(int $compareUserId, callable $contentExists): void
    {
        $this->info('ID diffs (--show-ids)');

        // Complete (your SQL) but NOT in index-equivalent => missing valid content
        $idsYourComplete = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'complete')
            ->pluck('id')
            ->toArray();
        $idsIndexCompare = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'complete')
            ->whereHas('contents', $contentExists)
            ->pluck('id')
            ->toArray();
        $missingContent = array_values(array_diff($idsYourComplete, $idsIndexCompare));
        if (!empty($missingContent)) {
            $this->warn('Complete (your SQL) but missing from index (no non-empty title+address in user_property_contents): ' . implode(', ', $missingContent));
        } else {
            $this->line('Complete: no IDs missing from index (all have valid content).');
        }

        // In drafts (drafts-equivalent, user_id=compare) but NOT in your incomplete => pending_review or NULL
        $idsDraftsCompare = Property::where('user_id', $compareUserId)
            ->where(function ($q) {
                $q->where('completion_status', '!=', 'complete')->orWhereNull('completion_status');
            })
            ->pluck('id')
            ->toArray();
        $idsYourIncomplete = Property::where('user_id', $compareUserId)
            ->where('completion_status', 'incomplete')
            ->pluck('id')
            ->toArray();
        $extraDrafts = array_values(array_diff($idsDraftsCompare, $idsYourIncomplete));
        if (!empty($extraDrafts)) {
            $this->warn('In drafts (API) but not in your incomplete SQL (completion_status is pending_review or NULL): ' . implode(', ', $extraDrafts));
        } else {
            $this->line('Drafts: no extra IDs vs your incomplete (all drafts are completion_status=incomplete).');
        }
    }
}
