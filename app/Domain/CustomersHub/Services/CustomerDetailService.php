<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\CustomersHub\CustomersHubStage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CustomerDetailService
 *
 * Handles customer detail operations (info, tasks, properties, preferences).
 */
class CustomerDetailService
{
    public function __construct(
        private ActionsAggregatorService $aggregator,
        private PropertyRequestDetailBuilder $propertyRequestDetailBuilder
    ) {
    }

    /**
     * Get complete customer details.
     */
    public function getCustomerDetails(int $userId, int $customerId): ?array
    {
        $customer = DB::table('api_customers')
            ->where('api_customers.id', $customerId)
            ->where('api_customers.user_id', $userId)
            ->leftJoin('customers_hub_stages as stage', 'api_customers.customers_hub_stage_id', '=', 'stage.stage_id')
            ->leftJoin('users_api_customers_priorities as priority', 'api_customers.priority_id', '=', 'priority.id')
            ->leftJoin('users_api_customers_types as type', 'api_customers.type_id', '=', 'type.id')
            ->leftJoin('user_cities as city', 'api_customers.city_id', '=', 'city.id')
            ->leftJoin('user_districts as district', 'api_customers.district_id', '=', 'district.id')
            ->select([
                'api_customers.*',
                'stage.stage_id as hub_stage_id',
                'stage.stage_name_ar as stage_name',
                'stage.stage_name_en as stage_name_en',
                'stage.color as stage_color',
                'priority.name as priority_name',
                'type.name as type_name',
                'city.name_ar as city_name',
                'district.name_ar as district_name',
            ])
            ->first();

        if (!$customer) {
            return null;
        }

        // Get tasks
        $tasks = $this->getCustomerTasks($userId, $customerId);

        // Get properties
        $properties = $this->getCustomerProperties($userId, $customerId);

        // Get preferences/requests
        $preferences = $this->getCustomerPreferences($userId, $customerId);

        // Get all property requests and inquiries (full payloads)
        $propertyRequests = $this->getCustomerPropertyRequests($userId, $customerId, $customer->phone_number ?? '');

        // Count interactions and appointments
        $totalInteractions = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->count();

        $totalAppointments = DB::table('property_request_appointments')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->count();

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone_number,
                'whatsapp' => $customer->whatsapp_number ?? $customer->phone_number,
                'email' => $customer->email,
                'stage' => [
                    'id' => $customer->hub_stage_id ?? $customer->customers_hub_stage_id ?? null,
                    'name' => $customer->stage_name ?? null,
                    'nameEn' => $customer->stage_name_en ?? null,
                    'color' => $customer->stage_color ?? null,
                ],
                'priority' => [
                    'id' => $customer->priority_id,
                    'name' => $customer->priority_name,
                ],
                'type' => [
                    'id' => $customer->type_id,
                    'name' => $customer->type_name,
                ],
                'city' => $customer->city_name ?? null,
                'district' => $customer->district_name ?? null,
                'occupation' => $customer->occupation ?? null,
                'familySize' => $customer->family_size ?? null,
                'totalInteractions' => $totalInteractions,
                'totalAppointments' => $totalAppointments,
                'tags' => isset($customer->tags) && $customer->tags ? json_decode($customer->tags, true) : [],
                'createdAt' => $customer->created_at ? Carbon::parse($customer->created_at)->toIso8601String() : null,
                'updatedAt' => $customer->updated_at ? Carbon::parse($customer->updated_at)->toIso8601String() : null,
            ],
            'tasks' => $tasks,
            'properties' => $properties,
            'propertyRequests' => $propertyRequests,
            'preferences' => $preferences,
        ];
    }

    /**
     * Get all property requests and inquiries for a customer (full payloads, same shape as pipeline request detail).
     */
    public function getCustomerPropertyRequests(int $userId, int $customerId, string $customerPhone): array
    {
        $inquiryIds = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->pluck('id')
            ->all();

        $propertyRequestIdsFromPivot = DB::table('api_customer_property_request')
            ->where('customer_id', $customerId)
            ->pluck('property_request_id')
            ->all();

        $propertyRequestIdsFromPhone = [];
        if ($customerPhone !== '') {
            $propertyRequestIdsFromPhone = DB::table('users_property_requests')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->where('phone', $customerPhone)
                ->pluck('id')
                ->all();
        }

        $propertyRequestIds = array_values(array_unique(array_merge($propertyRequestIdsFromPivot, $propertyRequestIdsFromPhone)));

        $items = [];

        foreach ($inquiryIds as $inquiryId) {
            $action = $this->aggregator->getById($userId, 'inquiry_' . $inquiryId);
            if ($action !== null) {
                $full = $this->propertyRequestDetailBuilder->buildFullInquiryAction($userId, $action);
                if ($full !== null) {
                    $items[] = $full;
                }
            }
        }

        foreach ($propertyRequestIds as $propertyRequestId) {
            $action = $this->aggregator->getById($userId, 'property_request_' . $propertyRequestId);
            if ($action !== null) {
                $full = $this->propertyRequestDetailBuilder->buildFullPropertyRequestAction($userId, $action);
                if ($full !== null) {
                    $items[] = $full;
                }
            }
        }

        usort($items, function (array $a, array $b): int {
            $createdA = $a['createdAt'] ?? '';
            $createdB = $b['createdAt'] ?? '';
            return strcmp($createdB, $createdA);
        });

        return $items;
    }

    /**
     * Update customer.
     */
    public function updateCustomer(int $userId, int $customerId, array $data): bool
    {
        // Only update fields that exist in api_customers table
        $allowedFields = [
            'name', 'phone_number', 'email', 'source', 'note',
            'stage_id', 'customers_hub_stage_id', 'priority_id', 'type_id', 'procedure_id',
            'responsible_employee_id', 'city_id', 'district_id'
        ];

        $updateData = [];

        foreach ($data as $key => $value) {
            // Convert camelCase to snake_case
            $snakeKey = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
            if (in_array($snakeKey, $allowedFields) || in_array($key, $allowedFields)) {
                $updateData[$snakeKey] = $value;
            }
        }

        if (empty($updateData)) {
            return false;
        }

        if (array_key_exists('customers_hub_stage_id', $updateData)) {
            $updateData['customers_hub_stage_changed_at'] = Carbon::now();
        }

        return DB::table('api_customers')
            ->where('id', $customerId)
            ->where('user_id', $userId)
            ->update(array_merge($updateData, ['updated_at' => Carbon::now()])) > 0;
    }

    /**
     * Get customer tasks.
     */
    public function getCustomerTasks(int $userId, int $customerId): array
    {
        $reminders = DB::table('reminders')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->whereNull('deleted_at')
            ->orderBy('datetime', 'asc')
            ->get();

        return $reminders->map(function ($reminder) {
            return [
                'id' => $reminder->id,
                'type' => 'contact', // Default type
                'datetime' => $reminder->datetime ? Carbon::parse($reminder->datetime)->toIso8601String() : null,
                'notes' => $reminder->description,
                'status' => $reminder->status ?? 'pending',
                'createdAt' => $reminder->created_at ? Carbon::parse($reminder->created_at)->toIso8601String() : null,
            ];
        })->toArray();
    }

    /**
     * Add task.
     */
    public function addTask(int $userId, int $customerId, array $data): int
    {
        // Convert ISO 8601 datetime to MySQL format if needed
        $datetime = $data['datetime'] ?? Carbon::now();
        if (is_string($datetime) && strpos($datetime, 'T') !== false) {
            $datetime = Carbon::parse($datetime)->format('Y-m-d H:i:s');
        }
        
        return DB::table('reminders')->insertGetId([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'title' => $data['title'] ?? 'Task',
            'description' => $data['notes'] ?? null,
            'datetime' => $datetime,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 0,
            'reminder_type_id' => 1, // Default reminder type
            'source' => $data['source'] ?? 'manual',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Update task.
     */
    public function updateTask(int $userId, int $taskId, array $data): bool
    {
        return DB::table('reminders')
            ->where('id', $taskId)
            ->where('user_id', $userId)
            ->update(array_merge($data, ['updated_at' => Carbon::now()])) > 0;
    }

    /**
     * Delete task.
     */
    public function deleteTask(int $userId, int $taskId): bool
    {
        return DB::table('reminders')
            ->where('id', $taskId)
            ->where('user_id', $userId)
            ->update(['deleted_at' => Carbon::now()]) > 0;
    }

    /**
     * Get customer properties.
     */
    public function getCustomerProperties(int $userId, int $customerId): array
    {
        $properties = DB::table('api_customer_property_interested')
            ->where('customer_id', $customerId)
            ->join('user_properties as p', 'api_customer_property_interested.property_id', '=', 'p.id')
            ->where('p.user_id', $userId)
            ->leftJoin('user_property_contents as pc', function ($join) {
                $join->on('p.id', '=', 'pc.property_id')
                    ->whereRaw('pc.language_id = (SELECT id FROM languages WHERE is_default = 1 AND user_id = p.user_id LIMIT 1)');
            })
            ->select([
                'p.id',
                'pc.title',
                'pc.address',
                'p.price',
                'p.purpose',
                'p.type',
                'p.beds',
                'p.bath',
                'p.area',
                'p.featured_image',
            ])
            ->get();

        return $properties->map(function ($property) {
            return [
                'id' => $property->id,
                'title' => $property->title,
                'address' => $property->address,
                'price' => $property->price,
                'purpose' => $property->purpose,
                'type' => $property->type,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => $property->area,
                'image' => $property->featured_image ? asset($property->featured_image) : null,
            ];
        })->toArray();
    }

    /**
     * Get customer preferences/requests.
     */
    public function getCustomerPreferences(int $userId, int $customerId): array
    {
        $inquiries = DB::table('api_customer_inquiry')
            ->where('user_id', $userId)
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $inquiries->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'propertyType' => $inquiry->property_type,
                'budget' => $inquiry->budget,
                'bedrooms' => $inquiry->bedrooms,
                'bathrooms' => $inquiry->bathrooms,
                'city' => $inquiry->city,
                'district' => $inquiry->district,
                'message' => $inquiry->message,
                'createdAt' => $inquiry->created_at ? Carbon::parse($inquiry->created_at)->toIso8601String() : null,
            ];
        })->toArray();
    }

    /**
     * Update customer preferences.
     */
    public function updatePreferences(int $userId, int $customerId, array $data): int
    {
        return DB::table('api_customer_inquiry')->insertGetId([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'stage_id' => CustomersHubStage::getDefaultStageId(),
            'property_type' => $data['propertyType'] ?? null,
            'budget' => $data['budget'] ?? null,
            'bedrooms' => $data['bedrooms'] ?? null,
            'bathrooms' => $data['bathrooms'] ?? null,
            'city' => $data['city'] ?? null,
            'district' => $data['district'] ?? null,
            'message' => $data['message'] ?? null,
            'created_at' => Carbon::now(),
        ]);
    }
}
