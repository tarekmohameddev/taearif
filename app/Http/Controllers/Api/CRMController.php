<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Crm\BulkImportCustomersRequest;
use App\Http\Requests\Api\Crm\ChangeCustomerPriorityRequest;
use App\Http\Requests\Api\Crm\ChangeCustomerProcedureRequest;
use App\Http\Requests\Api\Crm\ChangeCustomerStageRequest;
use App\Http\Requests\Api\Crm\ChangeCustomerTypeRequest;
use App\Http\Requests\Api\SearchCustomersRequest;
use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerAppointment;
use App\Services\CrmCustomerStageService;
use App\Services\TenantCrmBootstrapService;

class CRMController extends Controller
{
    // index
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $request->user()->tenantOwnerId();

        // ===== Bootstrap defaults =====
        // Kept here so the CRM board self-heals for tenants created before these
        // rows were seeded at tenant creation; the definitions themselves live in
        // TenantCrmBootstrapService. Deliberately not ensureForTenant(), which also
        // rewrites auto-customer settings.
        $bootstrap = app(TenantCrmBootstrapService::class);
        $bootstrap->ensureDefaultStages((int) $tenantId);
        $bootstrap->ensureDefaultProcedures((int) $tenantId);
        $bootstrap->ensureDefaultPriorities((int) $tenantId);
        $bootstrap->ensureDefaultTypes((int) $tenantId);

        $totalCustomers = ApiCustomer::where('user_id', $tenantId)->count();

        // ===== DRY helpers =====
        $withRelations = [
            'city:id,name_ar,name_en',
            'district:id,name_ar,name_en',
            'type:id,name',
            'stage:id,stage_name',
            'priorityRef:id,name',
            'procedure:id,procedure_name',
            'responsibleEmployee.activeWhatsappUser',
        ];

        $buildQuery = function (callable $scope) use ($tenantId, $withRelations) {
            $q = ApiCustomer::where('user_id', $tenantId)->with($withRelations);
            $scope($q);
            return $q;
        };

        // ===== OPTIMIZATION: Collect all customer IDs that will be used across all sections =====
        // This allows us to batch load counts in 2 queries instead of 2N queries
        $stages = UserApiCustomerStage::where('user_id', $tenantId)->orderBy('order')->get();
        $priorities = UserApiCustomerPriority::where('user_id', $tenantId)->orderBy('order')->get();
        $procedures = UserApiCustomerProcedure::where('user_id', $tenantId)->orderBy('order')->get();
        $types = UserApiCustomerType::where('user_id', $tenantId)->orderBy('order')->get();

        // OPTIMIZED: Collect all customer IDs in a single query instead of N queries
        // This reduces from N+4 queries to 1 query for customer ID collection
        $allCustomerIds = ApiCustomer::where('user_id', $tenantId)
            ->where(function($q) use ($stages, $priorities, $procedures, $types) {
                $q->whereIn('stage_id', $stages->pluck('id'))
                  ->orWhereIn('priority_id', $priorities->pluck('id'))
                  ->orWhereIn('procedure_id', $procedures->pluck('id'))
                  ->orWhereIn('type_id', $types->pluck('id'));
            })
            ->pluck('id')
            ->unique();

        // OPTIMIZATION: Batch load all counts in 2 queries instead of 2N queries
        $remindersCounts = collect();
        $appointmentsCounts = collect();

        if ($allCustomerIds->isNotEmpty()) {
            $remindersCounts = UserApiCustomerReminder::whereIn('customer_id', $allCustomerIds)
                ->selectRaw('customer_id, COUNT(*) as count')
                ->groupBy('customer_id')
                ->pluck('count', 'customer_id');

            $appointmentsCounts = UserApiCustomerAppointment::whereIn('customer_id', $allCustomerIds)
                ->selectRaw('customer_id, COUNT(*) as count')
                ->groupBy('customer_id')
                ->pluck('count', 'customer_id');
        }

        // Updated mapCustomer to use pre-loaded counts (same result, just faster)
        $mapCustomer = function ($customer) use ($remindersCounts, $appointmentsCounts) {
            $remindersCount = $remindersCounts->get($customer->id, 0);
            $appointmentsCount = $appointmentsCounts->get($customer->id, 0);

            return [
                'customer_id'  => $customer->id,
                'name'         => $customer->name,
                'email'        => $customer->email,
                'phone_number' => $customer->phone_number,

                'city' => $customer->city ? [
                    'id'      => $customer->city->id,
                    'name_ar' => $customer->city->name_ar,
                    'name_en' => $customer->city->name_en,
                ] : null,

                'district' => $customer->district ? [
                    'id'      => $customer->district->id,
                    'name_ar' => $customer->district->name_ar,
                    'name_en' => $customer->district->name_en,
                ] : null,

                'type_id'            => $customer->type_id,
                'priority_id'        => $customer->priority_id,
                'stage_id'           => $customer->stage_id,
                'procedure_id'       => $customer->procedure_id,
                'responsible_employee' => $customer->responsibleEmployee ? [
                    'id' => $customer->responsibleEmployee->id,
                    'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                    'email' => $customer->responsibleEmployee->email,
                    'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
                ] : null,
                'reminders_count'    => $remindersCount,
                'appointments_count' => $appointmentsCount,

                'city_id'            => $customer->city_id,
                'district_id'        => $customer->district_id, // keep raw id too
            ];
        };

        // ===== OPTIMIZATION: Use SQL aggregation to get counts and group data efficiently =====
        // Instead of loading all customers, use SQL GROUP BY for counts and selective loading
        // This prevents memory exhaustion for users with many customers
        
        // Get customer counts per stage/priority/procedure/type using SQL aggregation
        $stageCounts = ApiCustomer::where('user_id', $tenantId)
            ->select('stage_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('stage_id')
            ->groupBy('stage_id')
            ->pluck('count', 'stage_id');

        $priorityCounts = ApiCustomer::where('user_id', $tenantId)
            ->select('priority_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('priority_id')
            ->groupBy('priority_id')
            ->pluck('count', 'priority_id');

        $procedureCounts = ApiCustomer::where('user_id', $tenantId)
            ->select('procedure_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('procedure_id')
            ->groupBy('procedure_id')
            ->pluck('count', 'procedure_id');

        $typeCounts = ApiCustomer::where('user_id', $tenantId)
            ->select('type_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('type_id')
            ->groupBy('type_id')
            ->pluck('count', 'type_id');

        // OPTIMIZED: Load customers with select() to limit columns and use chunking for large datasets
        // Only load essential columns to reduce memory usage
        $allCustomers = ApiCustomer::where('user_id', $tenantId)
            ->select([
                'id', 'name', 'email', 'phone_number', 'city_id', 'district_id',
                'type_id', 'priority_id', 'stage_id', 'procedure_id', 'responsible_employee_id'
            ])
            ->with($withRelations)
            ->get();

        // Group customers by their IDs for quick lookup
        $customersByStageId = $allCustomers->groupBy('stage_id');
        $customersByPriorityId = $allCustomers->groupBy('priority_id');
        $customersByProcedureId = $allCustomers->groupBy('procedure_id');
        $customersByTypeId = $allCustomers->groupBy('type_id');

        // ===== Stages =====
        $stagesSummary = [];
        $stagesWithCustomers = [];

        foreach ($stages as $stage) {
            // Get customers from pre-grouped collection (no query)
            $stageCustomers = $customersByStageId->get($stage->id, collect());
            $customers = $stageCustomers->map($mapCustomer);

            $stagesSummary[] = [
                'stage_id'       => $stage->id,
                'stage_name'     => $stage->stage_name,
                'color'          => $stage->color,
                'icon'           => $stage->icon,
                'customer_count' => $stageCounts->get($stage->id, 0), // Use SQL aggregated count
            ];

            $stagesWithCustomers[] = [
                'stage_id'   => $stage->id,
                'stage_name' => $stage->stage_name,
                'customers'  => $customers, // Same structure
            ];
        }

        // ===== Priorities =====
        $prioritiesWithCustomers = [];

        foreach ($priorities as $priority) {
            $priorityCustomers = $customersByPriorityId->get($priority->id, collect());
            $customers = $priorityCustomers->map($mapCustomer);

            $prioritiesWithCustomers[] = [
                'priority_id'    => $priority->id,
                'priority_value' => $priority->value,
                'priority_name'  => $priority->name,
                'color'          => $priority->color,
                'icon'           => $priority->icon,
                'customer_count' => $priorityCounts->get($priority->id, 0), // Use SQL aggregated count
                'customers'      => $customers, // Same structure
            ];
        }

        // ===== Procedures =====
        $proceduresWithCustomers = [];

        foreach ($procedures as $proc) {
            $procCustomers = $customersByProcedureId->get($proc->id, collect());
            $customers = $procCustomers->map($mapCustomer);

            $proceduresWithCustomers[] = [
                'procedure_id'   => $proc->id,
                'procedure_name' => $proc->procedure_name,
                'color'          => $proc->color,
                'icon'           => $proc->icon,
                'customer_count' => $procedureCounts->get($proc->id, 0), // Use SQL aggregated count
                'customers'      => $customers, // Same structure
            ];
        }

        // ===== Types =====
        $typesWithCustomers = [];

        foreach ($types as $type) {
            $typeCustomers = $customersByTypeId->get($type->id, collect());
            $customers = $typeCustomers->map($mapCustomer);

            $typesWithCustomers[] = [
                'type_id'        => $type->id,
                'type_value'     => $type->value,
                'type_name'      => $type->name,
                'color'          => $type->color,
                'icon'           => $type->icon,
                'customer_count' => $typeCounts->get($type->id, 0), // Use SQL aggregated count
                'customers'      => $customers, // Same structure
            ];
        }

        return response()->json([
            'status'                    => 'success',
            'total_customers'           => $totalCustomers,
            'stages_summary'            => $stagesSummary,
            'stages_with_customers'     => $stagesWithCustomers,
            'priorities_with_customers' => $prioritiesWithCustomers,
            'procedures_with_customers' => $proceduresWithCustomers,
            'types_with_customers'      => $typesWithCustomers,
        ]);
    }


    // changeCustomerStage
    public function changeCustomerStage(ChangeCustomerStageRequest $request, $id, CrmCustomerStageService $stageService)
    {
        $user = auth()->user();
        $tenantId = $user->tenantOwnerId();
        $validated = $request->validated();

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $stage = UserApiCustomerStage::where('id', $validated['stage_id'])
            ->where('user_id', $tenantId)
            ->first();

        if (!$stage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stage not found or does not belong to you.'
            ], 404);
        }

        $stageService->changeStage($customer, $stage);

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer moved to new stage successfully',
            'data'    => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'new_stage_id' => $stage->id,
                'new_stage_name' => $stage->stage_name,
            ]
        ]);
    }

    // changeCustomerPriority
    public function changeCustomerPriority(ChangeCustomerPriorityRequest $request, $id)
    {
        $user = auth()->user();
        $tenantId = $user->tenantOwnerId();
        $validated = $request->validated();

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $customer->priority_id = (int) $validated['priority_id'];
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer priority updated successfully',
            'data'    => [
                'customer_id'    => $customer->id,
                'customer_name'  => $customer->name,
                'new_priority'   => $customer->priority_id,
                'priority_label' => $customer->priority_label,
            ]
        ]);
    }

    // changeCustomerType rent or sale
    public function changeCustomerType(ChangeCustomerTypeRequest $request, $id)
    {
        $user = auth()->user();
        $tenantId = $user->tenantOwnerId();
        $validated = $request->validated();

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $customer->type_id = $validated['type_id'];
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer type updated successfully',
            'data'    => [
                'customer_id'       => $customer->id,
                'customer_name'     => $customer->name,
                'new_customer_type_id' => $customer->type_id,
            ]
        ]);
    }

    // changeCustomerProcedure meeting visit
    public function changeCustomerProcedure(ChangeCustomerProcedureRequest $request, $id)
    {
        $user = auth()->user();
        $tenantId = $user->tenantOwnerId();
        $validated = $request->validated();

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $tenantId)
            ->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $procedure = UserApiCustomerProcedure::where('id', $validated['procedure_id'])
            ->where('user_id', $tenantId)
            ->first();

        if (!$procedure) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Procedure not found or does not belong to you.'
            ], 404);
        }

        $customer->procedure_id = $procedure->id;
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer moved to new procedure successfully',
            'data'    => [
                'customer_id'      => $customer->id,
                'customer_name'    => $customer->name,
                'new_procedure_id' => $procedure->id,
                'new_procedure'    => $procedure->procedure_name,
            ]
        ]);
    }

    //  search customers + filter
    public function searchCustomers(SearchCustomersRequest $request)
    {
        $user  = $request->user();
        $qText = trim((string)$request->get('q', ''));

        $toIntArray = function ($v): array {
            if (is_null($v) || $v === '') return [];
            if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
            if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
            if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
            return [];
        };
        $catIds  = $toIntArray($request->input('interested_category_ids'));
        $propIds = $toIntArray($request->input('interested_property_ids'));

        $perPage = (int) ($request->input('per_page') ?: 10);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = \App\Models\ApiCustomer::where('user_id', $request->user()->tenantOwnerId())
        ->with([
            'city:id,name_ar,name_en',
            'district:id,name_ar,name_en',
            'type:id,name',
            'stage:id,stage_name',
            'priorityRef:id,name',
            'procedure:id,procedure_name',
            'responsibleEmployee.activeWhatsappUser',
        ]);

        if ($qText !== '') {
            $query->where(function ($sub) use ($qText) {
                $sub->where('name', 'like', "%{$qText}%")
                    ->orWhere('email', 'like', "%{$qText}%")
                    ->orWhere('phone_number', 'like', "%{$qText}%")
                    ->orWhereHas('responsibleEmployee', function ($empQuery) use ($qText) {
                        $empQuery->where('first_name', 'like', "%{$qText}%")
                                 ->orWhere('last_name', 'like', "%{$qText}%");
                    })
                    ->orWhereHas('responsibleEmployee.activeWhatsappUser', function ($whatsappQuery) use ($qText) {
                        $whatsappQuery->where('number', 'like', "%{$qText}%");
                    });
            });
        }

        if ($request->filled('name'))         $query->where('name',        'like', '%' . trim($request->input('name')) . '%');
        if ($request->filled('email'))        $query->where('email',       'like', '%' . trim($request->input('email')) . '%');
        if ($request->filled('phone_number') && strtolower(trim($request->input('phone_number'))) !== 'all') {
            $query->where('phone_number','like', '%' . trim($request->input('phone_number')) . '%');
        }

        // Fix: Use filled() for all integer filters - more reliable than has() + null checks
        if ($request->filled('city_id')) {
            $query->where('city_id', (int)$request->input('city_id'));
        }
        // if ($request->filled('district_id'))   $query->where('district_id',   (int)$request->input('district_id'));
        if ($request->filled('type_id')) {
            $query->where('type_id', (int)$request->input('type_id'));
        }
        if ($request->filled('priority_id')) {
            $query->where('priority_id', (int)$request->input('priority_id'));
        }
        if ($request->filled('procedure_id')) {
            $query->where('procedure_id', (int)$request->input('procedure_id'));
        }
        if ($request->filled('stage_id')) {
            $query->where('stage_id', (int)$request->input('stage_id'));
        }
        if ($request->filled('responsible_employee_id')) {
            $query->where('responsible_employee_id', (int)$request->input('responsible_employee_id'));
        }
        
        // Filter by employee's WhatsApp number
        if ($request->filled('employee_whatsapp_number')) {
            $query->whereHas('responsibleEmployee.activeWhatsappUser', function ($sub) use ($request) {
                $sub->where('number', 'like', '%'.$request->input('employee_whatsapp_number').'%');
            });
        }

        if (!empty($catIds)) {
            $query->whereExists(function ($sub) use ($catIds) {
                $sub->select(DB::raw(1))
                    ->from('api_customer_property_interested as ac1')
                    ->whereColumn('ac1.customer_id', 'api_customers.id')
                    ->whereIn('ac1.category_id', $catIds);
            });
        }
        if (!empty($propIds)) {
            $query->whereExists(function ($sub) use ($propIds) {
                $sub->select(DB::raw(1))
                    ->from('api_customer_property_interested as ac2')
                    ->whereColumn('ac2.customer_id', 'api_customers.id')
                    ->whereIn('ac2.property_id', $propIds);
            });
        }

        $query->orderBy($sortBy, $sortDir);
        
        // Fix: Get filtered total count before pagination
        $totalFiltered = (clone $query)->count();
        $paginator = $query->paginate($perPage);

        $customerIds = $paginator->getCollection()->pluck('id')->all();

        $catRows = DB::table('api_customer_property_interested as ac')
            ->whereIn('ac.customer_id', $customerIds)
            ->whereNotNull('ac.category_id')
            ->join('api_user_categories as c', 'c.id', '=', 'ac.category_id')
            ->select('ac.customer_id', 'c.id', 'c.name')
            ->distinct()
            ->get()
            ->groupBy('customer_id');

        $propRows = DB::table('api_customer_property_interested as ac')
            ->whereIn('ac.customer_id', $customerIds)
            ->whereNotNull('ac.property_id')
            ->join('user_properties as up', 'up.id', '=', 'ac.property_id')
            ->leftJoin('user_property_contents as upc', 'upc.property_id', '=', 'up.id')
            ->select('ac.customer_id', 'up.id as id', DB::raw('MAX(upc.title) as name'))
            ->groupBy('ac.customer_id', 'up.id')
            ->get()
            ->groupBy('customer_id');

        $customers = $paginator->getCollection()->map(function ($customer) use ($catRows, $propRows) {
            $city = $customer->city;

            $cats = collect($catRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            $props = collect($propRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            return [
                    'id'           => $customer->id,
                    'name'         => $customer->name,
                    'email'        => $customer->email,
                    'phone_number' => $customer->phone_number,

                    'type' => $customer->type ? [
                        'id'   => $customer->type->id,
                        'name' => $customer->type->name,
                    ] : null,

                    'stage' => $customer->stage ? [
                        'id'   => $customer->stage->id,
                        'name' => $customer->stage->stage_name,
                    ] : null,

                    'priority' => $customer->priorityRef ? [
                        'id'   => $customer->priorityRef->id,
                        'name' => $customer->priorityRef->name,
                    ] : null,

                    'procedure' => $customer->procedure ? [
                        'id'   => $customer->procedure->id,
                        'name' => $customer->procedure->procedure_name,
                    ] : null,
                    'responsible_employee' => $customer->responsibleEmployee ? [
                        'id' => $customer->responsibleEmployee->id,
                        'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                        'email' => $customer->responsibleEmployee->email,
                        'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
                    ] : null,
                    'city' => $city ? [
                        'id'       => $city->id,
                        'name_ar'  => $city->name_ar,
                        'name_en'  => $city->name_en,
                    ] : null,
                    'district' => $customer->district ? [
                        'id'       => $customer->district->id,
                        'name_ar'  => $customer->district->name_ar,
                        'name_en'  => $customer->district->name_en,
                    ] : null,

                    'note'         => $customer->note ?? '',
                    'city_id'      => $customer->city_id ?? null,
                    'district_id'  => $customer->district_id ?? null,
                    'created_by'   => $customer->user_id,
                    'created_at'   => optional($customer->created_at)->toISOString(),
                    'updated_at'   => optional($customer->updated_at)->toISOString(),

                    'interested_categories' => $cats,
                    'interested_properties' => $props,
                ];
            })->values();

        // Fix: Use filtered count instead of total count of all customers
        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => ['total_customers' => $totalFiltered],
                'customers' => $customers,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * Export CRM customers to Excel/CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        $filters = $request->only([
            'type_id',
            'priority_id',
            'stage_id',
            'procedure_id',
            'city_id',
            'district_id',
            'responsible_employee_id',
            'search',
        ]);

        $format = $request->input('format', 'xlsx');
        $filename = 'crm_customers_export_' . now()->format('Y-m-d_His');

        if ($format === 'csv') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CrmCustomersExport($tenantId, $filters),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CrmCustomersExport($tenantId, $filters),
            $filename . '.xlsx'
        );
    }

    /**
     * Download the CRM customer import template
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CrmCustomersTemplateExport($tenantId),
            'crm_customers_import_template.xlsx'
        );
    }

    /**
     * Bulk import CRM customers from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkImport(BulkImportCustomersRequest $request)
    {
        $user = $request->user();
        $tenantId = $user->tenantOwnerId();

        try {
            // Calculate the exact number of rows with data
            $filePath = $request->file('file')->getPathname();
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow();

            // Count incoming rows
            $import = new \App\Imports\CrmCustomersImport($tenantId, $highestRow);
            $collection = \Maatwebsite\Excel\Facades\Excel::toCollection($import, $request->file('file'));
            $firstSheet = $collection->first();

            $incomingRowCount = $firstSheet->filter(function ($row) {
                $rowArray = $row->toArray();

                if (isset($rowArray['_skip_empty_row']) && $rowArray['_skip_empty_row'] === true) {
                    return false;
                }

                $hasData = !empty(array_filter($rowArray, function ($value) {
                    return !is_null($value) && $value !== '';
                }));

                return $hasData;
            })->count();

            Log::info('CRM customer bulk import started', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'incoming_rows' => $incomingRowCount,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to read uploaded file: ' . $e->getMessage(),
            ], 422);
        }

        try {
            // Perform the actual import
            $import = new \App\Imports\CrmCustomersImport($tenantId, $highestRow);
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $failures = $import->sheetImport->failures();
            $errors = $import->sheetImport->errors();
            $detailedErrors = [];

            // Collect Validation Failures
            foreach ($failures as $failure) {
                $detailedErrors[] = [
                    'row' => $failure->row(),
                    'message' => 'Validation Error: ' . implode(', ', $failure->errors()),
                    'values' => $failure->values(),
                ];
            }

            // Collect Logic Errors (Exceptions)
            foreach ($errors as $error) {
                $message = $error->getMessage();
                $row = null;

                if (preg_match('/Row (\d+):/', $message, $matches)) {
                    $row = (int) $matches[1];
                }

                $detailedErrors[] = [
                    'row' => $row,
                    'message' => $message,
                ];
            }

            $importedCount = $import->sheetImport->importedCount;
            $failedCount = count($detailedErrors);

            if ($failedCount > 0) {
                return response()->json([
                    'status' => 'partial_success',
                    'message' => "Import completed with issues. Imported: {$importedCount}, Failed: {$failedCount}.",
                    'imported_count' => $importedCount,
                    'failed_count' => $failedCount,
                    'errors' => $detailedErrors,
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'message' => "Import successful. {$importedCount} customers created.",
                'imported_count' => $importedCount,
                'failed_count' => 0,
                'errors' => [],
            ]);

        } catch (\Exception $e) {
            Log::error('CRM bulk customer import critical error', [
                'user_id' => $user->id,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'A critical error occurred during import: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function ok($data = [], int $code = 200)
    {
        return response()->json(array_merge(['status' => 'success'], $data), $code);
    }
    private function error($message, int $code = 400)
    {
        return response()->json(['status' => 'error', 'message' => $message], $code);
    }


}
