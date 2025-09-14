<?php

namespace App\Models\Api\Rms;

use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmReminder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmPaymentInstallment;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;

class RmRental extends Model
{
    use SoftDeletes;

    protected $table = 'rm_rentals';

    protected $fillable = [
        'user_id', 
        'property_id', 
        'unit_label', 
        'project_id',
        'tenant_full_name', 
        'property_number', 
        'tenant_phone', 
        'tenant_email', 
        'tenant_job_title',
        'tenant_social_status', 
        'tenant_national_id',
        'base_rent_amount', 
        'currency', 
        'deposit_amount',
        'platform_fee',
        'water_fee',
        'office_commission_type',
        'office_commission_value',
        'office_fee',
        'contract_number',
        'total_rental_amount',
        'move_in_date', 
        'paying_plan', 
        'rental_period',
        'status', 
        'notes', 
        'created_by', 
        'updated_by',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'platform_fee' => 'decimal:2',
        'water_fee' => 'decimal:2',
        'office_commission_value' => 'decimal:2',
        'office_fee' => 'decimal:2',
        'total_rental_amount' => 'decimal:2',
    ];

    protected $appends = [
        'next_payment_due_date',
        'next_payment_amount',
    ];

    public function contracts()
    {
        return $this->hasMany(RmContract::class, 'rental_id');
    }

    public function activeContract()
    {
        return $this->hasOne(RmContract::class, 'rental_id')->where('status', 'active');
    }

    public function latestContract()
    {
        return $this->hasOne(RmContract::class, 'rental_id')->latest();
    }

    public function expiredContracts()
    {
        return $this->hasMany(RmContract::class, 'rental_id')->where('status', 'expired');
    }

    public function pendingContracts()
    {
        return $this->hasMany(RmContract::class, 'rental_id')->where('status', 'pending');
    }

    public function installments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'rental_id');
    }

    public function payments()
    {
        return $this->hasMany(RmPayment::class, 'rental_id');
    }

    public function maintenance()
    {
        return $this->hasMany(RmMaintenanceTicket::class, 'rental_id');
    }

    public function reminders()
    {
        return $this->hasMany(RmReminder::class, 'rental_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function upcomingInstallments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'rental_id')->where('due_date', '>', now());
    }

    public function maintenanceOpen()
    {
        return $this->hasMany(RmMaintenanceTicket::class, 'rental_id')->where('status', 'open');
    }

    public function getNextPaymentDueDateAttribute()
    {
        $installment = $this->installments()
            ->whereIn('status', ['pending', 'active'])
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->first();

        return $installment?->due_date;
    }

    public function getNextPaymentAmountAttribute()
    {
        $installment = $this->installments()
            ->whereIn('status', ['pending', 'active'])
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->first();

        return $installment?->amount;
    }

    public function getOfficeFeeAttribute()
    {
        // If any required field is null, return 0
        if (is_null($this->office_commission_type) || 
            is_null($this->office_commission_value) || 
            is_null($this->rental_period) || 
            is_null($this->base_rent_amount)) {
            return 0;
        }

        // Calculate based on commission type
        if ($this->office_commission_type === 'percentage') {
            return ($this->rental_period * $this->base_rent_amount) * ($this->office_commission_value / 100);
        } elseif ($this->office_commission_type === 'amount') {
            return $this->office_commission_value;
        }

        return 0;
    }

    public function getTotalRentalAmountAttribute()
    {
        // If any required field is null, return 0
        if (is_null($this->base_rent_amount) || 
            is_null($this->rental_period)) {
            return 0;
        }

        // Simple calculation: base_rent_amount × rental_period
        return $this->base_rent_amount * $this->rental_period;
    }

}
