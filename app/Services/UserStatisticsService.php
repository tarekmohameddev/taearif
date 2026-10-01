<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Provides reusable, user-scoped counters without loading related models.
 *
 * Message counters are per inbound message. An inbound message is considered
 * replied to when a later outbound message exists in the same conversation;
 * otherwise it is considered abandoned.
 */
class UserStatisticsService
{
    private const EMPTY_STATISTICS = [
        'incoming_messages_count' => 0,
        'replied_messages_count' => 0,
        'abandoned_messages_count' => 0,
        'properties_count' => 0,
        'rental_properties_count' => 0,
        'sale_properties_count' => 0,
        'matches_count' => 0,
    ];

    /**
     * Return the counters for one user.
     *
     * @param  User|int  $user
     * @return array<string, int>
     */
    public function forUser($user): array
    {
        $userId = $user instanceof User ? (int) $user->getKey() : (int) $user;

        return $this->forUserIds([$userId])[$userId] ?? self::EMPTY_STATISTICS;
    }

    /**
     * Return counters keyed by user ID. This always runs three aggregate
     * queries, regardless of how many users are requested.
     *
     * @param  iterable<User|int>  $users
     * @return array<int, array<string, int>>
     */
    public function forUsers(iterable $users): array
    {
        $userIds = [];

        foreach ($users as $user) {
            $userId = $user instanceof User ? (int) $user->getKey() : (int) $user;
            if ($userId > 0) {
                $userIds[] = $userId;
            }
        }

        return $this->forUserIds($userIds);
    }

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, array<string, int>>
     */
    public function forUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $userIds),
            static fn (int $userId): bool => $userId > 0
        )));

        if ($userIds === []) {
            return [];
        }

        $statistics = [];
        foreach ($userIds as $userId) {
            $statistics[$userId] = self::EMPTY_STATISTICS;
        }

        $this->addMessageStatistics($statistics, $userIds);
        $this->addPropertyStatistics($statistics, $userIds);
        $this->addMatchStatistics($statistics, $userIds);

        return $statistics;
    }

    /**
     * @param  array<int, array<string, int>>  $statistics
     * @param  array<int, int>  $userIds
     */
    private function addMessageStatistics(array &$statistics, array $userIds): void
    {
        $rows = DB::table('messages as inbound')
            ->whereIn('inbound.user_id', $userIds)
            ->where('inbound.direction', 'inbound')
            ->select('inbound.user_id')
            ->selectRaw('COUNT(*) as incoming_messages_count')
            ->selectRaw(
                "SUM(CASE WHEN EXISTS (
                    SELECT 1
                    FROM messages AS reply
                    WHERE reply.conversation_id = inbound.conversation_id
                      AND reply.user_id = inbound.user_id
                      AND reply.direction = 'outbound'
                      AND (
                          reply.created_at > inbound.created_at
                          OR (reply.created_at = inbound.created_at AND reply.id > inbound.id)
                      )
                ) THEN 1 ELSE 0 END) as replied_messages_count"
            )
            ->groupBy('inbound.user_id')
            ->get();

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $incoming = (int) $row->incoming_messages_count;
            $replied = (int) $row->replied_messages_count;

            $statistics[$userId]['incoming_messages_count'] = $incoming;
            $statistics[$userId]['replied_messages_count'] = $replied;
            $statistics[$userId]['abandoned_messages_count'] = $incoming - $replied;
        }
    }

    /**
     * @param  array<int, array<string, int>>  $statistics
     * @param  array<int, int>  $userIds
     */
    private function addPropertyStatistics(array &$statistics, array $userIds): void
    {
        // listing_purpose is authoritative. The legacy purpose fallback keeps
        // old rows useful until all historical properties have been backfilled.
        $normalizedPurpose = "CASE
            WHEN listing_purpose IN ('sale', 'rent') THEN listing_purpose
            WHEN purpose IN ('sale', 'sold') THEN 'sale'
            WHEN purpose IN ('rent', 'rented') THEN 'rent'
            ELSE NULL
        END";

        $rows = DB::table('user_properties')
            ->whereIn('user_id', $userIds)
            ->select('user_id')
            ->selectRaw('COUNT(*) as properties_count')
            ->selectRaw("SUM(CASE WHEN {$normalizedPurpose} = 'rent' THEN 1 ELSE 0 END) as rental_properties_count")
            ->selectRaw("SUM(CASE WHEN {$normalizedPurpose} = 'sale' THEN 1 ELSE 0 END) as sale_properties_count")
            ->groupBy('user_id')
            ->get();

        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $statistics[$userId]['properties_count'] = (int) $row->properties_count;
            $statistics[$userId]['rental_properties_count'] = (int) $row->rental_properties_count;
            $statistics[$userId]['sale_properties_count'] = (int) $row->sale_properties_count;
        }
    }

    /**
     * @param  array<int, array<string, int>>  $statistics
     * @param  array<int, int>  $userIds
     */
    private function addMatchStatistics(array &$statistics, array $userIds): void
    {
        $rows = DB::table('property_matches')
            ->whereIn('user_id', $userIds)
            ->select('user_id')
            ->selectRaw('COUNT(*) as matches_count')
            ->groupBy('user_id')
            ->get();

        foreach ($rows as $row) {
            $statistics[(int) $row->user_id]['matches_count'] = (int) $row->matches_count;
        }
    }
}
