<?php
namespace App\Support;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;

trait LogsTenantActivity
{
    protected function logAction(Request $request, string $action, string $targetType, $targetId = null, $old = null, $new = null): void
    {
        $u = $request->user();

        ActivityLogger::log([
            'user_id'     => $u->tenantOwnerId(),// team owner
            'actor_type'  => $u->isEmployee() ? 'employee' : 'user',
            'actor_id'    => $u->id,
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'old_values'  => $old,
            'new_values'  => $new,
        ]);
    }
}
