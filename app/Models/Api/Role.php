<?php

namespace App\Models\Api;

use App\Models\Api\Employee;
use Illuminate\Database\Eloquent\Model;

class Role extends Model {
  protected $table = 'api_roles';
  protected $fillable = ['user_id','name','permissions'];

  protected $casts = ['permissions' => 'array'];

  public function employees() {
    return $this->belongsToMany(Employee::class, 'api_employee_role', 'role_id', 'employee_id');
  }
}
