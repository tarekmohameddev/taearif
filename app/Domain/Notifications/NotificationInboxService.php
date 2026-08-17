<?php

namespace App\Domain\Notifications;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class NotificationInboxService
{
    /**
     * @return array{id:int, recipients:Collection<int,int>, created:bool}|null
     */
    public function persist(array $notification, string $permission): ?array
    {
        $existingId = $notification['dedupe_key']
            ? DB::table('app_notifications')->where('dedupe_key', $notification['dedupe_key'])->value('id')
            : null;
        if ($existingId !== null) {
            return [
                'id' => (int) $existingId,
                'recipients' => DB::table('app_notification_recipients')
                    ->where('notification_id', $existingId)->pluck('recipient_user_id'),
                'created' => false,
            ];
        }

        $recipients = $this->resolveRecipients((int) $notification['tenant_user_id'], $permission);
        if ($recipients->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($notification, $recipients) {
            $now = Carbon::now();
            $existingId = $notification['dedupe_key']
                ? DB::table('app_notifications')->where('dedupe_key', $notification['dedupe_key'])
                    ->lockForUpdate()->value('id')
                : null;
            if ($existingId !== null) {
                return ['id' => (int) $existingId, 'recipients' => $recipients, 'created' => false];
            }

            $id = DB::table('app_notifications')->insertGetId([
                'tenant_user_id' => $notification['tenant_user_id'],
                'type' => $notification['type'],
                'title' => $notification['title'],
                'body' => $notification['body'],
                'source_type' => $notification['source_type'],
                'source_id' => $notification['source_id'],
                'request_id' => $notification['request_id'],
                'customer_id' => $notification['customer_id'] ?? null,
                'actor_user_id' => $notification['actor_user_id'] ?? null,
                'payload' => empty($notification['payload']) ? null : json_encode($notification['payload']),
                'dedupe_key' => $notification['dedupe_key'] ?? null,
                'occurred_at' => $notification['occurred_at'] ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('app_notification_recipients')->insert($recipients->map(fn (int $userId) => [
                'notification_id' => $id,
                'recipient_user_id' => $userId,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return ['id' => (int) $id, 'recipients' => $recipients, 'created' => true];
        });
    }

    public function resolveRecipients(int $tenantUserId, string $permission): Collection
    {
        $candidateIds = collect([$tenantUserId])->merge(
            DB::table('users')->where('tenant_id', $tenantUserId)
                ->where('account_type', 'employee')->where('active', true)->pluck('id')
        )->unique()->values();

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantUserId);
        $registrar->forgetCachedPermissions();

        return $candidateIds->filter(function (int $id) use ($permission) {
            $user = User::find($id);
            return $user !== null && $user->can($permission);
        })->values();
    }

    public function listForViewer(int $viewerId, array $filters): array
    {
        $limit = min(max((int) ($filters['limit'] ?? 25), 1), 100);
        $offset = max((int) ($filters['offset'] ?? 0), 0);
        $query = DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->when(! empty($filters['unreadOnly']), fn ($q) => $q->whereNull('r.read_at'))
            ->orderByDesc('n.occurred_at');
        if (! empty($filters['category'])) {
            if ($filters['category'] === 'PROPERTY_REQUEST') {
                $query->where('n.source_type', 'property_request');
            } elseif ($filters['category'] === 'CONTACT_MESSAGE') {
                $query->where('n.source_type', 'contact_message');
            } else {
                $query->where('n.type', 'like', $filters['category'].'%');
            }
        }

        $total = (clone $query)->count();
        $items = $query->offset($offset)->limit($limit)->get([
            'n.id', 'n.type', 'n.title', 'n.body', 'n.source_type', 'n.source_id',
            'n.request_id', 'n.customer_id', 'n.payload', 'n.occurred_at', 'r.read_at',
        ])->map(fn ($row) => $this->toMobileDto($row));

        return compact('items', 'total', 'limit', 'offset') + ['hasMore' => $offset + $limit < $total];
    }

    public function unreadCount(int $viewerId): int
    {
        return DB::table('app_notification_recipients')
            ->where('recipient_user_id', $viewerId)->whereNull('read_at')->count();
    }

    public function markRead(int $viewerId, int $notificationId): bool
    {
        $query = DB::table('app_notification_recipients')->where('recipient_user_id', $viewerId)
            ->where('notification_id', $notificationId);
        if (! (clone $query)->exists()) {
            return false;
        }
        $query->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
        return true;
    }

    public function markAllRead(int $viewerId): int
    {
        return DB::table('app_notification_recipients')->where('recipient_user_id', $viewerId)
            ->whereNull('read_at')->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function notificationForPush(int $notificationId): ?array
    {
        $row = DB::table('app_notifications')->where('id', $notificationId)->first();
        return $row ? $this->toMobileDto($row) : null;
    }

    private function toMobileDto(object $row): array
    {
        $payload = is_string($row->payload ?? null) ? json_decode($row->payload, true) : ($row->payload ?? []);
        $sourceType = (string) $row->source_type;

        return [
            'id' => (int) $row->id,
            'type' => (string) $row->type,
            'category' => $this->categoryForType((string) $row->type, $sourceType),
            'title' => (string) $row->title,
            'body' => (string) $row->body,
            'isRead' => isset($row->read_at) && $row->read_at !== null,
            'occurredAt' => Carbon::parse($row->occurred_at)->toIso8601String(),
            'deepLink' => match ($sourceType) {
                'property_request' => 'taearif://customers-hub/requests/property_request_'.$row->source_id,
                'contact_message' => 'taearif://contact-messages/'.$row->source_id,
                default => 'taearif://notifications/'.$row->id,
            },
            'entityType' => $sourceType,
            'entityId' => (int) $row->source_id,
            'requestId' => (string) $row->request_id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
            'payload' => is_array($payload) ? $payload : [],
        ];
    }

    private function categoryForType(string $type, string $sourceType): string
    {
        return match (true) {
            $sourceType === 'property_request', str_starts_with($type, 'PROPERTY_REQUEST') => 'PROPERTY_REQUEST',
            $sourceType === 'contact_message', str_starts_with($type, 'CONTACT_MESSAGE') => 'CONTACT_MESSAGE',
            str_contains($type, 'REMINDER') => 'REMINDER',
            str_contains($type, 'RENTAL') => 'RENTAL',
            default => 'SYSTEM',
        };
    }
}
