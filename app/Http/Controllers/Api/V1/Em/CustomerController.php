<?php

namespace App\Http\Controllers\Api\V1\Em;

use App\Models\ApiCustomer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use App\Services\CrmCustomerStageService;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\Api\UserApiCustomerStage;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use ResolvesTenant;

    public function __construct()
    {
        $this->middleware(['auth:sanctum']);
        $this->middleware('employee.can:customer.read')->only(['index','show']);
        $this->middleware('employee.can:customer.create')->only(['store']);
        $this->middleware('employee.can:customer.update')->only(['update']);
        $this->middleware('employee.can:customer.delete')->only(['destroy']);
    }

    // GET /em/customers
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $q = ApiCustomer::where('user_id',$tenantId)
            ->when($request->filled('q'), function($qb) use ($request){
                $s = trim($request->q);
                $qb->where(function($w) use ($s){
                    $w->where('name','like',"%$s%")
                      ->orWhere('email','like',"%$s%")
                      ->orWhere('phone_number','like',"%$s%");
                });
            })
            ->when($request->filled('stage_id'), fn($qb)=>$qb->where('stage_id',(int)$request->stage_id))
            ->when($request->filled('type_id'), fn($qb)=>$qb->where('type_id',(int)$request->type_id))
            ->when($request->filled('priority_id'), fn($qb)=>$qb->where('priority_id',(int)$request->priority_id))
            ->when($request->filled('city_id'), fn($qb)=>$qb->where('city_id',(int)$request->city_id))
            ->when($request->filled('district_id'), fn($qb)=>$qb->where('district_id',(int)$request->district_id))
            ->when($request->filled('created_by_type'), fn($qb)=>$qb->where('created_by_type',$request->created_by_type))
            ->when($request->filled('created_by_id'), fn($qb)=>$qb->where('created_by_id',(int)$request->created_by_id))
            ->orderByDesc('id');

        return response()->json($q->paginate((int)($request->per_page ?? 20)));
    }

    // POST /em/customers
    public function store(Request $request, CrmCustomerStageService $stageService)
    {
        $tenantId = $this->tenantId();
        $actor = $this->actor(); // employee

        $data = $request->validate([
            'name'         => ['required','string','max:255'],
            'email'        => ['nullable','email','max:255'],
            'phone_number' => ['nullable','string','max:50'],
            'note'         => ['nullable','string','max:2000'],
            'stage_id'     => ['nullable','integer', Rule::exists('users_api_customers_stages','id')->where(fn($q)=>$q->where('user_id',$tenantId))],
            'procedure_id' => ['nullable','integer'],
            'type_id'      => ['nullable','integer'],
            'priority_id'  => ['nullable','integer'],
            'city_id'      => ['nullable','integer'],
            'district_id'  => ['nullable','integer'],
        ]);

        $stageId = $data['stage_id'] ?? null;

        $customer = ApiCustomer::create([
            'user_id'        => $tenantId,
            'name'           => $data['name'],
            'email'          => $data['email'] ?? null,
            'phone_number'   => $data['phone_number'] ?? null,
            'note'           => $data['note'] ?? null,
            'procedure_id'   => $data['procedure_id'] ?? null,
            'type_id'        => $data['type_id'] ?? null,
            'priority_id'    => $data['priority_id'] ?? null,
            'city_id'        => $data['city_id'] ?? null,
            'district_id'    => $data['district_id'] ?? null,
            'created_by_type'=> $actor['type'],
            'created_by_id'  => $actor['id'],
            'password'       => bcrypt(\Illuminate\Support\Str::random(16)),
        ]);

        if ($stageId !== null) {
            $stage = UserApiCustomerStage::where('id', $stageId)->where('user_id', $tenantId)->first();
            if ($stage) {
                $stageService->changeStage($customer, $stage);
            }
        }

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => $actor['type'],
            'actor_id'    => $actor['id'],
            'action'      => 'customer.created',
            'target_type' => 'api_customers',
            'target_id'   => $customer->id,
            'new_values'  => $customer->only([
                'name',
                'email',
                'phone_number',
                'stage_id',
                'type_id',
                'priority_id',
                'city_id',
                'district_id',
                'created_by_type',
                'procedure_id',
                'created_by_id'
            ]),
        ]);

        return response()->json($customer, 201);
    }

    // GET /em/customers/{id}
    public function show($id)
    {
        $tenantId = $this->tenantId();
        $c = ApiCustomer::where('user_id',$tenantId)->findOrFail($id);
        return response()->json($c);
    }

    // PUT /em/customers/{id}
    public function update(Request $request, $id, CrmCustomerStageService $stageService)
    {
        $tenantId = $this->tenantId();
        $actor = $this->actor();

        $c = ApiCustomer::where('user_id',$tenantId)->findOrFail($id);

        $data = $request->validate([
            'name'         => ['sometimes','string','max:255'],
            'email'        => ['sometimes','nullable','email','max:255'],
            'phone_number' => ['sometimes','nullable','string','max:50'],
            'note'         => ['sometimes','nullable','string','max:2000'],
            'stage_id'     => ['sometimes','nullable','integer', Rule::exists('users_api_customers_stages','id')->where(fn($q)=>$q->where('user_id',$tenantId))],
            'procedure_id' => ['sometimes','nullable','integer'],
            'type_id'      => ['sometimes','nullable','integer'],
            'priority_id'  => ['sometimes','nullable','integer'],
            'city_id'      => ['sometimes','nullable','integer'],
            'district_id'  => ['sometimes','nullable','integer'],
        ]);

        $stageId = array_key_exists('stage_id', $data) ? $data['stage_id'] : null;
        unset($data['stage_id']);

        $old = $c->toArray();

        $c->fill($data);
        $c->save();

        if ($stageId !== null) {
            $stage = UserApiCustomerStage::where('id', $stageId)->where('user_id', $tenantId)->first();
            if ($stage) {
                $stageService->changeStage($c, $stage);
            }
        }

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => $actor['type'],
            'actor_id'    => $actor['id'],
            'action'      => 'customer.updated',
            'target_type' => 'api_customers',
            'target_id'   => $c->id,
            'old_values'  => $old,
            'new_values'  => $c->only(array_keys($data)),
        ]);

        return response()->json($c);
    }

    // DELETE /em/customers/{id}
    public function destroy($id)
    {
        $tenantId = $this->tenantId();
        $actor = $this->actor();

        $c = ApiCustomer::where('user_id',$tenantId)->findOrFail($id);
        $snapshot = $c->toArray();
        $c->delete();

        ActivityLogger::log([
            'user_id'     => $tenantId,
            'actor_type'  => $actor['type'],
            'actor_id'    => $actor['id'],
            'action'      => 'customer.deleted',
            'target_type' => 'api_customers',
            'target_id'   => $snapshot['id'],
            'old_values'  => $snapshot,
        ]);

        return response()->noContent();
    }
}
