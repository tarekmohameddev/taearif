<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerAppointment;

class CRMController extends Controller
{
    // index
    public function index(Request $request)
    {
        $user = $request->user();


        $hasStages = UserApiCustomerStage::where('user_id', $user->id)->exists();
        if (!$hasStages) {
            $defaultStages = [
                ['stage_name' => 'طلب معاينه',        'order' => 1],
                ['stage_name' => 'صفقة بيع او ايجار', 'order' => 2],
                ['stage_name' => 'اقفال الصفقة',      'order' => 3],
            ];
            foreach ($defaultStages as $stage) {
                UserApiCustomerStage::create([
                    'user_id'   => $user->id,
                    'stage_name'=> $stage['stage_name'],
                    'order'     => $stage['order'],
                    'is_active' => true,
                ]);
            }
        }

        $defaultProcedures = [
            ['procedure_name' => 'meeting', 'order' => 1, 'icon' => 'users', 'color' => '#2196f3'],
            ['procedure_name' => 'visit',   'order' => 2, 'icon' => 'map',   'color' => '#ff9800'],
        ];
        foreach ($defaultProcedures as $p) {
            UserApiCustomerProcedure::firstOrCreate(
                ['user_id' => $user->id, 'procedure_name' => $p['procedure_name']],
                ['order' => $p['order'], 'icon' => $p['icon'], 'color' => $p['color'], 'is_active' => true]
            );
        }


        $priorityDefaults = [
            ['name'=>'Low',    'value'=>1, 'order'=>1, 'color'=>'#4caf50','icon'=>'arrow-down'],
            ['name'=>'Medium', 'value'=>2, 'order'=>2, 'color'=>'#ff9800','icon'=>'minus'],
            ['name'=>'High',   'value'=>3, 'order'=>3, 'color'=>'#f44336','icon'=>'arrow-up'],
        ];
        foreach ($priorityDefaults as $p) {
            UserApiCustomerPriority::firstOrCreate(
                ['user_id'=>$user->id, 'value'=>$p['value']],
                ['name'=>$p['name'], 'order'=>$p['order'], 'color'=>$p['color'], 'icon'=>$p['icon'], 'is_active'=>true]
            );
        }


        $defaultTypes = [
            ['name' => 'Rent',   'value' => 'Rent',   'order' => 1, 'icon' => 'home',      'color' => '#2196f3'],
            ['name' => 'Sale',   'value' => 'Sale',   'order' => 2, 'icon' => 'dollar',    'color' => '#4caf50'],
            ['name' => 'Rented', 'value' => 'Rented', 'order' => 3, 'icon' => 'check',     'color' => '#9e9e9e'],
            ['name' => 'Sold',   'value' => 'Sold',   'order' => 4, 'icon' => 'check-all', 'color' => '#9e9e9e'],
            ['name' => 'Both',   'value' => 'Both',   'order' => 5, 'icon' => 'arrows',    'color' => '#ff9800'],
        ];
        foreach ($defaultTypes as $t) {
            UserApiCustomerType::firstOrCreate(
                ['user_id' => $user->id, 'value' => $t['value']],
                ['name' => $t['name'], 'order' => $t['order'], 'icon' => $t['icon'], 'color' => $t['color'], 'is_active' => true]
            );
        }

        $totalCustomers = ApiCustomer::where('user_id', $user->id)->count();

        $stages = UserApiCustomerStage::where('user_id', $user->id)->orderBy('order')->get();
        $stagesSummary = [];
        $stagesWithCustomers = [];
        foreach ($stages as $stage) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)->where('stage_id', $stage->id);
            $customerCount = $customerQuery->count();

            $stagesSummary[] = [
                'stage_id'       => $stage->id,
                'stage_name'     => $stage->stage_name,
                'color'          => $stage->color,
                'icon'           => $stage->icon,
                'customer_count' => $customerCount,
            ];

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'email'              => $customer->email,
                    'phone_number'       => $customer->phone_number,
                    'city'               => $customer->city, // if you want names, eager-load relation instead
                    'type_id'            => $customer->type_id,
                    'priority_id'        => $customer->priority_id,
                    'stage_id'           => $customer->stage_id,
                    'procedure_id'       => $customer->procedure_id,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $stagesWithCustomers[] = [
                'stage_id'   => $stage->id,
                'stage_name' => $stage->stage_name,
                'customers'  => $customers,
            ];
        }

        $priorities = UserApiCustomerPriority::where('user_id', $user->id)->orderBy('order')->get();
        $prioritiesWithCustomers = [];
        foreach ($priorities as $priority) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)->where('priority_id', $priority->id);
            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'email'              => $customer->email,
                    'phone_number'       => $customer->phone_number,
                    'city'               => $customer->city,
                    'type_id'            => $customer->type_id,
                    'priority_id'        => $customer->priority_id,
                    'stage_id'           => $customer->stage_id,
                    'procedure_id'       => $customer->procedure_id,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $prioritiesWithCustomers[] = [
                'priority_id'    => $priority->id,
                'priority_value' => $priority->value, // keep for display if you use it
                'priority_name'  => $priority->name,
                'color'          => $priority->color,
                'icon'           => $priority->icon,
                'customer_count' => $customerCount,
                'customers'      => $customers,
            ];
        }

        $procedures = UserApiCustomerProcedure::where('user_id', $user->id)->orderBy('order')->get();
        $proceduresWithCustomers = [];
        foreach ($procedures as $proc) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)->where('procedure_id', $proc->id);
            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'email'              => $customer->email,
                    'phone_number'       => $customer->phone_number,
                    'city'               => $customer->city,
                    'type_id'            => $customer->type_id,
                    'priority_id'        => $customer->priority_id,
                    'stage_id'           => $customer->stage_id,
                    'procedure_id'       => $customer->procedure_id,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $proceduresWithCustomers[] = [
                'procedure_id'   => $proc->id,
                'procedure_name' => $proc->procedure_name,
                'color'          => $proc->color,
                'icon'           => $proc->icon,
                'customer_count' => $customerCount,
                'customers'      => $customers,
            ];
        }

        $types = UserApiCustomerType::where('user_id', $user->id)->orderBy('order')->get();
        $typesWithCustomers = [];
        foreach ($types as $type) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)->where('type_id', $type->id);
            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'email'              => $customer->email,
                    'phone_number'       => $customer->phone_number,
                    'city'               => $customer->city,
                    'type_id'            => $customer->type_id,
                    'priority_id'        => $customer->priority_id,
                    'stage_id'           => $customer->stage_id,
                    'procedure_id'       => $customer->procedure_id,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $typesWithCustomers[] = [
                'type_id'        => $type->id,
                'type_value'     => $type->value, // optional
                'type_name'      => $type->name,
                'color'          => $type->color,
                'icon'           => $type->icon,
                'customer_count' => $customerCount,
                'customers'      => $customers,
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
    public function changeCustomerStage(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'stage_id' => 'required|integer|exists:users_api_customers_stages,id',
        ]);

        $customer = \App\Models\ApiCustomer::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $stage = \App\Models\Api\UserApiCustomerStage::where('id', $validated['stage_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$stage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stage not found or does not belong to you.'
            ], 404);
        }

        $customer->stage_id = $stage->id;
        $customer->save();

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
    public function changeCustomerPriority(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'priority_id' => 'required|integer', // 1=Low, 2=Medium, 3=High
        ]);

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $user->id)
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
    public function changeCustomerType(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'type_id' => 'required|integer',
        ]);

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $user->id)
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
    public function changeCustomerProcedure(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'procedure_id' => 'required|integer|exists:users_api_customers_procedures,id',
        ]);

        $customer = ApiCustomer::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$customer) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Customer not found or does not belong to you.'
            ], 404);
        }

        $procedure = UserApiCustomerProcedure::where('id', $validated['procedure_id'])
            ->where('user_id', $user->id)
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
    public function searchCustomers(Request $request)
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

        $request->validate([
            'name'          => 'nullable|string|max:255',
            'email'         => 'nullable|string|max:255',
            'phone_number'  => 'nullable|string|max:20',
            'city_id'       => 'nullable|integer',
            'district_id'   => 'nullable|integer',
            'type_id'       => 'nullable|integer',
            'priority_id'   => 'nullable|integer',
            'procedure_id'  => 'nullable|integer',
            'stage_id'      => 'nullable|integer',

            'page'          => 'nullable|integer|min:1',
            'per_page'      => 'nullable|integer|min:1|max:100',
            'sort_by'       => 'nullable|in:name,email,phone_number,created_at,priority_id,type_id,stage_id,procedure_id,city_id,district_id',
            'sort_dir'      => 'nullable|in:asc,desc',
        ]);

        $perPage = (int) ($request->input('per_page') ?: 10);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = \App\Models\ApiCustomer::where('user_id', $user->id)
        ->with([
            'district.city',
            'city',
            'type:id,name',
            'stage:id,stage_name',
            'priorityRef:id,name',
            'procedure:id,procedure_name',
        ]);

        if ($qText !== '') {
            $query->where(function ($sub) use ($qText) {
                $sub->where('name', 'like', "%{$qText}%")
                    ->orWhere('email', 'like', "%{$qText}%")
                    ->orWhere('phone_number', 'like', "%{$qText}%");
            });
        }

        if ($request->filled('name'))         $query->where('name',        'like', '%' . trim($request->input('name')) . '%');
        if ($request->filled('email'))        $query->where('email',       'like', '%' . trim($request->input('email')) . '%');
        if ($request->filled('phone_number')) $query->where('phone_number','like', '%' . trim($request->input('phone_number')) . '%');

        if ($request->filled('city_id'))       $query->where('city_id',       (int)$request->input('city_id'));
        if ($request->filled('district_id'))   $query->where('district_id',   (int)$request->input('district_id'));
        if ($request->filled('type_id'))       $query->where('type_id',       (int)$request->input('type_id'));
        if ($request->filled('priority_id'))   $query->where('priority_id',   (int)$request->input('priority_id'));
        if ($request->filled('procedure_id'))  $query->where('procedure_id',  (int)$request->input('procedure_id'));
        if ($request->filled('stage_id'))      $query->where('stage_id',      (int)$request->input('stage_id'));

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
            $district     = $customer->district;
            $districtCity = $district?->city;
            $rootCity     = $customer->city;

            $districtPayload = $district ? [
                'id'              => $district->id,
                'name_ar'         => $district->name_ar ?? null,
                'name_en'         => $district->name_en ?? null,
                'city_id'         => $district->city_id ?? $rootCity?->id,
                'city_name_ar'    => $districtCity?->name_ar ?? $rootCity?->name_ar ?? null,
                'city_name_en'    => $districtCity?->name_en ?? $rootCity?->name_en ?? null,
                'country_name_ar' => $district->country_name_ar ?? null,
                'country_name_en' => $district->country_name_en ?? null,
                'created_at'      => optional($district->created_at)->toISOString(),
                'updated_at'      => optional($district->updated_at)->toISOString(),
            ] : 'N/A';

            $cats = collect($catRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            $props = collect($propRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            return [
                'id'                    => $customer->id,
                'name'                  => $customer->name,
                'email'                 => $customer->email,
                'phone_number'          => $customer->phone_number,
                'type' => $customer->type ? [
                    'id' => $customer->type->id,
                    'name' => $customer->type->name,
                ] : null,

                'stage' => $customer->stage ? [
                    'id' => $customer->stage->id,
                    'name' => $customer->stage->stage_name,
                ] : null,

                'priority' => $customer->priorityRef ? [
                    'id' => $customer->priorityRef->id,
                    'name' => $customer->priorityRef->name,
                ] : null,

                'procedure' => $customer->procedure ? [
                    'id' => $customer->procedure->id,
                    'name' => $customer->procedure->procedure_name,
                ] : null,                'district'              => $districtPayload,
                'note'                  => $customer->note ?? '',
                'city_id'               => $customer->city_id ?? null,
                'created_by'            => $customer->user_id,
                'created_at'            => optional($customer->created_at)->toISOString(),
                'updated_at'            => optional($customer->updated_at)->toISOString(),
                'interested_categories' => $cats,
                'interested_properties' => $props,
            ];
        })->values();

        $totalAll = \App\Models\ApiCustomer::where('user_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => ['total_customers' => $totalAll],
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

    private function ok($data = [], int $code = 200)
    {
        return response()->json(array_merge(['status' => 'success'], $data), $code);
    }
    private function error($message, int $code = 400)
    {
        return response()->json(['status' => 'error', 'message' => $message], $code);
    }


}
