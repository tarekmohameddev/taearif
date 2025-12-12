<?php

namespace App\Models\Api;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {
  protected $table = 'api_roles';
  protected $fillable = ['user_id','name','permissions'];

  protected $casts = ['permissions' => 'array'];

  // Legacy method removed - employees are now managed via Spatie permissions
  // Employees are User models with account_type='employee' and use Spatie's role system
}
