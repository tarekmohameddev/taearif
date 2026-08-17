<?php

namespace App\Domain\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationPreferencesService
{
    public const KEYS = [
        'enabled',
        'sound',
        'badge',
        'popup',
        'PROPERTY_REQUEST',
        'CONTACT_MESSAGE',
        'REMINDER',
        'RENTAL',
        'SYSTEM',
    ];

    public function defaults(): array
    {
        return array_fill_keys(self::KEYS, true);
    }

    public function get(int $userId): array
    {
        $row = DB::table('notification_preferences')->where('user_id', $userId)->first();
        if ($row === null) {
            return $this->defaults();
        }

        return collect(self::KEYS)->mapWithKeys(
            fn (string $key) => [$key => (bool) $row->{$key}]
        )->all();
    }

    public function put(int $userId, array $values): array
    {
        $preferences = array_merge($this->get($userId), $values);
        $now = now();

        DB::table('notification_preferences')->updateOrInsert(
            ['user_id' => $userId],
            array_merge($preferences, ['updated_at' => $now, 'created_at' => $now])
        );

        return $preferences;
    }

    public function eligibleUsers(Collection $userIds, string $category): Collection
    {
        if (! in_array($category, self::KEYS, true)) {
            return collect();
        }

        $disabled = DB::table('notification_preferences')
            ->whereIn('user_id', $userIds)
            ->where(function ($query) use ($category) {
                $query->where('enabled', false)->orWhere($category, false);
            })
            ->pluck('user_id');

        return $userIds->diff($disabled)->values();
    }
}
