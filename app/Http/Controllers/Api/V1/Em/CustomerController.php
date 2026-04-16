<?php

namespace App\Http\Controllers\Api\V1\Em;

use App\Http\Requests\Api\V1\Em\StoreCustomerRequest;
use App\Http\Requests\Api\V1\Em\UpdateCustomerRequest;
use App\Models\ApiCustomer;
use Illuminate\Http\Request;
use App\Services\ActivityLogger;
use App\Services\CrmCustomerStageService;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\Api\UserApiCustomerStage;

class CustomerController extends Controller
{
    use ResolvesTenant;

    /**
     * Get the current actor information for logging.
     * Since this is an employee controller, actor is always an employee.
     */
    protected function actor(): array
    {
        $user = auth('sanctum')->user();
        return [
            'type' => 'employee',
            'id' => $user->id,
        ];
    }

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
            ->with(['responsibleEmployee.activeWhatsappUser'])
            ->orderByDesc('id');

        return response()->json($q->paginate((int)($request->per_page ?? 20)));
    }

    // POST /em/customers
    public function store(StoreCustomerRequest $request, CrmCustomerStageService $stageService)
    {
        $tenantId = $this->tenantId();
        $actor = $this->actor(); // employee

        $data = $request->validated();

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
            'password'       => bcrypt('12345678'),
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
        $c = ApiCustomer::where('user_id',$tenantId)->with(['responsibleEmployee.activeWhatsappUser'])->findOrFail($id);
        return response()->json($c);
    }

    // PUT /em/customers/{id}
    public function update(UpdateCustomerRequest $request, $id, CrmCustomerStageService $stageService)
    {
        $tenantId = $this->tenantId();
        $actor = $this->actor();

        $c = ApiCustomer::where('user_id',$tenantId)->findOrFail($id);

        $data = $request->validated();

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
