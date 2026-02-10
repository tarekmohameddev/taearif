<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAssignmentRule extends Model
{
    protected $fillable = ['user_id', 'employee_id', 'is_active', 'rules'];
    
    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get the tenant owner that owns the rule.
     */
    public function tenant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    /**
     * Get the employee that this rule applies to.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
