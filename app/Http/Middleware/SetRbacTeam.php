<?php

use Closure;
use Spatie\Permission\PermissionRegistrar;

class SetRbacTeam {
  public function __construct(protected PermissionRegistrar $registrar) {}

  public function handle($request, Closure $next) {
    if ($u = $request->user()) {
      $teamId = $u->account_type === 'tenant' ? (int)$u->id : (int)($u->tenant_id ?? 0);
      if ($teamId) $this->registrar->setPermissionsTeamId($teamId);
    }
    return $next($request);
  }
}
