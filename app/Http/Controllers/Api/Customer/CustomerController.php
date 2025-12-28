<?php

namespace App\Http\Controllers\Api\Customer;

use Throwable;
use App\Models\ApiCustomer;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Models\User\UserCity;
use App\Support\TenantActivity;
use Illuminate\Validation\Rule;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Api\UserApiCustomerType;
use Illuminate\Database\QueryException;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\ApiCustomerPropertyInterested;
use Illuminate\Validation\ValidationException;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\User;
use App\Support\Audit;


class CustomerController extends Controller
{

    // filterOptions
    public function filterOptions(Request $request)
    {
        $user = $request->user();

        $usedOnly = (bool) $request->boolean('used_only', true);
        $cityId   = $request->input('city_id');

        $types = UserApiCustomerType::where('user_id', $user->id)
            ->orderBy('order')
            ->get(['id', 'name', 'value', 'icon', 'color']);

        $priorities = UserApiCustomerPriority::where('user_id', $user->id)
            ->orderBy('order')
            ->get(['id', 'name', 'value', 'icon', 'color']);

        $stages = UserApiCustomerStage::where('user_id', $user->id)
            ->orderBy('order')
            ->get(['id', 'stage_name as name', 'icon', 'color']);

        $procedures = UserApiCustomerProcedure::where('user_id', $user->id)
            ->orderBy('order')
            ->get(['id', 'procedure_name as name', 'icon', 'color']);

        if ($usedOnly) {
            $cityIds = ApiCustomer::where('user_id', $user->id)->whereNotNull('city_id')->distinct()->pluck('city_id');
            $cities = UserCity::whereIn('id', $cityIds)->orderBy('name_ar')->get(['id','name_ar','name_en']);

            $districtQuery = UserDistrict::query()
                ->whereIn('id', ApiCustomer::where('user_id', $user->id)->whereNotNull('district_id')->distinct()->pluck('district_id'));
        } else {
            $cities = UserCity::orderBy('name_ar')->get(['id','name_ar','name_en']);
            $districtQuery = UserDistrict::query();
        }
        if ($cityId) {
            $districtQuery->where('city_id', (int) $cityId);
        }
        $districts = $districtQuery->orderBy('name_ar')->get(['id','city_id','name_ar','name_en']);

        $employees = User::where('tenant_id', $user->tenantOwnerId())
            ->where('account_type', 'employee')
            ->where('active', true)
            ->get(['id', 'first_name', 'last_name', 'email']);

        $employeesList = $employees->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')),
                'email' => $emp->email,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'types'      => $types,
                'priorities' => $priorities,
                'stages'     => $stages,
                'procedures' => $procedures,
                'cities'     => $cities,
                'districts'  => $districts,
                'employees'  => $employeesList,
            ],
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $customers = ApiCustomer::where('user_id', $user->id)
            ->with([
                'city:id,name_ar,name_en',
                'district:id,name_ar,name_en',
                'type:id,name',
                'stage:id,stage_name',
                'priorityRef:id,name',
                'procedure:id,procedure_name',
                'responsibleEmployee.activeWhatsappUser',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Helpers to map values to Arabic
        $mapInquiryTypeToArabic = function ($type) {
            if (is_null($type) || $type === '') return null;
            $t = mb_strtolower(trim($type));
            return match ($t) {
                'inquire', 'inquiry', 'question' => 'استفسار',
                'buy', 'purchase', 'sale', '购买' => 'شراء',
                'rent', 'rental', 'lease' => 'إيجار',
                default => 'استفسار', // fallback to generic
            };
        };

        $mapPropertyTypeToArabic = function ($type) {
            if (is_null($type) || $type === '') return null;
            $val = trim($type);
            // If already contains Arabic characters, keep as-is
            if (preg_match('/\p{Arabic}/u', $val)) {
                return $val;
            }
            $t = mb_strtolower($val);
            $map = [
                'residential' => 'سكني',
                'commercial'  => 'تجاري',
                'industrial'  => 'صناعي',
                'agricultural'=> 'زراعي',
                'apartment'   => 'شقة',
                'villa'       => 'فيلا',
                'land'        => 'أرض',
                'office'      => 'مكتب',
                'shop'        => 'محل',
                'warehouse'   => 'مستودع',
            ];
            return $map[$t] ?? $val;
        };

        $formattedCustomers = $customers->map(function ($customer) use ($mapInquiryTypeToArabic, $mapPropertyTypeToArabic) {
            $district = $customer->district;
            
            $interestedCategories = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
            ->join('api_user_categories', 'api_user_categories.id', '=', 'api_customer_property_interested.category_id')
            ->select('api_user_categories.id', 'api_user_categories.name')
            ->distinct()
            ->get();

        $interestedProperties = ApiCustomerPropertyInterested::where('customer_id', $customer->id)
            ->join('user_properties as up', 'up.id', '=', 'api_customer_property_interested.property_id')
            ->leftJoin('user_property_contents as upc', 'upc.property_id', '=', 'up.id')
            ->select('up.id', DB::raw('MAX(upc.title) as name'))
            ->groupBy('up.id')
            ->get();

            // Collect and merge customer inquiries
            $inquiries = DB::table('api_customer_inquiry')
                ->where('customer_id', $customer->id)
                ->orderByDesc('created_at')
                ->get(['message', 'inquiry_type', 'property_type', 'location', 'city', 'district']);

            $agg = [
                'message'       => null,
                'inquiry_type'  => null,
                'property_type' => null,
                'location'      => null,
                'city'          => null,
                'district'      => null,
            ];

            foreach ($inquiries as $row) {
                if (is_null($agg['message'])       && !empty($row->message))       $agg['message']       = $row->message;
                if (is_null($agg['inquiry_type'])  && !empty($row->inquiry_type))  $agg['inquiry_type']  = $row->inquiry_type;
                if (is_null($agg['property_type']) && !empty($row->property_type)) $agg['property_type'] = $row->property_type;
                if (is_null($agg['city'])          && !empty($row->city))          $agg['city']          = $row->city;
                if (is_null($agg['district'])      && !empty($row->district))      $agg['district']      = $row->district;
                if (is_null($agg['location'])      && !empty($row->location))      $agg['location']      = $row->location;
                // Break early if all fields are filled
                if (!in_array(null, $agg, true)) {
                    break;
                }
            }

            // Prepare district info (up to 3 unique values); fallback to location if missing
            $districtInfo = null;
            if ($inquiries->isNotEmpty()) {
                $districtsList = $inquiries->pluck('district')->filter()->unique()->take(3);
                $citiesList    = $inquiries->pluck('city')->filter()->unique()->take(3);

                if ($districtsList->isNotEmpty() || $citiesList->isNotEmpty()) {
                    $districtInfo = [
                        'name_ar'     => $districtsList->implode(','),
                        'city_name_ar'=> $citiesList->implode(','),
                    ];
                } elseif (!empty($agg['location'])) {
                    $districtInfo = [
                        'name_ar'      => null,
                        'city_name_ar' => $agg['location'],
                    ];
                }
            }

            // Build merged inquiry object
            $inquiryPayload = null;
            if ($agg['message'] || $agg['inquiry_type'] || $agg['property_type']) {
                $inquiryPayload = [
                    'message'       => $agg['message'],
                    'inquiry_type'  => $mapInquiryTypeToArabic($agg['inquiry_type']),
                    'property_type' => $mapPropertyTypeToArabic($agg['property_type']),
                ];
            }

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
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
                ] : null,
                'responsible_employee' => $customer->responsibleEmployee ? [
                    'id' => $customer->responsibleEmployee->id,
                    'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                    'email' => $customer->responsibleEmployee->email,
                    'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
                ] : null,
                'note' => $customer->note ?? null,
                'inquiry' => $inquiryPayload,
                'city_id' => $customer->city_id ?? null,
                'district' => $district ? [
                    'id'      => $district->id,
                    'name_ar' => $district->name_ar,
                    'name_en' => $district->name_en,
                ] : $districtInfo, // fallback to inquiry-derived info when relation absent
                'created_by' => $customer->user_id,
                'created_at' => $customer->created_at->toISOString(),
                'updated_at' => $customer->updated_at->toISOString(),
                'interested_categories' => $interestedCategories,
                'interested_properties' => $interestedProperties,

            ];
        });

        $totalCustomers = ApiCustomer::where('user_id', $user->id)->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'summary' => [
                    'total_customers' => $totalCustomers,
                ],
                'customers' => $formattedCustomers,
                'pagination' => [
                    'total' => $customers->total(),
                    'per_page' => $customers->perPage(),
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ]
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {
        return $this->guard(function () use ($request) {
            $user = $request->user();

            $toIntArray = function ($v): array {
                if (is_null($v) || $v === '') return [];
                if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
                if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
                if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
                return [];
            };
            $request->merge([
                'interested_category_ids' => $toIntArray($request->input('interested_category_ids')),
                'interested_property_ids' => $toIntArray($request->input('interested_property_ids')),
            ]);

            $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => ['nullable','email',
                    Rule::unique('api_customers','email')->where(fn($q)=>$q->where('user_id',$user->id)),
                ],
                'phone_number' => ['required','string','max:20',
                    Rule::unique('api_customers','phone_number')->where(fn($q)=>$q->where('user_id',$user->id)),
                ],
                'city_id'      => 'nullable|exists:user_cities,id',
                'district_id'  => 'nullable|exists:user_districts,id',
                'note'         => 'nullable|string',
                'type_id'      => ['required', Rule::exists('users_api_customers_types','id')->where(fn($q)=>$q->where('user_id',$user->id))],
                'priority_id'  => ['required', Rule::exists('users_api_customers_priorities','id')->where(fn($q)=>$q->where('user_id',$user->id))],
                'responsible_employee_id' => [
                    'nullable',
                    Rule::exists('users', 'id')->where(function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenantOwnerId())
                              ->where('account_type', 'employee')
                              ->where('active', true);
                    }),
                ],
                'stage_id'     => ['nullable', Rule::exists('users_api_customers_stages','id')->where(fn($q)=>$q->where('user_id',$user->id))],
                'procedure_id' => ['nullable', Rule::exists('users_api_customers_procedures','id')->where(fn($q)=>$q->where('user_id',$user->id))],
                'password'     => 'required|string|min:6',
                'interested_category_ids'   => 'nullable|array',
                'interested_category_ids.*' => 'integer|exists:api_user_categories,id',
                'interested_property_ids'   => 'nullable|array',
                'interested_property_ids.*' => 'integer|exists:user_properties,id',
            ]);

            $customer = null;

            \DB::transaction(function () use ($request, $user, &$customer) {
                $customer = \App\Models\ApiCustomer::create([
                    'user_id'      => $user->id,
                    'name'         => $request->name,
                    'email'        => $request->email,
                    'city_id'      => $request->city_id,
                    'district_id'  => $request->district_id,
                    'note'         => $request->note,
                    'type_id'      => $request->type_id,
                    'priority_id'  => $request->priority_id,
                    'responsible_employee_id' => $request->responsible_employee_id,
                    'stage_id'     => $request->stage_id,
                    'procedure_id' => $request->procedure_id,
                    'phone_number' => $request->phone_number,
                    'password'     => bcrypt($request->password),
                ]);

                foreach (($request->interested_category_ids ?? []) as $catId) {
                    \App\Models\ApiCustomerPropertyInterested::firstOrCreate([
                        'user_id'     => $user->id,
                        'customer_id' => $customer->id,
                        'category_id' => (int)$catId,
                    ]);
                }
                foreach (($request->interested_property_ids ?? []) as $propId) {
                    \App\Models\ApiCustomerPropertyInterested::firstOrCreate([
                        'user_id'     => $user->id,
                        'customer_id' => $customer->id,
                        'property_id' => (int)$propId,
                    ]);
                }
            });

            TenantActivity::emit($request, 'customer.created', 'api_customers', $customer->id, null, $customer->only(['name','email','phone_number']));

            return $this->ok([
                'message' => 'Customer created successfully',
                'data'    => $customer,
            ], 201);
        });
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

        $customer = ApiCustomer::where('user_id', $user->id)
            ->with(['responsibleEmployee.activeWhatsappUser'])
            ->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $customerData = $customer->toArray();
        if ($customer->responsibleEmployee) {
            $customerData['responsible_employee'] = [
                'id' => $customer->responsibleEmployee->id,
                'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                'email' => $customer->responsibleEmployee->email,
                'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
            ];
        } else {
            $customerData['responsible_employee'] = null;
        }

        return response()->json([
            'status' => 'success',
            'data' => $customerData
        ]);

    }

    /**
     * Return customer details along with all inquiries (Arabic mappings applied).
     */
    public function showWithInquiries(Request $request, $id)
    {
        $user = $request->user();

        $customer = ApiCustomer::where('user_id', $user->id)
            ->with([
                'type:id,name',
                'stage:id,stage_name',
                'priorityRef:id,name',
                'procedure:id,procedure_name',
                'responsibleEmployee.activeWhatsappUser',
            ])
            ->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        // Local mappers
        $mapInquiryTypeToArabic = function ($type) {
            if (is_null($type) || $type === '') return null;
            $t = mb_strtolower(trim($type));
            return match ($t) {
                'inquire', 'inquiry', 'question' => 'استفسار',
                'buy', 'purchase', 'sale', '购买' => 'شراء',
                'rent', 'rental', 'lease' => 'إيجار',
                default => 'استفسار',
            };
        };

        $mapPropertyTypeToArabic = function ($type) {
            if (is_null($type) || $type === '') return null;
            $val = trim($type);
            if (preg_match('/\p{Arabic}/u', $val)) {
                return $val;
            }
            $t = mb_strtolower($val);
            $map = [
                'residential' => 'سكني',
                'commercial'  => 'تجاري',
                'industrial'  => 'صناعي',
                'agricultural'=> 'زراعي',
                'apartment'   => 'شقة',
                'villa'       => 'فيلا',
                'land'        => 'أرض',
                'office'      => 'مكتب',
                'shop'        => 'محل',
                'warehouse'   => 'مستودع',
            ];
            return $map[$t] ?? $val;
        };

        // Fetch all inquiries
        $rows = DB::table('api_customer_inquiry')
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get(['id','message','inquiry_type','property_type','budget','currency','location','city','district','created_at']);

        // Build district info with fallback
        $districtInfo = null;
        if ($rows->isNotEmpty()) {
            $districtsList = $rows->pluck('district')->filter()->unique()->take(3);
            $citiesList    = $rows->pluck('city')->filter()->unique()->take(3);
            if ($districtsList->isNotEmpty() || $citiesList->isNotEmpty()) {
                $districtInfo = [
                    'name_ar'      => $districtsList->implode(','),
                    'city_name_ar' => $citiesList->implode(','),
                ];
            } else {
                $location = $rows->firstWhere('location', '!=', null)->location ?? null;
                if (!empty($location)) {
                    $districtInfo = [
                        'name_ar'      => null,
                        'city_name_ar' => $location,
                    ];
                }
            }
        }

        // Shape inquiries with Arabic mapping
        $inquiries = $rows->map(function ($r) use ($mapInquiryTypeToArabic, $mapPropertyTypeToArabic) {
            return [
                'id'            => $r->id,
                'message'       => $r->message,
                'inquiry_type'  => $mapInquiryTypeToArabic($r->inquiry_type),
                'property_type' => $mapPropertyTypeToArabic($r->property_type),
                'budget'        => $r->budget,
                'currency'      => $r->currency,
                'location'      => $r->location,
                'city'          => $r->city,
                'district'      => $r->district,
                'created_at'    => optional($r->created_at)->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone_number' => $customer->phone_number,
                    'type' => $customer->type ? [ 'id' => $customer->type->id, 'name' => $customer->type->name ] : null,
                    'stage' => $customer->stage ? [ 'id' => $customer->stage->id, 'name' => $customer->stage->stage_name ] : null,
                    'priority' => $customer->priorityRef ? [ 'id' => $customer->priorityRef->id, 'name' => $customer->priorityRef->name ] : null,
                    'procedure' => $customer->procedure ? [ 'id' => $customer->procedure->id, 'name' => $customer->procedure->procedure_name ] : null,
                    'responsible_employee' => $customer->responsibleEmployee ? [
                        'id' => $customer->responsibleEmployee->id,
                        'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                        'email' => $customer->responsibleEmployee->email,
                        'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
                    ] : null,
                    'district' => $districtInfo,
                    'created_at' => optional($customer->created_at)->toISOString(),
                    'updated_at' => optional($customer->updated_at)->toISOString(),
                ],
                'inquiries' => $inquiries,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, \App\Services\CrmCustomerStageService $stageService)
    {
        $user = $request->user();
        $customer = \App\Models\ApiCustomer::where('user_id',$user->id)->findOrFail($id);

        $toIntArray = function ($v): array {
            if (is_null($v) || $v === '') return [];
            if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
            if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
            if (is_array($v))  return array_values(array_filter(array_map('intval', $v)));
            return [];
        };
        $request->merge([
            'interested_category_ids' => $toIntArray($request->input('interested_category_ids')),
            'interested_property_ids' => $toIntArray($request->input('interested_property_ids')),
        ]);

        $request->validate([
            'name'         => 'sometimes|string|max:255',
            'email'        => ['sometimes','nullable','email',
                Rule::unique('api_customers','email')->where(fn($q)=>$q->where('user_id',$user->id))->ignore($customer->id),
            ],
            'phone_number' => ['sometimes','string','max:20',
                Rule::unique('api_customers','phone_number')->where(fn($q)=>$q->where('user_id',$user->id))->ignore($customer->id),
            ],
            'city_id'      => 'nullable|exists:user_cities,id',
            'district_id'  => 'nullable|exists:user_districts,id',
            'note'         => 'nullable|string',
            'type_id'      => ['nullable', Rule::exists('users_api_customers_types','id')->where(fn($q)=>$q->where('user_id',$user->id))],
            'priority_id'  => ['nullable', Rule::exists('users_api_customers_priorities','id')->where(fn($q)=>$q->where('user_id',$user->id))],

            'stage_id'     => ['nullable', Rule::exists('users_api_customers_stages','id')->where(fn($q)=>$q->where('user_id',$user->id))],
            'responsible_employee_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) use ($user) {
                    $query->where('tenant_id', $user->tenantOwnerId())
                          ->where('account_type', 'employee')
                          ->where('active', true);
                }),
            ],
            'procedure_id' => ['nullable', Rule::exists('users_api_customers_procedures','id')->where(fn($q)=>$q->where('user_id',$user->id))],

            'password'     => 'nullable|string|min:6',

            'interested_category_ids'   => 'nullable|array',
            'interested_category_ids.*' => 'integer|exists:api_user_categories,id',
            'interested_property_ids'   => 'nullable|array',
            'interested_property_ids.*' => 'integer|exists:user_properties,id',
        ]);

        $data = array_filter([
            'name'         => $request->input('name'),
            'email'        => $request->input('email'),
            'note'         => $request->input('note'),
            'city_id'      => $request->input('city_id'),
            'district_id'  => $request->input('district_id'),
            'type_id'      => $request->input('type_id'),
            'priority_id'  => $request->input('priority_id'),
            // stage_id is handled via CrmCustomerStageService to centralize CRM side effects
            'responsible_employee_id' => $request->input('responsible_employee_id'),
            'procedure_id' => $request->input('procedure_id'),
            'phone_number' => $request->input('phone_number'),
        ], fn($v)=>!is_null($v));

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        \DB::transaction(function () use ($request, $user, $customer, $data, $stageService) {
            $customer->update($data);

            // If stage_id is present, use the shared service to change stage
            if ($request->filled('stage_id')) {
                $stage = \App\Models\Api\UserApiCustomerStage::where('id', (int) $request->input('stage_id'))
                    ->where('user_id', $user->id)
                    ->first();

                if ($stage) {
                    $stageService->changeStage($customer, $stage);
                }
            }

            if ($request->has('interested_category_ids')) {
                \App\Models\ApiCustomerPropertyInterested::where('customer_id',$customer->id)
                    ->whereNotNull('category_id')->delete();
                foreach (($request->interested_category_ids ?? []) as $catId) {
                    \App\Models\ApiCustomerPropertyInterested::firstOrCreate([
                        'user_id'=>$user->id,'customer_id'=>$customer->id,'category_id'=>(int)$catId,
                    ]);
                }
            }

            if ($request->has('interested_property_ids')) {
                \App\Models\ApiCustomerPropertyInterested::where('customer_id',$customer->id)
                    ->whereNotNull('property_id')->delete();
                foreach (($request->interested_property_ids ?? []) as $propId) {
                    \App\Models\ApiCustomerPropertyInterested::firstOrCreate([
                        'user_id'=>$user->id,'customer_id'=>$customer->id,'property_id' => (int)$propId,
                    ]);
                }
            }
        });

        TenantActivity::emit($request, 'customer.updated', 'api_customers', $customer->id, $old ?? null, $customer->only(['name','email','phone_number', 'responsible_employee_id']));

        return response()->json([
            'status'  => 'success',
            'message' => 'Customer updated successfully',
            'data'    => $customer,
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
        $customer = ApiCustomer::where('user_id', $user->id)->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        try {
            // Store customer data for activity log before deletion
            $customerData = $customer->toArray();

            // Delete customer - related records will be automatically deleted via CASCADE
            $customer->delete();

            TenantActivity::emit($request, 'customer.deleted', 'api_customers', $customer->id, $customerData, null);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'foreign key constraint')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Cannot delete customer due to related records.',
                    'sql_error' => config('app.debug') ? $e->getMessage() : null,
                ], 409);
            }
            throw $e;
        }
    }

    /**
     * Search customers by name, email or phone
     * 
     * Filters:
     * - q: text search on name, email, phone_number
     * - city_id, district_id, type_id, priority_id, procedure_id: exact match
     * - phone_number: partial match on customer phone
     * - responsible_employee_id: filter by assigned employee
     * - employee_whatsapp_number: partial match on assigned employee's active WhatsApp number
     * - created_from, created_to: date range on created_at (YYYY-MM-DD, inclusive full days)
     * - interested_category_ids, interested_property_ids: array of IDs
     * - sort_by: name|created_at|priority_id (default: created_at)
     * - sort_dir: asc|desc (default: desc)
    */
    public function search(Request $request)
    {
        $user  = $request->user();
        $qText = $request->get('q');

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
            'city_id'       => 'nullable|integer',
            'district_id'   => 'nullable|integer',
            'type_id'       => 'nullable|integer',
            'priority_id'   => 'nullable|integer',
            'procedure_id'  => 'nullable|integer',
            'phone_number'  => 'nullable|string|max:20',
            'responsible_employee_id' => 'nullable|integer',
            'employee_whatsapp_number' => 'nullable|string|max:20',
            'created_from'  => 'nullable|date',
            'created_to'    => 'nullable|date',
            'page'          => 'nullable|integer|min:1',
            'per_page'      => 'nullable|integer|min:1|max:100',
            'sort_by'       => 'nullable|in:name,created_at,priority_id',
            'sort_dir'      => 'nullable|in:asc,desc',
        ]);

        $perPage = (int) ($request->input('per_page') ?: 10);
        $sortBy  = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = \App\Models\ApiCustomer::where('user_id', $user->id)
        ->with([
            'city:id,name_ar,name_en',
            'district:id,name_ar,name_en',
            'type:id,name',
            'stage:id,stage_name',
            'priorityRef:id,name',
            'procedure:id,procedure_name',
            'responsibleEmployee.activeWhatsappUser',
        ]);

        if (!empty($qText)) {
            $query->where(function ($sub) use ($qText) {
                $sub->where('name', 'like', "%{$qText}%")
                    ->orWhere('email', 'like', "%{$qText}%")
                    ->orWhere('phone_number', 'like', "%{$qText}%");
            });
        }

        if ($request->filled('city_id'))       $query->where('city_id',       (int)$request->input('city_id'));
        if ($request->filled('district_id'))   $query->where('district_id',   (int)$request->input('district_id'));
        if ($request->filled('type_id'))       $query->where('type_id',       (int)$request->input('type_id'));
        if ($request->filled('priority_id'))   $query->where('priority_id',   (int)$request->input('priority_id'));
        if ($request->filled('procedure_id'))  $query->where('procedure_id',  (int)$request->input('procedure_id'));
        if ($request->filled('phone_number'))  $query->where('phone_number', 'like', '%'.$request->input('phone_number').'%');
        if ($request->filled('responsible_employee_id')) $query->where('responsible_employee_id', (int)$request->input('responsible_employee_id'));
        
        // Filter by employee's WhatsApp number
        if ($request->filled('employee_whatsapp_number')) {
            $query->whereHas('responsibleEmployee.activeWhatsappUser', function ($sub) use ($request) {
                $sub->where('number', 'like', '%'.$request->input('employee_whatsapp_number').'%');
            });
        }
        
        // Date range filters (inclusive)
        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->input('created_from'));
        }
        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->input('created_to'));
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
            $district = $customer->district;

            $cats = collect($catRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            $props = collect($propRows->get($customer->id) ?? [])
                ->map(fn($r) => ['id' => (int)$r->id, 'name' => $r->name])
                ->values();

            // Get customer inquiries for district information
            $customerInquiries = DB::table('api_customer_inquiry')
                ->where('customer_id', $customer->id)
                ->whereNotNull('district')
                ->whereNotNull('city')
                ->select('district', 'city')
                ->distinct()
                ->limit(3)
                ->get();

            // Format district information
            $districtInfo = null;
            if ($customerInquiries->isNotEmpty()) {
                $districts = $customerInquiries->pluck('district')->filter()->unique()->take(3)->implode(',');
                $cities = $customerInquiries->pluck('city')->filter()->unique()->take(3)->implode(',');

                $districtInfo = [
                    'name_ar' => $districts,
                    'city_name_ar' => $cities,
                ];
            }

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
                ] : null,
                'responsible_employee' => $customer->responsibleEmployee ? [
                    'id' => $customer->responsibleEmployee->id,
                    'name' => trim(($customer->responsibleEmployee->first_name ?? '') . ' ' . ($customer->responsibleEmployee->last_name ?? '')),
                    'email' => $customer->responsibleEmployee->email,
                    'whatsapp_number' => $customer->responsibleEmployee->activeWhatsappUser ? $customer->responsibleEmployee->activeWhatsappUser->number : null,
                ] : null,
                'city' => $city ? [
                    'id'      => $city->id,
                    'name_ar' => $city->name_ar,
                    'name_en' => $city->name_en,
                ] : null,
                'district' => $district ? [
                    'id'      => $district->id,
                    'name_ar' => $district->name_ar,
                    'name_en' => $district->name_en,
                ] : $districtInfo,
                'note'                  => $customer->note ?? null,
                'district_id'           => $customer->district_id ?? null,
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

    private function guard(\Closure $fn)
    {
        try {
            return $fn();
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Resource not found',
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => 'Database error',
                'sql_error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } catch (Throwable $e) {
            return response()->json([
                'status'    => 'error',
                'message'   => $e->getMessage(),
                'exception' => config('app.debug') ? class_basename($e) : null,
            ], 500);
        }
    }

    /**
     * Download the customer import template
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate(Request $request)
    {
        $user = $request->user();
        $userId = $user?->id;

        // Resolve tenant context for unauthenticated access
        if (!$userId) {
            $tenantId = $request->input('tenant_id');

            if ($tenantId) {
                $tenantUser = \App\Models\User::find($tenantId);

                if (!$tenantUser) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid tenant_id',
                    ], 400);
                }

                $userId = $tenantUser->id;
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'tenant_id is required when not authenticated',
                ], 400);
            }
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersTemplateExport($userId),
            'customers_import_template.xlsx'
        );
    }

    /**
     * Bulk import customers from Excel/CSV file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        $user = $request->user();

        try {
            // Calculate the exact number of rows with data
            $filePath = $request->file('file')->getPathname();
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestDataRow();

            // Count incoming rows
            $import = new \App\Imports\CustomersImport($user->id, $highestRow);
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

            Log::info('Customer bulk import started', [
                'user_id' => $user->id,
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
            $import = new \App\Imports\CustomersImport($user->id, $highestRow);
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

            // Emit activity event
            TenantActivity::emit($request, 'customers.bulk_imported', 'api_customers', null, null, [
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
            ]);

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
            Log::error('Bulk customer import critical error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'A critical error occurred during import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export customers to Excel/CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $filters = $request->only([
            'type_id',
            'priority_id',
            'stage_id',
            'procedure_id',
            'city_id',
            'district_id',
            'search',
        ]);

        $format = $request->input('format', 'xlsx');
        $filename = 'customers_export_' . now()->format('Y-m-d_His');

        if ($format === 'csv') {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CustomersExport($user->id, $filters),
                $filename . '.csv',
                \Maatwebsite\Excel\Excel::CSV
            );
        }

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\CustomersExport($user->id, $filters),
            $filename . '.xlsx'
        );
    }

}
