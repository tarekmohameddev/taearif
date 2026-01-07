<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApiCustomer;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerReminder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class UserApiCustomerReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized user.'], 401);
        }

        $tenantId = $user->tenantOwnerId();

        // First validate reminder_id if provided (before main validation)
        if ($request->has('reminder_id')) {
            $sourceReminder = UserApiCustomerReminder::where(function($query) use ($tenantId) {
                $query->whereNull('user_id')  // Can clone from default reminders
                      ->orWhere('user_id', $tenantId);  // Or user's own reminders
            })->find($request->reminder_id);

            if (!$sourceReminder) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reminder not found or you do not have access to it.'
                ], 404);
            }
        }

        $validated = $request->validate([
            'customer_id' => 'nullable|integer', // Make nullable for general reminders
            'title'       => 'required_without:reminder_id|string|max:255', // Required if not cloning
            'priority'    => 'nullable|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'datetime'    => 'required|date',
            'reminder_id' => 'nullable|integer|exists:users_api_customers_reminders,id', // For cloning
        ]);

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
        }

        // Always set user_id to tenantOwnerId (users cannot create default reminders)
        $validated['user_id'] = $tenantId;

        $reminder = UserApiCustomerReminder::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder created successfully',
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
                'is_default'    => false,
            ]
        ], 201);
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

        // Allow viewing default reminders OR user's own reminders
        $reminder = UserApiCustomerReminder::with('customer')
            ->where(function($query) use ($tenantId) {
                $query->whereNull('user_id')  // Default reminders
                      ->orWhere('user_id', $tenantId);  // User's own reminders
            })
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
                'is_default'    => $reminder->isDefault(),
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

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

        $validated = $request->validate([
            'title'    => 'sometimes|string|max:255',
            'priority' => 'nullable|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'datetime' => 'sometimes|date',
            'customer_id' => 'nullable|integer',
        ]);

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

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder updated successfully',
            'data' => [
                'id'            => $reminder->id,
                'title'         => $reminder->title,
                'priority'      => $reminder->priority,
                'priority_label'=> $reminder->priority_label,
                'datetime'      => $reminder->datetime,
                'customer'      => $reminder->customer?->only(['id', 'name']),
                'is_default'    => false,
            ]
        ]);
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
