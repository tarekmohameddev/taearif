<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Models\Api\EmployeeActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;

class LogController extends Controller
{
    use ResolvesTenant;

    public function __construct()
    {
        // Owner by default; if you want employees with 'logs.read',
        // add middleware('auth:employees_api','employee.can:logs.read')
        $this->middleware('auth:sanctum');
    }

    // GET /logs
    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $q = EmployeeActivityLog::where('user_id', $tenantId)
            ->when($request->filled('actor_type'), fn($qb) => $qb->where('actor_type', $request->actor_type))
            ->when($request->filled('actor_id'), fn($qb) => $qb->where('actor_id', (int) $request->actor_id))
            ->when($request->filled('action'), fn($qb) => $qb->where('action', $request->action))
            ->when($request->filled('target_type'), fn($qb) => $qb->where('target_type', $request->target_type))
            ->when($request->filled('target_id'), fn($qb) => $qb->where('target_id', (int) $request->target_id))
            ->when($request->filled('date_from'), fn($qb) => $qb->whereDate('created_at','>=',$request->date_from))
            ->when($request->filled('date_to'), fn($qb) => $qb->whereDate('created_at','<=',$request->date_to))
            ->orderByDesc('id');

        return response()->json($q->paginate((int)($request->per_page ?? 20)));
    }
}
