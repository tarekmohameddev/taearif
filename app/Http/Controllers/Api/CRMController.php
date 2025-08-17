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
    public function index(Request $request)
    {
        $user = $request->user();


        $hasStages = UserApiCustomerStage::where('user_id', $user->id)->exists();

        if (!$hasStages) {

            $defaultStages = [
                ['stage_name' => 'طلب معاينه', 'order' => 1],
                ['stage_name' => 'صفقة بيع او ايجار', 'order' => 2],
                ['stage_name' => 'اقفال الصفقة', 'order' => 3],
            ];

            foreach ($defaultStages as $stage) {
                UserApiCustomerStage::create([
                    'user_id'     => $user->id,
                    'stage_name'  => $stage['stage_name'],
                    'order'       => $stage['order'],
                    'is_active'   => true,
                ]);
            }
        }

        $defaultProcedures = [
            ['procedure_name' => 'meeting', 'order' => 1, 'icon' => 'users', 'color' => '#2196f3'],
            ['procedure_name' => 'visit',   'order' => 2, 'icon' => 'map',   'color' => '#ff9800'],
        ];
        foreach ($defaultProcedures as $p) {
            UserApiCustomerProcedure ::firstOrCreate(
                ['user_id' => $user->id, 'procedure_name' => $p['procedure_name']],
                ['order' => $p['order'], 'icon' => $p['icon'], 'color' => $p['color'], 'is_active' => true]
            );
        }

        $defaults = [
            ['name'=>'Low',    'value'=>1, 'order'=>1, 'color'=>'#4caf50','icon'=>'arrow-down'],
            ['name'=>'Medium', 'value'=>2, 'order'=>2, 'color'=>'#ff9800','icon'=>'minus'],
            ['name'=>'High',   'value'=>3, 'order'=>3, 'color'=>'#f44336','icon'=>'arrow-up'],
          ];

          foreach ($defaults as $p) {
              UserApiCustomerPriority::firstOrCreate(
                  ['user_id'=>$user->id, 'value'=>$p['value']],
                  ['name'=>$p['name'], 'order'=>$p['order'], 'color'=>$p['color'], 'icon'=>$p['icon'], 'is_active'=>true]
              );
          }

        //total customers for this user
        $totalCustomers = ApiCustomer::where('user_id', $user->id)->count();

        //all stages with customer
        $stages = UserApiCustomerStage::where('user_id', $user->id)
            ->orderBy('order', 'asc')
            ->get();

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
                $remindersCount = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'city'               => $customer->city,
                    'priority'           => $customer->priority,
                    'customer_type'      => $customer->customer_type,
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
        // ===== PRIORITIES with customers =====
        $priorities = UserApiCustomerPriority::where('user_id', $user->id)
            ->orderBy('order', 'asc')
            ->get();

        $prioritiesWithCustomers = [];

        foreach ($priorities as $priority) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)
                ->where('priority', $priority->value);

            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'city'               => $customer->city, // same as your current shape
                    'priority'           => $customer->priority,
                    'customer_type'      => $customer->customer_type,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $prioritiesWithCustomers[] = [
                'priority_value'  => $priority->value,
                'priority_name'   => $priority->name,
                'color'           => $priority->color,
                'icon'            => $priority->icon,
                'customer_count'  => $customerCount,
                'customers'       => $customers,
            ];
        }

        // ===== PROCEDURES with customers =====
        $procedures = UserApiCustomerProcedure::where('user_id', $user->id)
            ->orderBy('order', 'asc')
            ->get();

        $proceduresWithCustomers = [];

        foreach ($procedures as $proc) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)
                ->where('procedure_id', $proc->id);

            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'city'               => $customer->city,
                    'priority'           => $customer->priority,
                    'customer_type'      => $customer->customer_type,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $proceduresWithCustomers[] = [
                'procedure_id'    => $proc->id,
                'procedure_name'  => $proc->procedure_name,
                'color'           => $proc->color,
                'icon'            => $proc->icon,
                'customer_count'  => $customerCount,
                'customers'       => $customers,
            ];
        }
        // ===== TYPES (seed defaults if missing) =====
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

        // ===== TYPES with customers =====
        $types = UserApiCustomerType::where('user_id', $user->id)
            ->orderBy('order', 'asc')
            ->get();

        $typesWithCustomers = [];

        foreach ($types as $type) {
            $customerQuery = ApiCustomer::where('user_id', $user->id)
                ->where('customer_type', $type->value);

            $customerCount = $customerQuery->count();

            $customers = $customerQuery->get()->map(function ($customer) {
                $remindersCount    = UserApiCustomerReminder::where('customer_id', $customer->id)->count();
                $appointmentsCount = UserApiCustomerAppointment::where('customer_id', $customer->id)->count();

                return [
                    'customer_id'        => $customer->id,
                    'name'               => $customer->name,
                    'city'               => $customer->city,
                    'priority'           => $customer->priority,
                    'customer_type'      => $customer->customer_type,
                    'reminders_count'    => $remindersCount,
                    'appointments_count' => $appointmentsCount,
                ];
            });

            $typesWithCustomers[] = [
                'type_value'     => $type->value,
                'type_name'      => $type->name,
                'color'          => $type->color,
                'icon'           => $type->icon,
                'customer_count' => $customerCount,
                'customers'      => $customers,
            ];
        }

        return response()->json([
            'status'                   => 'success',
            'total_customers'          => $totalCustomers,
            'stages_summary'           => $stagesSummary,
            'stages_with_customers'    => $stagesWithCustomers,
            'priorities_with_customers'=> $prioritiesWithCustomers,
            'procedures_with_customers'=> $proceduresWithCustomers,
            'types_with_customers'     => $typesWithCustomers,
        ]);
    }

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
            'priority' => 'required|integer|in:1,2,3', // 1=Low, 2=Medium, 3=High
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

        $customer->priority = (int) $validated['priority'];
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer priority updated successfully',
            'data'    => [
                'customer_id'    => $customer->id,
                'customer_name'  => $customer->name,
                'new_priority'   => $customer->priority,
                'priority_label' => $customer->priority_label,
            ]
        ]);
    }


    // changeCustomerType rent or sale
    public function changeCustomerType(Request $request, $id)
    {
        $user = $request->user();

        $validated = $request->validate([
            'customer_type' => ['required', Rule::in(['Rent','Sale','Rented','Sold','Both'])],
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

        $customer->customer_type = $validated['customer_type'];
        $customer->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer type updated successfully',
            'data'    => [
                'customer_id'       => $customer->id,
                'customer_name'     => $customer->name,
                'new_customer_type' => $customer->customer_type,
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

    //  search customers by name or phone + filter by stage + filter by periority
    public function searchCustomers(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'query'     => 'nullable|string|max:255',
            'stage_id'  => 'nullable|integer|exists:users_api_customers_stages,id',
            'priority'  => 'nullable|integer|in:1,2,3', // 1=Low, 2=Medium, 3=High
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'sort_by'   => 'nullable|in:name,created_at,priority',
            'sort_dir'  => 'nullable|in:asc,desc',
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $sortBy  = $validated['sort_by']  ?? 'created_at';
        $sortDir = $validated['sort_dir'] ?? 'desc';

        $query = ApiCustomer::where('user_id', $user->id);

        if (!empty($validated['query'])) {
            $q = $validated['query'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('phone_number', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (!empty($validated['stage_id'])) {
            $query->where('stage_id', $validated['stage_id']);
        }

        if (!empty($validated['priority'])) {
            $query->where('priority', (int)$validated['priority']);
        }

        $query->orderBy($sortBy, $sortDir);

        $paginator = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'customers' => $paginator->items(),
            ],
            'pagination' => [
                'total'        => $paginator->total(),
                'per_page'     => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }
}
