<?php
namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeRole extends Pivot
{
    protected static function booted()
    {
        static::created(function ($pivot) {
            optional($pivot->employee)->forgetPermissionCache();
        });
        static::deleted(function ($pivot) {
            optional($pivot->employee)->forgetPermissionCache();
        });
        static::updated(function ($pivot) {
            optional($pivot->employee)->forgetPermissionCache();
        });
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Api\Employee::class, 'employee_id');
    }
}
