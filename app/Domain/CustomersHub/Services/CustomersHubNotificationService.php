<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class CustomersHubNotificationService
{
    public const PERMISSION_VIEW = 'customers_hub_requests.view';

    public const SOURCE_PROPERTY_REQUEST = 'property_request';

    public const TYPE_UPDATED = 'property_request_updated';
    public const TYPE_STAGE_CHANGED = 'property_request_stage_changed';
    public const TYPE_PRIORITY_CHANGED = 'property_request_priority_changed';
    public const TYPE_STATUS_CHANGED = 'property_request_status_changed';
    public const TYPE_ASSIGNED = 'property_request_assigned';
    public const TYPE_COMPLETED = 'property_request_completed';
    public const TYPE_DISMISSED = 'property_request_dismissed';
    public const TYPE_SNOOZED = 'property_request_snoozed';
    public const TYPE_APPOINTMENT_CREATED = 'property_request_appointment_created';
    public const TYPE_REMINDER_CREATED = 'property_request_reminder_created';
    public const TYPE_REMINDER_DUE = 'property_request_reminder_due';
    public const TYPE_REMINDER_OVERDUE = 'property_request_reminder_overdue';

    /**
     * Create a property request notification with per-recipient unread rows.
     */
    public function notifyPropertyRequestEvent(
        int $tenantUserId,
        int $propertyRequestId,
        string $type,
        string $title,
        string $body,
        array $payload = [],
        ?int $actorUserId = null,
        ?int $customerId = null,
        ?string $dedupeKey = null
    ): ?int {
        if ($dedupeKey !== null) {
            $existingId = DB::table('app_notifications')
                ->where('dedupe_key', $dedupeKey)
                ->value('id');
            if ($existingId !== null) {
                return (int) $existingId;
            }
        }

        $recipients = $this->resolveRecipients($tenantUserId);
        if ($recipients->isEmpty()) {
            return null;
        }

        $now = Carbon::now();
        $requestId = 'property_request_' . $propertyRequestId;

        return DB::transaction(function () use (
            $tenantUserId,
            $propertyRequestId,
            $type,
            $title,
            $body,
            $payload,
            $actorUserId,
            $customerId,
            $dedupeKey,
            $recipients,
            $now,
            $requestId
        ) {
            if ($dedupeKey !== null) {
                $existingId = DB::table('app_notifications')
                    ->where('dedupe_key', $dedupeKey)
                    ->lockForUpdate()
                    ->value('id');
                if ($existingId !== null) {
                    return (int) $existingId;
                }
            }

            $notificationId = DB::table('app_notifications')->insertGetId([
                'tenant_user_id' => $tenantUserId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'source_type' => self::SOURCE_PROPERTY_REQUEST,
                'source_id' => $propertyRequestId,
                'request_id' => $requestId,
                'customer_id' => $customerId,
                'actor_user_id' => $actorUserId,
                'payload' => $payload !== [] ? json_encode($payload) : null,
                'dedupe_key' => $dedupeKey,
                'occurred_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $recipientRows = $recipients->map(fn (int $recipientId) => [
                'notification_id' => $notificationId,
                'recipient_user_id' => $recipientId,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('app_notification_recipients')->insert($recipientRows);

            return (int) $notificationId;
        });
    }

    /**
     * Tenant owner plus active employees with customers_hub_requests.view.
     * @return Collection<int, int>
     */
    public function resolveRecipients(int $tenantUserId): Collection
    {
        $cacheKey = 'ch:notif:recipients:' . $tenantUserId;

        /** @var Collection<int, int> $candidateIds */
        $candidateIds = Cache::remember($cacheKey, 300, function () use ($tenantUserId) {
            $ids = collect([$tenantUserId]);

            $employeeIds = DB::table('users')
                ->where('tenant_id', $tenantUserId)
                ->where('account_type', 'employee')
                ->where('active', true)
                ->pluck('id');

            return $ids->merge($employeeIds)->unique()->values();
        });

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId($tenantUserId);
        $registrar->forgetCachedPermissions();

        return $candidateIds
            ->filter(function (int $userId) {
                /** @var User|null $user */
                $user = User::find($userId);

                return $user !== null && $user->can(self::PERMISSION_VIEW);
            })
            ->values();
    }

    /**
     * @return array{items: Collection, total: int}
     */
    public function listForViewer(int $viewerId, array $filters = [], bool $unreadOnly = false): array
    {
        $limit = min(max((int) ($filters['limit'] ?? 25), 1), 100);
        $offset = max((int) ($filters['offset'] ?? 0), 0);
        $sourceType = $filters['sourceType'] ?? $filters['source_type'] ?? null;

        $query = DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->when($unreadOnly, fn ($q) => $q->whereNull('r.read_at'))
            ->when($sourceType, fn ($q) => $q->where('n.source_type', $sourceType))
            ->orderByDesc('n.occurred_at');

        $total = (clone $query)->count();

        $rows = $query
            ->offset($offset)
            ->limit($limit)
            ->get([
                'n.id',
                'n.type',
                'n.title',
                'n.body',
                'n.source_type',
                'n.source_id',
                'n.request_id',
                'n.customer_id',
                'n.actor_user_id',
                'n.payload',
                'n.occurred_at',
                'r.read_at',
            ]);

        $items = $rows->map(fn ($row) => $this->formatNotificationRow($row));

        return [
            'items' => $items,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total,
        ];
    }

    public function unreadCountForViewer(int $viewerId, ?string $sourceType = null): int
    {
        return (int) DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at')
            ->when($sourceType, fn ($q) => $q->where('n.source_type', $sourceType))
            ->count();
    }

    public function markRead(int $viewerId, int $notificationId): bool
    {
        return DB::table('app_notification_recipients')
            ->where('notification_id', $notificationId)
            ->where('recipient_user_id', $viewerId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now(), 'updated_at' => Carbon::now()]) > 0;
    }

    public function markAllRead(int $viewerId, ?string $sourceType = null): int
    {
        $now = Carbon::now();

        $query = DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at');

        if ($sourceType !== null) {
            $query->where('n.source_type', $sourceType);
        }

        $ids = $query->pluck('r.id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return DB::table('app_notification_recipients')
            ->whereIn('id', $ids)
            ->update(['read_at' => $now, 'updated_at' => $now]);
    }

    public function viewerCanAccessNotification(int $viewerId, int $notificationId): bool
    {
        return DB::table('app_notification_recipients')
            ->where('notification_id', $notificationId)
            ->where('recipient_user_id', $viewerId)
            ->exists();
    }

    /**
     * Property request source IDs with at least one unread notification for the viewer.
     *
     * @return list<int>
     */
    public function getUnreadPropertyRequestSourceIds(int $viewerId): array
    {
        if (! $this->notificationsTablesExist()) {
            return [];
        }

        return DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at')
            ->where('n.source_type', self::SOURCE_PROPERTY_REQUEST)
            ->distinct()
            ->pluck('n.source_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Default unread category breakdown (all false).
     *
     * @return array<string, bool>
     */
    public function emptyUnreadCategoriesBreakdown(): array
    {
        return [
            'appointment' => false,
            'stageChange' => false,
            'priorityChange' => false,
            'statusChange' => false,
            'assigned' => false,
            'completed' => false,
            'dismissed' => false,
            'snoozed' => false,
            'reminder' => false,
            'generalUpdate' => false,
        ];
    }

    /**
     * Unread category breakdown for a property request (snapshot before mark-read).
     *
     * @return array<string, bool>
     */
    public function buildUnreadCategoriesBreakdown(int $viewerId, int $propertyRequestId): array
    {
        $breakdown = $this->emptyUnreadCategoriesBreakdown();

        if (! $this->notificationsTablesExist()) {
            return $breakdown;
        }

        $types = DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at')
            ->where('n.source_type', self::SOURCE_PROPERTY_REQUEST)
            ->where('n.source_id', $propertyRequestId)
            ->pluck('n.type');

        foreach ($types as $type) {
            $category = $this->mapNotificationTypeToCategory((string) $type);
            if ($category !== null) {
                $breakdown[$category] = true;
            }
        }

        return $breakdown;
    }

    public function hasUnreadForPropertyRequest(int $viewerId, int $propertyRequestId): bool
    {
        if (! $this->notificationsTablesExist()) {
            return false;
        }

        return DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at')
            ->where('n.source_type', self::SOURCE_PROPERTY_REQUEST)
            ->where('n.source_id', $propertyRequestId)
            ->exists();
    }

    /**
     * Mark all unread notifications for a property request as read for one viewer.
     */
    public function markPropertyRequestNotificationsRead(int $viewerId, int $propertyRequestId): int
    {
        if (! $this->notificationsTablesExist()) {
            return 0;
        }

        $now = Carbon::now();

        $recipientRowIds = DB::table('app_notification_recipients as r')
            ->join('app_notifications as n', 'n.id', '=', 'r.notification_id')
            ->where('r.recipient_user_id', $viewerId)
            ->whereNull('r.read_at')
            ->where('n.source_type', self::SOURCE_PROPERTY_REQUEST)
            ->where('n.source_id', $propertyRequestId)
            ->pluck('r.id');

        if ($recipientRowIds->isEmpty()) {
            return 0;
        }

        return DB::table('app_notification_recipients')
            ->whereIn('id', $recipientRowIds)
            ->update(['read_at' => $now, 'updated_at' => $now]);
    }

    public function mapNotificationTypeToCategory(string $type): ?string
    {
        return match ($type) {
            self::TYPE_APPOINTMENT_CREATED => 'appointment',
            self::TYPE_STAGE_CHANGED => 'stageChange',
            self::TYPE_PRIORITY_CHANGED => 'priorityChange',
            self::TYPE_STATUS_CHANGED => 'statusChange',
            self::TYPE_ASSIGNED => 'assigned',
            self::TYPE_COMPLETED => 'completed',
            self::TYPE_DISMISSED => 'dismissed',
            self::TYPE_SNOOZED => 'snoozed',
            self::TYPE_REMINDER_CREATED,
            self::TYPE_REMINDER_DUE,
            self::TYPE_REMINDER_OVERDUE => 'reminder',
            self::TYPE_UPDATED => 'generalUpdate',
            default => null,
        };
    }

    private function notificationsTablesExist(): bool
    {
        static $exists = null;
        if ($exists === null) {
            $exists = \Illuminate\Support\Facades\Schema::hasTable('app_notifications')
                && \Illuminate\Support\Facades\Schema::hasTable('app_notification_recipients');
        }

        return $exists;
    }

    /**
     * Load property request context for notification body/titles.
     *
     * @return object{id: int, full_name: ?string, customer_id: ?int}|null
     */
    public function getPropertyRequestContext(int $tenantUserId, int $propertyRequestId): ?object
    {
        return DB::table('users_property_requests')
            ->where('id', $propertyRequestId)
            ->where('user_id', $tenantUserId)
            ->first(['id', 'full_name', 'customer_id']);
    }

    private function formatNotificationRow(object $row): array
    {
        $payload = $row->payload;
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($payload)) {
            $payload = [];
        }

        return [
            'id' => (int) $row->id,
            'type' => $row->type,
            'title' => $row->title,
            'body' => $row->body,
            'sourceType' => $row->source_type,
            'sourceId' => (int) $row->source_id,
            'requestId' => $row->request_id,
            'customerId' => $row->customer_id !== null ? (int) $row->customer_id : null,
            'actorUserId' => $row->actor_user_id !== null ? (int) $row->actor_user_id : null,
            'payload' => $payload,
            'readAt' => $row->read_at ? Carbon::parse($row->read_at)->toIso8601String() : null,
            'isRead' => $row->read_at !== null,
            'occurredAt' => Carbon::parse($row->occurred_at)->toIso8601String(),
            'createdAt' => Carbon::parse($row->occurred_at)->toIso8601String(),
        ];
    }
}
