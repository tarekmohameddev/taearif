<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EmployeeActivityLog extends Model {
  protected $table = 'api_employee_activity_logs';
  protected $fillable = [
    'user_id','actor_type','actor_id','action','target_type','target_id',
    'old_values','new_values','ip','user_agent'
  ];
  protected $casts = ['old_values'=>'array','new_values'=>'array'];

  /**
   * Get the actor (user) who performed this action
   */
  public function actor()
  {
    return $this->belongsTo(User::class, 'actor_id');
  }

  /**
   * Get the tenant owner (user_id references tenant owner)
   */
  public function tenantOwner()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
