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

class CRMController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        //Check if user already has stages
        $hasStages = UserApiCustomerStage::where('user_id', $user->id)->exists();

        if (!$hasStages) {
            // Create default stages
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

        //total customers for this user
        $totalCustomers = ApiCustomer::where('user_id', $user->id)->count();

        //all stages with customer
        $stages = UserApiCustomerStage::where('user_id', $user->id)
            ->orderBy('order', 'asc')
            ->get();

        $stagesSummary = [];
        $stagesWithCustomers = [];

        foreach ($stages as $stage) {
            // Count customers in this stage
            $customerQuery = ApiCustomer::where('user_id', $user->id)->where('stage_id', $stage->id);

            $customerCount = $customerQuery->count();

            $stagesSummary[] = [
                'stage_id'       => $stage->id,
                'stage_name'     => $stage->stage_name,
                'color'          => $stage->color,
                'icon'           => $stage->icon,
                'customer_count' => $customerCount,
            ];

            // Fetch customers with info
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


}
