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

    protected $appends = [
        'current_property_id',
        'current_project_id',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function installments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'contract_id');
    }

    public function getCurrentPropertyIdAttribute()
    {
        return optional($this->rental)->property_id;
    }

    public function getCurrentProjectIdAttribute()
    {
        return optional($this->rental)->project_id;
    }

    protected static function booted()
    {
        static::creating(function (self $contract) {
            if (is_null($contract->property_id) || is_null($contract->project_id)) {
                $rental = $contract->rental ?: RmRental::find($contract->rental_id);
                if ($rental) {
                    $contract->property_id = $contract->property_id ?? $rental->property_id;
                    $contract->project_id  = $contract->project_id  ?? $rental->project_id;
                }
            }
        });

        static::saving(function (self $contract) {
            // When moving to active, ensure snapshot identifiers exist
            if ((is_null($contract->property_id) || is_null($contract->project_id)) && $contract->status === 'active') {
                $rental = $contract->rental ?: RmRental::find($contract->rental_id);
                if ($rental) {
                    $contract->property_id = $contract->property_id ?? $rental->property_id;
                    $contract->project_id  = $contract->project_id  ?? $rental->project_id;
                }
            }
        });
    }
}
