<?php

namespace App\Support;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;

class PropertyRequestFilterOptionsCache
{
    /**
     * Forget all property_request_filter_options_v2 filterData keys for an owner.
     * This includes keys like property_request_filter_options_v2_{id}_1_all,
     * _v2_{id}_0_all, and city-scoped _v2_{id}_1_5, _v2_{id}_0_5, etc.
     *
     * - When the cache store is Redis: uses SCAN to find keys matching
     *   property_request_filter_options_v2_{ownerId}_* and deletes them.
     * - When not Redis: forgets property_request_filter_options_v2_{ownerId}_1_all
     *   and _v2_{ownerId}_0_all; city-scoped keys will expire by TTL.
     */
    public static function forgetFilterDataForOwner(int $ownerId): void
    {
        $store = Cache::getStore();

        if ($store instanceof RedisStore) {
            $conn = $store->connection();
            $prefix = $store->getPrefix();
            $pattern = $prefix . 'property_request_filter_options_v2_' . $ownerId . '_*';

            $cursor = 0;
            $allKeys = [];
            do {
                $result = $conn->command('scan', [$cursor, 'MATCH', $pattern, 'COUNT', 100]);
                if (! is_array($result) || ! array_key_exists(0, $result)) {
                    break;
                }
                $cursor = (int) $result[0];
                if (! empty($result[1])) {
                    $allKeys = array_merge($allKeys, $result[1]);
                }
            } while ($cursor !== 0);

            if (! empty($allKeys)) {
                $conn->del($allKeys);
            }

            return;
        }

        Cache::forget("property_request_filter_options_v2_{$ownerId}_1_all");
        Cache::forget("property_request_filter_options_v2_{$ownerId}_0_all");
    }
}
