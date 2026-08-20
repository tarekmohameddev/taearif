<?php

namespace App\Domain\Notifications;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DevicePushTokenService
{
    public function upsert(int $userId, int $tenantId, array $data): array
    {
        $now = now();

        return DB::transaction(function () use ($userId, $tenantId, $data, $now) {
            DB::table('device_push_tokens')
                ->where('user_id', '<>', $userId)
                ->where(function ($query) use ($data) {
                    $query->where('device_id', $data['device_id'])
                        ->orWhere('token', $data['token']);
                })
                ->update(['active' => false, 'updated_at' => $now]);

            DB::table('device_push_tokens')->updateOrInsert(
                ['user_id' => $userId, 'device_id' => $data['device_id']],
                [
                    'tenant_id' => $tenantId,
                    'token' => $data['token'],
                    'provider' => $data['provider'],
                    'platform' => $data['platform'],
                    'app_id' => $data['app_id'] ?? null,
                    'app_version' => $data['app_version'] ?? null,
                    'locale' => $data['locale'] ?? null,
                    'model' => $data['model'] ?? null,
                    'os_version' => $data['os_version'] ?? null,
                    'active' => true,
                    'last_seen_at' => $now,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            return (array) DB::table('device_push_tokens')
                ->where('user_id', $userId)
                ->where('device_id', $data['device_id'])
                ->first();
        });
    }

    public function deactivate(int $userId, ?string $deviceId = null, ?string $token = null): int
    {
        return DB::table('device_push_tokens')
            ->where('user_id', $userId)
            ->when($deviceId, fn ($query) => $query->where('device_id', $deviceId))
            ->when($token, fn ($query) => $query->where('token', $token))
            ->update(['active' => false, 'updated_at' => now()]);
    }

    public function deactivateById(int $id): void
    {
        DB::table('device_push_tokens')->where('id', $id)
            ->update(['active' => false, 'updated_at' => now()]);
    }

    public function activeForUsers(Collection $userIds): Collection
    {
        return DB::table('device_push_tokens')
            ->whereIn('user_id', $userIds)
            ->where('active', true)
            ->get();
    }
}
