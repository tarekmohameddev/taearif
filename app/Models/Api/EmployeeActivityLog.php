<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;


class EmployeeActivityLog extends Model {
  protected $table = 'api_employee_activity_logs';
  protected $fillable = [
    'user_id','actor_type','actor_id','action','target_type','target_id',
    'old_values','new_values','ip','user_agent'
  ];
  protected $casts = ['old_values'=>'array','new_values'=>'array'];
}
