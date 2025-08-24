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
        $u        = $request->user();
        $tenantId = $u->tenantOwnerId(); // <- from your User model helpers

        $q = EmployeeActivityLog::query()
            ->where('user_id', $tenantId)
            ->when($request->filled('actor_type'),  fn($qb) => $qb->where('actor_type',  $request->actor_type))
            ->when($request->filled('actor_id'),    fn($qb) => $qb->where('actor_id',    (int) $request->actor_id))
            ->when($request->filled('action'),      fn($qb) => $qb->where('action',      $request->action))
            ->when($request->filled('target_type'), fn($qb) => $qb->where('target_type', $request->target_type))
            ->when($request->filled('target_id'),   fn($qb) => $qb->where('target_id',   (int) $request->target_id))
            ->when($request->filled('date_from'),   fn($qb) => $qb->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'),     fn($qb) => $qb->whereDate('created_at', '<=', $request->date_to));

        // Simple free-text search
        if ($s = trim((string) $request->get('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('action', 'like', "%{$s}%")
                  ->orWhere('target_type', 'like', "%{$s}%")
                  ->orWhere('ip', 'like', "%{$s}%");
            });
        }

        $q->orderByDesc('id');

        $perPage   = max(1, min(100, (int) $request->integer('per_page', 20)));
        $paginator = $q->paginate($perPage);

        $withActor = (bool) $request->boolean('with_actor', false);
        $rows = $paginator->getCollection()->map(function ($r) use ($withActor) {
            $item = [
                'id'          => $r->id,
                'action'      => $r->action,
                'actor_type'  => $r->actor_type, // 'user' or 'employee'
                'actor_id'    => $r->actor_id,
                'target_type' => $r->target_type,
                'target_id'   => $r->target_id,
                'old_values'  => $r->old_values,
                'new_values'  => $r->new_values,
                'ip'          => $r->ip,
                'user_agent'  => $r->user_agent,
                'created_at'  => $r->created_at,
            ];

            if ($withActor && $r->actor_id) {
                $actor = \App\Models\User::find($r->actor_id);
                if ($actor) {
                    $item['actor'] = [
                        'id'           => $actor->id,
                        'name'         => trim(($actor->first_name ?? '').' '.($actor->last_name ?? ''))
                                          ?: ($actor->username ?? $actor->email),
                        'email'        => $actor->email,
                        'account_type' => $actor->account_type, // 'tenant' or 'employee'
                    ];
                }
            }
            return $item;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'logs' => $rows,
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
}
