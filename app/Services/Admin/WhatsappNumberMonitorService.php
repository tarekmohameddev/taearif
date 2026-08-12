<?php

namespace App\Services\Admin;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Read-only reporting for the admin WhatsApp numbers monitor.
 *
 * Two things this service deliberately does differently from the naive join:
 *
 * 1. Messages are persisted against the *tenant owner* id, not against
 *    whatsapp_users.user_id — see ChatController::handleWhatsappWebhook(), which passes
 *    $ownerUser->tenantOwnerId(). For an employee-owned number those two ids differ, so
 *    joining on whatsapp_users.user_id would report a healthy number as "never received".
 *
 * 2. wa_numbers is matched on (provider, phone_number_id) only, mirroring
 *    WhatsAppWebhookService::resolveTenantFromPayload(). Scoping the match by user_id as well
 *    would hide the owner_mismatch case, where the row exists but points at another tenant and
 *    inbound is being routed to the wrong account.
 */
class WhatsappNumberMonitorService
{
    public const HEALTH_WORKING = 'working';
    public const HEALTH_NO_RECENT_INBOUND = 'no_recent_inbound';
    public const HEALTH_NO_INBOUND_EVER = 'no_inbound_ever';
    public const HEALTH_NOT_LINKED = 'not_linked';

    public const SYNC_SYNCED = 'synced';
    public const SYNC_MISSING = 'missing';
    public const SYNC_OWNER_MISMATCH = 'owner_mismatch';
    public const SYNC_NA = 'n/a';

    /** Statuses that mean the number is not usable, regardless of message history. */
    private const UNLINKED_STATUSES = ['not_linked', 'inactive', 'blocked'];

    public const PER_PAGE = 25;

    public static function healthOptions(): array
    {
        return [
            self::HEALTH_WORKING,
            self::HEALTH_NO_RECENT_INBOUND,
            self::HEALTH_NO_INBOUND_EVER,
            self::HEALTH_NOT_LINKED,
        ];
    }

    public static function syncOptions(): array
    {
        return [
            self::SYNC_SYNCED,
            self::SYNC_MISSING,
            self::SYNC_OWNER_MISMATCH,
            self::SYNC_NA,
        ];
    }

    public function staleHours(): int
    {
        return max(1, (int) config('communication.whatsapp.monitor.inbound_stale_hours', 24));
    }

    public function staleCutoff(): Carbon
    {
        return now()->subHours($this->staleHours());
    }

    /**
     * Paginated number list with health and sync resolved per row.
     *
     * @param  array{status?:string|null,health?:string|null,sync?:string|null,q?:string|null,sort?:string|null,order?:string|null}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $this->applyStatusFilter($query, $filters['status'] ?? null);
        $this->applySearchFilter($query, $filters['q'] ?? null);
        $this->applySyncFilter($query, $filters['sync'] ?? null);
        $this->applyHealthFilter($query, $filters['health'] ?? null);

        $sortByMessageActivity = $this->applySort(
            $query,
            $filters['sort'] ?? null,
            $filters['order'] ?? null
        );

        $paginator = $query
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        if ($sortByMessageActivity) {
            $paginator->getCollection()->transform(function ($row) {
                $row->health = $this->resolveHealth($row, $row->last_inbound_at);
                $row->sync = $this->resolveSync($row);

                return $row;
            });
        } else {
            $activity = $this->messageActivityFor(
                collect($paginator->items())->pluck('tenant_owner_id')->all()
            );

            $paginator->getCollection()->transform(function ($row) use ($activity) {
                $stats = $activity[(int) $row->tenant_owner_id] ?? null;

                $row->last_inbound_at = $stats->last_inbound_at ?? null;
                $row->last_outbound_at = $stats->last_outbound_at ?? null;
                $row->health = $this->resolveHealth($row, $row->last_inbound_at);
                $row->sync = $this->resolveSync($row);

                return $row;
            });
        }

        return $paginator;
    }

    /**
     * Counts per health value plus the sync states worth alarming on.
     *
     * Evaluated across every row, so it is cached — the view labels the age.
     *
     * @return array{counts:array<string,int>,generated_at:\Illuminate\Support\Carbon}
     */
    public function summary(): array
    {
        $ttl = max(0, (int) config('communication.whatsapp.monitor.summary_cache_seconds', 300));

        $build = fn () => [
            'counts' => $this->buildSummaryCounts(),
            'generated_at' => now(),
        ];

        if ($ttl === 0) {
            return $build();
        }

        return Cache::remember('admin.whatsapp_monitor.summary.v1', $ttl, $build);
    }

    /**
     * Last messages for the tenant that owns a number, newest first.
     *
     * Content is tenant customer data: only a truncated preview is selected, never the full body.
     */
    public function recentMessages(int $tenantOwnerId, int $limit = 20)
    {
        return DB::table('messages')
            ->select('id', 'direction', 'status', 'content', 'created_at')
            ->where('user_id', $tenantOwnerId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function ($message) {
                $message->preview = Str::limit((string) $message->content, 120);
                unset($message->content);

                return $message;
            });
    }

    /**
     * A single number with its tenant, wa_numbers match, and resolved health/sync.
     */
    public function find(int $whatsappUserId): ?object
    {
        $row = $this->baseQuery()->where('wu.id', $whatsappUserId)->first();

        if ($row === null) {
            return null;
        }

        $stats = $this->messageActivityFor([(int) $row->tenant_owner_id])[(int) $row->tenant_owner_id] ?? null;

        $row->last_inbound_at = $stats->last_inbound_at ?? null;
        $row->last_outbound_at = $stats->last_outbound_at ?? null;
        $row->health = $this->resolveHealth($row, $row->last_inbound_at);
        $row->sync = $this->resolveSync($row);

        return $row;
    }

    /**
     * whatsapp_users joined to its tenant and its wa_numbers counterpart. Never touches messages.
     */
    private function baseQuery(): Builder
    {
        return DB::table('whatsapp_users as wu')
            ->join('users as u', 'u.id', '=', 'wu.user_id')
            ->leftJoin('wa_numbers as wn', function ($join) {
                $join->on('wn.phone_number_id', '=', 'wu.phone_id')
                    ->where('wn.provider', '=', 'meta');
            })
            ->whereNotNull('wu.id')
            ->select([
                'wu.id',
                'wu.number',
                'wu.name',
                'wu.status',
                'wu.request_status',
                'wu.phone_id',
                'wu.user_id',
                'wu.created_at',
                'u.username',
                'u.email',
                DB::raw($this->tenantOwnerExpression() . ' as tenant_owner_id'),
                'wn.id as wa_number_id',
                'wn.user_id as wa_number_user_id',
                'wn.phone_number as wa_number_phone',
                'wn.status as wa_number_status',
            ]);
    }

    /**
     * Mirrors User::tenantOwnerId(): employees resolve to their tenant, everyone else to self.
     */
    private function tenantOwnerExpression(): string
    {
        return "(CASE WHEN u.account_type = 'employee' THEN u.tenant_id ELSE u.id END)";
    }

    /**
     * @return bool  True when message activity was joined for sorting (skip post-pagination fetch).
     */
    private function applySort(Builder $query, ?string $sort, ?string $order): bool
    {
        $sort = $sort !== null && $sort !== '' ? $sort : 'id';
        $order = strtolower((string) ($order ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, ['last_inbound_at', 'last_outbound_at'], true)) {
            $this->joinMessageActivity($query);
            $column = 'msg_activity.' . $sort;
            $query->orderByRaw("({$column} IS NULL) ASC")
                ->orderBy($column, $order);

            return true;
        }

        if ($sort === 'created_at') {
            $query->orderByRaw('(wu.created_at IS NULL) ASC')
                ->orderBy('wu.created_at', $order);

            return false;
        }

        if ($order === 'asc') {
            $query->orderBy('wu.id');
        } else {
            $query->orderByDesc('wu.id');
        }

        return false;
    }

    private function joinMessageActivity(Builder $query): void
    {
        $owner = $this->tenantOwnerExpression();

        $sub = DB::table('messages')
            ->groupBy('user_id')
            ->select([
                'user_id',
                DB::raw("MAX(CASE WHEN direction = 'inbound' THEN created_at END) as last_inbound_at"),
                DB::raw("MAX(CASE WHEN direction = 'outbound' THEN created_at END) as last_outbound_at"),
            ]);

        $query->leftJoinSub($sub, 'msg_activity', function ($join) use ($owner) {
            $join->whereRaw("msg_activity.user_id = {$owner}");
        });

        $query->addSelect([
            'msg_activity.last_inbound_at',
            'msg_activity.last_outbound_at',
        ]);
    }

    /**
     * Last inbound/outbound per tenant, restricted to the ids on the current page so the
     * existing messages.user_id index is used instead of scanning the table.
     *
     * @param  array<int|string|null>  $tenantOwnerIds
     * @return array<int,object>
     */
    private function messageActivityFor(array $tenantOwnerIds): array
    {
        $ids = collect($tenantOwnerIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return [];
        }

        return DB::table('messages')
            ->whereIn('user_id', $ids)
            ->groupBy('user_id')
            ->select([
                'user_id',
                DB::raw("MAX(CASE WHEN direction = 'inbound' THEN created_at END) as last_inbound_at"),
                DB::raw("MAX(CASE WHEN direction = 'outbound' THEN created_at END) as last_outbound_at"),
            ])
            ->get()
            ->keyBy(fn ($row) => (int) $row->user_id)
            ->all();
    }

    private function applyStatusFilter(Builder $query, ?string $status): void
    {
        if ($status !== null && $status !== '') {
            $query->where('wu.status', $status);
        }
    }

    private function applySearchFilter(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $like = '%' . $term . '%';

        $query->where(function ($inner) use ($like, $term) {
            $inner->where('wu.number', 'like', $like)
                ->orWhere('wu.phone_id', 'like', $like)
                ->orWhere('u.username', 'like', $like)
                ->orWhere('u.email', 'like', $like);

            if (ctype_digit($term)) {
                $id = (int) $term;
                $owner = $this->tenantOwnerExpression();

                $inner->orWhere('wu.id', $id)
                    ->orWhere('u.id', $id)
                    ->orWhereRaw("{$owner} = ?", [$id]);
            }
        });
    }

    /**
     * Pure join predicate — no messages access.
     */
    private function applySyncFilter(Builder $query, ?string $sync): void
    {
        $owner = $this->tenantOwnerExpression();

        switch ($sync) {
            case self::SYNC_SYNCED:
                $query->whereNotNull('wn.id')->whereRaw("wn.user_id = {$owner}");
                break;
            case self::SYNC_OWNER_MISMATCH:
                $query->whereNotNull('wn.id')->whereRaw("wn.user_id <> {$owner}");
                break;
            case self::SYNC_MISSING:
                $query->whereNull('wn.id')->where($this->hasPhoneIdPredicate());
                break;
            case self::SYNC_NA:
                $query->whereNot($this->hasPhoneIdPredicate());
                break;
        }
    }

    /**
     * Health cannot be resolved after pagination without breaking the page counts, so when a
     * health filter is present it is pushed into the query as a correlated EXISTS. That is
     * bounded by the whatsapp_users row count and uses the messages.user_id index per row.
     */
    private function applyHealthFilter(Builder $query, ?string $health): void
    {
        if ($health === null || $health === '') {
            return;
        }

        $cutoff = $this->staleCutoff();

        switch ($health) {
            case self::HEALTH_NOT_LINKED:
                $query->whereNot($this->linkedPredicate());
                break;

            case self::HEALTH_WORKING:
                $query->where($this->linkedPredicate())
                    ->whereExists($this->inboundExists($cutoff));
                break;

            case self::HEALTH_NO_RECENT_INBOUND:
                $query->where($this->linkedPredicate())
                    ->whereNotExists($this->inboundExists($cutoff))
                    ->whereExists($this->inboundExists(null));
                break;

            case self::HEALTH_NO_INBOUND_EVER:
                $query->where($this->linkedPredicate())
                    ->whereNotExists($this->inboundExists(null));
                break;
        }
    }

    /**
     * phone_id present (non-null and non-empty). Used by sync filters to mirror resolveSync().
     */
    private function hasPhoneIdPredicate(): \Closure
    {
        return function ($inner) {
            $inner->whereNotNull('wu.phone_id')
                ->where('wu.phone_id', '<>', '');
        };
    }

    /**
     * "Linked" = usable status and a phone_id set. Returned as a closure so it can be negated.
     */
    private function linkedPredicate(): \Closure
    {
        return function ($inner) {
            $inner->whereNotIn('wu.status', self::UNLINKED_STATUSES)
                ->whereNotNull('wu.phone_id')
                ->where('wu.phone_id', '<>', '');
        };
    }

    private function inboundExists(?Carbon $since): \Closure
    {
        $owner = $this->tenantOwnerExpression();

        return function ($sub) use ($since, $owner) {
            $sub->from('messages as m')
                ->selectRaw('1')
                ->whereRaw("m.user_id = {$owner}")
                ->where('m.direction', 'inbound');

            if ($since !== null) {
                $sub->where('m.created_at', '>=', $since);
            }
        };
    }

    /**
     * @return array<string,int>
     */
    private function buildSummaryCounts(): array
    {
        $counts = [];

        foreach (self::healthOptions() as $health) {
            $query = $this->baseQuery();
            $this->applyHealthFilter($query, $health);
            $counts[$health] = $query->count();
        }

        foreach ([self::SYNC_MISSING, self::SYNC_OWNER_MISMATCH] as $sync) {
            $query = $this->baseQuery();
            $this->applySyncFilter($query, $sync);
            $counts[$sync] = $query->count();
        }

        $counts['total'] = $this->baseQuery()->count();

        return $counts;
    }

    private function isLinked(object $row): bool
    {
        return ! in_array((string) $row->status, self::UNLINKED_STATUSES, true)
            && trim((string) $row->phone_id) !== '';
    }

    public function resolveHealth(object $row, $lastInboundAt): string
    {
        if (! $this->isLinked($row)) {
            return self::HEALTH_NOT_LINKED;
        }

        if ($lastInboundAt === null) {
            return self::HEALTH_NO_INBOUND_EVER;
        }

        return Carbon::parse($lastInboundAt)->greaterThanOrEqualTo($this->staleCutoff())
            ? self::HEALTH_WORKING
            : self::HEALTH_NO_RECENT_INBOUND;
    }

    public function resolveSync(object $row): string
    {
        if (trim((string) $row->phone_id) === '') {
            return self::SYNC_NA;
        }

        if ($row->wa_number_id === null) {
            return self::SYNC_MISSING;
        }

        return (int) $row->wa_number_user_id === (int) $row->tenant_owner_id
            ? self::SYNC_SYNCED
            : self::SYNC_OWNER_MISMATCH;
    }
}
