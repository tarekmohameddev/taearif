<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactMessages\BulkActionRequest;
use App\Http\Requests\ContactMessages\IndexRequest;
use App\Http\Requests\ContactMessages\LinkCustomerRequest;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
use App\Models\ApiCustomer;
use App\Models\ContactMessage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ContactMessagesController extends Controller
{
    protected function tenantId(): int
    {
        return (int) auth()->user()->tenantOwnerId();
    }

    public function index(IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->baseQuery($validated);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (ContactMessage $m) => $this->formatListItem($m));

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = ContactMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_read', false)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $tenantId = $this->tenantId();
        $base = ContactMessage::query()->where('tenant_id', $tenantId);

        $total = (clone $base)->whereIn('status', ['active', 'archived'])->count();
        $unread = (clone $base)->where('status', 'active')->where('is_read', false)->count();
        $read = (clone $base)->where('status', 'active')->where('is_read', true)->count();
        $converted = (clone $base)->whereNotNull('customer_id')->count();
        $archived = (clone $base)->where('status', 'archived')->count();

        $todayStart = Carbon::now()->startOfDay();
        $weekStart = Carbon::now()->startOfWeek();

        $today = (clone $base)->where('created_at', '>=', $todayStart)->count();
        $thisWeek = (clone $base)->where('created_at', '>=', $weekStart)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'unread' => $unread,
                'read' => $read,
                'converted_to_customer' => $converted,
                'archived' => $archived,
                'today' => $today,
                'this_week' => $thisWeek,
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $message = $this->findForTenant($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatDetailItem($message),
        ]);
    }

    public function markRead(string $id): JsonResponse
    {
        $message = $this->findForTenant($id);

        if (! $message->is_read) {
            $message->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
            $message->refresh();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'is_read' => $message->is_read,
                'read_at' => $message->read_at?->toISOString(),
            ],
        ]);
    }

    public function markUnread(string $id): JsonResponse
    {
        $message = $this->findForTenant($id);
        $message->update([
            'is_read' => false,
            'read_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'is_read' => false,
                'read_at' => null,
            ],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $query = ContactMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'active')
            ->where('is_read', false);

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        $updated = $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    public function bulk(BulkActionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];
        $tenantId = $this->tenantId();

        if ($action === 'delete' && ! auth()->user()->can('contact_messages.delete')) {
            abort(403, 'You do not have permission to delete contact messages.');
        }

        $messages = ContactMessage::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $failedIds = [];
        $updatedCount = 0;
        $userId = auth()->id();
        $now = now();

        foreach ($ids as $id) {
            $message = $messages->get($id);
            if (! $message) {
                $failedIds[] = $id;
                continue;
            }

            match ($action) {
                'read' => $message->update([
                    'is_read' => true,
                    'read_at' => $now,
                ]),
                'unread' => $message->update([
                    'is_read' => false,
                    'read_at' => null,
                ]),
                'archive' => $message->update([
                    'status' => 'archived',
                    'metadata' => array_merge($message->metadata ?? [], [
                        'archived_at' => $now->toISOString(),
                        'archived_by_user_id' => $userId,
                    ]),
                ]),
                'delete' => $message->delete(),
            };

            $updatedCount++;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'updated_count' => $updatedCount,
                'failed_ids' => $failedIds,
            ],
        ]);
    }

    public function archive(string $id): JsonResponse
    {
        $message = $this->findForTenant($id);
        $message->update([
            'status' => 'archived',
            'metadata' => array_merge($message->metadata ?? [], [
                'archived_at' => now()->toISOString(),
                'archived_by_user_id' => auth()->id(),
            ]),
        ]);
        $message->refresh();

        return response()->json([
            'success' => true,
            'data' => $this->formatDetailItem($message),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $message = $this->findForTenant($id);
        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted',
        ]);
    }

    public function createCustomer(string $id): JsonResponse
    {
        if (! auth()->user()->can('contact_messages.create_customer')
            && ! auth()->user()->can('customers_hub_customers.create')) {
            abort(403);
        }

        $message = $this->findForTenant($id);

        if ($message->customer_id) {
            $existing = ApiCustomer::find($message->customer_id);

            return response()->json([
                'success' => false,
                'message' => 'Message is already linked to a customer.',
                'data' => [
                    'customer' => $existing ? $this->formatCustomer($existing) : null,
                ],
            ], 409);
        }

        if (empty($message->customer_name) && empty($message->customer_phone)) {
            return response()->json([
                'success' => false,
                'message' => 'At least customer_name or customer_phone is required to create a customer.',
                'errors' => [
                    'customer' => ['At least customer_name or customer_phone is required.'],
                ],
            ], 422);
        }

        $tenantId = $this->tenantId();
        $defaults = $this->getDefaultCustomerAttributes($tenantId);
        $stageId = UserApiCustomerStage::where('user_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('order')
            ->value('id');

        $customer = DB::transaction(function () use ($message, $tenantId, $defaults, $stageId) {
            $customer = ApiCustomer::create([
                'user_id' => $tenantId,
                'name' => $message->customer_name,
                'phone_number' => $message->customer_phone,
                'email' => $message->customer_email,
                'stage_id' => $stageId,
                'customers_hub_stage_id' => 'new_lead',
                'note' => $this->buildCustomerNote($message),
                'password' => bcrypt('12345678'),
                'type_id' => $defaults['type_id'],
                'priority_id' => $defaults['priority_id'],
                'procedure_id' => $defaults['procedure_id'],
                'source' => 'website_contact_message',
                'source_id' => $message->id,
            ]);

            $message->update([
                'customer_id' => $customer->id,
                'metadata' => array_merge($message->metadata ?? [], [
                    'converted_at' => now()->toISOString(),
                    'converted_by_user_id' => auth()->id(),
                ]),
            ]);

            return $customer;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $this->formatCustomer($customer),
                'message' => [
                    'id' => $message->id,
                    'customer_id' => $message->customer_id,
                ],
            ],
        ], 201);
    }

    public function linkCustomer(LinkCustomerRequest $request, string $id): JsonResponse
    {
        $message = $this->findForTenant($id);
        $validated = $request->validated();
        $customerId = (int) $validated['customer_id'];
        $force = (bool) ($validated['force'] ?? false);

        $customer = ApiCustomer::query()
            ->where('user_id', $this->tenantId())
            ->findOrFail($customerId);

        if ($message->customer_id && (int) $message->customer_id === $customerId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => [
                        'id' => $message->id,
                        'customer_id' => $message->customer_id,
                    ],
                    'customer' => $this->formatCustomerSummary($customer),
                ],
            ]);
        }

        if ($message->customer_id && ! $force) {
            return response()->json([
                'success' => false,
                'message' => 'Message is already linked to a different customer. Use force=true to overwrite.',
                'data' => [
                    'customer_id' => $message->customer_id,
                ],
            ], 409);
        }

        $message->update([
            'customer_id' => $customerId,
            'metadata' => array_merge($message->metadata ?? [], [
                'linked_at' => now()->toISOString(),
                'linked_by_user_id' => auth()->id(),
            ]),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message' => [
                    'id' => $message->id,
                    'customer_id' => $message->customer_id,
                ],
                'customer' => $this->formatCustomerSummary($customer),
            ],
        ]);
    }

    public function customerMessages(IndexRequest $request, string $customerId): JsonResponse
    {
        $tenantId = $this->tenantId();

        ApiCustomer::query()
            ->where('user_id', $tenantId)
            ->findOrFail($customerId);

        $validated = $request->validated();
        $query = $this->baseQuery($validated)
            ->where('customer_id', $customerId);

        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(fn (ContactMessage $m) => $this->formatListItem($m));

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    protected function baseQuery(array $validated)
    {
        $query = ContactMessage::query()->where('tenant_id', $this->tenantId());

        $status = $validated['status'] ?? 'active';
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (array_key_exists('is_read', $validated)) {
            $query->where('is_read', (bool) $validated['is_read']);
        }

        if (! empty($validated['source'])) {
            $query->where('source', $validated['source']);
        }

        if (! empty($validated['customer_id'])) {
            $query->where('customer_id', $validated['customer_id']);
        }

        if (! empty($validated['search'])) {
            $term = '%' . $validated['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', $term)
                    ->orWhere('customer_email', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term)
                    ->orWhere('message', 'like', $term);
            });
        }

        if (! empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (! empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        return $query;
    }

    protected function findForTenant(string $id): ContactMessage
    {
        return ContactMessage::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail($id);
    }

    protected function formatListItem(ContactMessage $message): array
    {
        $text = $message->message;
        if (mb_strlen($text) > 120) {
            $text = mb_substr($text, 0, 120) . '…';
        }

        return [
            'id' => $message->id,
            'customer_name' => $message->customer_name,
            'customer_email' => $message->customer_email,
            'customer_phone' => $message->customer_phone,
            'message' => $text,
            'source' => $message->source,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toISOString(),
            'status' => $message->status,
            'customer_id' => $message->customer_id,
            'metadata' => $message->metadata ?? [],
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    protected function formatDetailItem(ContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'customer_name' => $message->customer_name,
            'customer_email' => $message->customer_email,
            'customer_phone' => $message->customer_phone,
            'message' => $message->message,
            'source' => $message->source,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toISOString(),
            'status' => $message->status,
            'customer_id' => $message->customer_id,
            'metadata' => $message->metadata ?? [],
            'created_at' => $message->created_at?->toISOString(),
            'updated_at' => $message->updated_at?->toISOString(),
        ];
    }

    protected function formatCustomer(ApiCustomer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone_number' => $customer->phone_number,
            'email' => $customer->email,
            'customers_hub_stage_id' => $customer->customers_hub_stage_id,
            'source' => $customer->source,
            'source_id' => $customer->source_id,
            'note' => $customer->note,
        ];
    }

    protected function formatCustomerSummary(ApiCustomer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone_number' => $customer->phone_number,
            'email' => $customer->email,
        ];
    }

    protected function buildCustomerNote(ContactMessage $message): string
    {
        $parts = [$message->message];
        $meta = $message->metadata ?? [];

        foreach (['city', 'budget', 'unit_type', 'payment_method', 'country', 'rating'] as $key) {
            if (! empty($meta[$key])) {
                $parts[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $meta[$key];
            }
        }

        return implode("\n", $parts);
    }

    protected function getDefaultCustomerAttributes(int $userId): array
    {
        $cacheKey = "customer_defaults:{$userId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
            return [
                'type_id' => UserApiCustomerType::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
                'priority_id' => UserApiCustomerPriority::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
                'procedure_id' => UserApiCustomerProcedure::where('user_id', $userId)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->value('id'),
            ];
        });
    }
}
