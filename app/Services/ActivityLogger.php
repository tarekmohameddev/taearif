<?php

namespace App\Services;

use App\Models\Api\EmployeeActivityLog;


class ActivityLogger {
  public static function log(array $data): void {
    $ctx = request();
    $defaults = [
      'ip' => $ctx->ip(),
      'user_agent' => $ctx->userAgent(),
      'created_at' => now(), 'updated_at' => now()
    ];
    EmployeeActivityLog::create(array_merge($defaults, $data));
  }
}
/*
*Use it when you want to log an activity
/*Role assigned/removed
/*Customer created/updated/deleted
/*Login/Logout
*/
// Assign roles to employee
// ActivityLogger::log([
//     'user_id'     => $tenantId,
//     'actor_type'  => 'user',
//     'actor_id'    => auth()->id(),
//     'action'      => 'role.assigned',
//     'target_type' => 'api_employees',
//     'target_id'   => $employee->id,
//     'old_values'  => null,
//     'new_values'  => ['roles' => $roleIds],
//   ]);

// Customer created by employee
// ActivityLogger::log([
//     'user_id'     => $employee->user_id,
//     'actor_type'  => 'employee',
//     'actor_id'    => $employee->id,
//     'action'      => 'customer.created',
//     'target_type' => 'api_customers',
//     'target_id'   => $customer->id,
//     'new_values'  => $customer->only(['name','email','phone_number']),
//   ]);
