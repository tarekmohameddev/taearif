<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ApiCustomer;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerAppointment;
use Illuminate\Validation\Rule;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerPriority;

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

        return response()->json([
            'status'              => 'success',
            'total_customers'     => $totalCustomers,
            'stages_summary'      => $stagesSummary,
            'stages_with_customers' => $stagesWithCustomers,
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
