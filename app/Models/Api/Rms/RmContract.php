<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RmContract extends Model
{
    use SoftDeletes;

    protected $table = 'rm_contracts';

    protected $fillable = [
        'user_id', 'rental_id', 'contract_number',
        'start_date', 'end_date', 'status',
        'termination_reason', 'file_path', 'created_by', 'updated_by',
        'property_id',
         'project_id',
        'property_name',
        'project_name',
        'water_fee_monthly',
        'office_commission_type',
        'office_commission_value',
        'platform_fee',
        'grace_period_months',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'water_fee_monthly' => 'decimal:2',
        'office_commission_value' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'grace_period_months' => 'integer',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function installments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'contract_id');
    }
}
