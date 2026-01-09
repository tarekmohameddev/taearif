<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerReminderRequest;
use App\Http\Requests\Customer\UpdateCustomerReminderRequest;
use Illuminate\Http\Request;
use App\Models\ApiCustomer;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerReminder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class UserApiCustomerReminderController extends Controller
{
    /**
     * Format reminder response with standardized structure.
     *
     * @param UserApiCustomerReminder $reminder
     * @param string $message
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    private function formatReminderResponse(UserApiCustomerReminder $reminder, string $message = 'Success', int $statusCode = 200)
    {
        // Ensure customer relationship is loaded
        if (!$reminder->relationLoaded('customer')) {
            $reminder->load('customer');
        }

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
                'is_default'    => $reminder->isDefault(),
            ]
        ], $statusCode);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        // Validate filter and sort parameters
        $validated = $request->validate([
            'filter_id' => 'nullable|integer',
            'filter_title' => 'nullable|string|max:255',
            'filter_datetime_from' => 'nullable|date',
            'filter_datetime_to' => 'nullable|date|after_or_equal:filter_datetime_from',
            'sort_by' => 'nullable|string|in:id,title,datetime,priority,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ]);

        // Build base query with tenant filtering
        // Show both default reminders (user_id IS NULL) AND user's own reminders
        $query = UserApiCustomerReminder::with('customer')
            ->where(function($q) use ($tenantId) {
                $q->whereNull('user_id')  // Default reminders
                  ->orWhere('user_id', $tenantId);  // User's own reminders
            });

        // Apply filters conditionally (AND logic - multiple filters combine)
        if ($request->filled('filter_id')) {
            $query->where('id', $validated['filter_id']);
        }

        if ($request->filled('filter_title')) {
            $query->where('title', $validated['filter_title']);
        }

        if ($request->filled('filter_datetime_from')) {
            $query->where('datetime', '>=', $validated['filter_datetime_from']);
        }

        if ($request->filled('filter_datetime_to')) {
            $query->where('datetime', '<=', $validated['filter_datetime_to']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'datetime';
        $sortDir = $validated['sort_dir'] ?? 'asc';
        $query->orderBy($sortBy, $sortDir);

        $reminders = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $reminders->map(fn($reminder) => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
                'is_default'    => $reminder->isDefault(),
            ])
        ]);
    }

    /**
     * Get filter options for customer reminders (dropdown data).
     * Returns unique titles and date range for frontend dropdowns.
     * Cached for 1 hour as filter options change infrequently.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function filterOptions(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        // Cache for 1 hour (filter options change infrequently)
        $cacheKey = "customer_reminders_filter_options_{$tenantId}";

        $filterOptions = Cache::remember($cacheKey, 3600, function () use ($tenantId) {
            // Base query for tenant filtering (same logic as index method)
            $baseQuery = UserApiCustomerReminder::where(function($q) use ($tenantId) {
                $q->whereNull('user_id')  // Default reminders
                  ->orWhere('user_id', $tenantId);  // User's own reminders
            });

            // Get unique titles (limit to prevent huge dropdowns)
            $titles = $baseQuery->clone()
                ->whereNotNull('title')
                ->distinct()
                ->orderBy('title')
                ->limit(100) // Prevent huge dropdowns
                ->pluck('title')
                ->values()
                ->toArray();

            // Get date range (min and max datetime)
            $dateRange = $baseQuery->clone()
                ->selectRaw('MIN(datetime) as min_date, MAX(datetime) as max_date')
                ->first();

            return [
                'titles' => $titles,
                'date_range' => [
                    'min' => $dateRange->min_date ?? null,
                    'max' => $dateRange->max_date ?? null,
                ],
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $filterOptions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreCustomerReminderRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerReminderRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized user.'], 401);
        }

        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        $validated = $request->validated();

        // If reminder_id is provided, clone from existing reminder
        if (isset($validated['reminder_id'])) {
            $sourceReminder = UserApiCustomerReminder::where(function($query) use ($tenantId) {
                $query->whereNull('user_id')  // Can clone from default reminders
                      ->orWhere('user_id', $tenantId);  // Or user's own reminders
            })->findOrFail($validated['reminder_id']);

            // Copy title and priority from source reminder
            $validated['title'] = $sourceReminder->title;
            if (!isset($validated['priority']) && $sourceReminder->priority) {
                $validated['priority'] = $sourceReminder->priority;
            }
            
            // Remove reminder_id from validated data (not a database field)
            unset($validated['reminder_id']);
        }

        // If customer_id is provided, validate it belongs to user's tenant
        if (isset($validated['customer_id']) && $validated['customer_id']) {
            $customer = ApiCustomer::where('id', $validated['customer_id'])
                ->where('user_id', $tenantId)
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found or does not belong to you.'
                ], 404);
            }

            // Check if reminder with same title already exists for this customer
            // Note: Database unique constraint will also prevent duplicates (handles race conditions)
            $existingReminder = UserApiCustomerReminder::where('user_id', $tenantId)
                ->where('customer_id', $validated['customer_id'])
                ->where('title', $validated['title'])
                ->first();

            if ($existingReminder) {
                // Update existing reminder instead of creating duplicate
                return DB::transaction(function () use ($existingReminder, $validated) {
                    $existingReminder->update([
                        'datetime' => $validated['datetime'],
                        'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $existingReminder->priority,
                    ]);

                    return $this->formatReminderResponse($existingReminder, 'Reminder updated successfully', 200);
                });
            }
        }

        // Always set user_id to tenantOwnerId (users cannot create default reminders)
        $validated['user_id'] = $tenantId;

        try {
            $reminder = DB::transaction(function () use ($validated) {
                return UserApiCustomerReminder::create($validated);
            });
        } catch (QueryException $e) {
            // Handle unique constraint violation (race condition)
            if ($e->getCode() == 23000 || str_contains($e->getMessage(), 'reminders_user_customer_title_unique')) {
                // If this is for a customer reminder, try to update instead
                if (isset($validated['customer_id']) && $validated['customer_id']) {
                    $existingReminder = UserApiCustomerReminder::where('user_id', $validated['user_id'])
                        ->where('customer_id', $validated['customer_id'])
                        ->where('title', $validated['title'])
                        ->first();

                    if ($existingReminder) {
                        return DB::transaction(function () use ($existingReminder, $validated) {
                            $existingReminder->update([
                                'datetime' => $validated['datetime'],
                                'priority' => array_key_exists('priority', $validated) ? $validated['priority'] : $existingReminder->priority,
                            ]);

                            return $this->formatReminderResponse($existingReminder, 'Reminder updated successfully', 200);
                        });
                    }
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'A reminder with this title already exists for this customer.'
                ], 422);
            }

            // Re-throw if it's not a unique constraint violation
            throw $e;
        }

        return $this->formatReminderResponse($reminder, 'Reminder created successfully', 201);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        // Allow viewing default reminders OR user's own reminders
        $reminder = UserApiCustomerReminder::with('customer')
            ->where(function($query) use ($tenantId) {
                $query->whereNull('user_id')  // Default reminders
                      ->orWhere('user_id', $tenantId);  // User's own reminders
            })
            ->findOrFail($id);

        return $this->formatReminderResponse($reminder, 'Success');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateCustomerReminderRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerReminderRequest $request, $id)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        // Only allow updating user's own reminders (not default reminders)
        $reminder = UserApiCustomerReminder::where('user_id', $tenantId)
            ->findOrFail($id);

        // Prevent editing default reminders
        if ($reminder->isDefault()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Default reminders cannot be edited.'
            ], 403);
        }

        $validated = $request->validated();

        // If customer_id is being updated, validate it belongs to the user
        if (isset($validated['customer_id']) && $validated['customer_id']) {
            $customer = ApiCustomer::where('id', $validated['customer_id'])
                ->where('user_id', $tenantId)
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Customer not found or does not belong to you.'
                ], 404);
            }
        }

        $reminder->update($validated);

        return $this->formatReminderResponse($reminder, 'Reminder updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        // Validate tenant ID
        if ($tenantId <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid tenant. You must be associated with a tenant.'
            ], 403);
        }

        // Only allow deleting user's own reminders (not default reminders)
        $reminder = UserApiCustomerReminder::where('user_id', $tenantId)
            ->findOrFail($id);

        // Prevent deleting default reminders
        if ($reminder->isDefault()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Default reminders cannot be deleted.'
            ], 403);
        }

        $reminder->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder deleted successfully'
        ]);
    }

}
