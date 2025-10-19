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
        'unit_id',
        'project_id',
        'tenant_full_name',
        'building_id',
        'tenant_phone',
        'tenant_email',
        'tenant_job_title',
        'tenant_social_status',
        'tenant_national_id',
        'base_rent_amount',
        'currency',
        'rental_type',
        'rental_duration',
        'total_rental_amount',
        'contract_number',
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
        'base_rent_amount' => 'decimal:2',
        'total_rental_amount' => 'decimal:2',
        'rental_duration' => 'integer',
    ];

    protected $appends = [
        'next_payment_due_date',
        'next_payment_amount',
        'base_rent_amount',
        'total_tenant_costs',
        'total_owner_costs',
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

    public function costItems()
    {
        return $this->hasMany(RentalCostItem::class, 'rental_id');
    }

    public function expenses()
    {
        return $this->hasMany(\App\Models\RmExpense::class, 'rental_id');
    }

    public function activeExpenses()
    {
        return $this->hasMany(\App\Models\RmExpense::class, 'rental_id')->where('is_active', true);
    }

    public function activeCostItems()
    {
        return $this->hasMany(RentalCostItem::class, 'rental_id')->active();
    }

    public function tenantCostItems()
    {
        return $this->hasMany(RentalCostItem::class, 'rental_id')->active()->forTenant();
    }

    public function ownerCostItems()
    {
        return $this->hasMany(RentalCostItem::class, 'rental_id')->active()->forOwner();
    }

    public function property()
    {
        return $this->belongsTo(Property::class, 'unit_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function building()
    {
        return $this->belongsTo(\App\Models\Building::class, 'building_id');
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

    public function getBaseRentAmountAttribute()
    {
        // Calculate base rent amount = amount per payment based on paying_plan
        // base_rent_amount = total_rental_amount / number_of_payments

        if (is_null($this->total_rental_amount) ||
            is_null($this->rental_duration) ||
            is_null($this->rental_type) ||
            is_null($this->paying_plan) ||
            $this->rental_duration <= 0) {  // Prevent division by zero
            return 0;
        }

        // Calculate total months based on rental_type
        if ($this->rental_type === 'monthly') {
            $totalMonths = $this->rental_duration;
        } elseif ($this->rental_type === 'annual') {
            $totalMonths = $this->rental_duration * 12;
        } else {
            return 0;
        }

        // Calculate number of payments based on paying_plan
        // monthly: every month → totalMonths / 1
        // quarterly: every 3 months → totalMonths / 3
        // semi_annual: every 6 months → totalMonths / 6
        // annual: yearly → totalMonths / 12
        $paymentInterval = match($this->paying_plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        // Calculate number of payments
        $numberOfPayments = ceil($totalMonths / $paymentInterval);

        if ($numberOfPayments <= 0) {
            return 0;
        }

        // Calculate base rent amount per payment
        return $this->total_rental_amount / $numberOfPayments;
    }

    public function getTotalTenantCostsAttribute()
    {
        // Calculate total costs that tenant needs to pay
        $totalCosts = 0;

        foreach ($this->tenantCostItems as $costItem) {
            if ($costItem->type === 'fixed') {
                $totalCosts += $costItem->cost;
            } elseif ($costItem->type === 'percentage') {
                $baseAmount = $costItem->percentage_of ?? $this->total_rental_amount;
                $totalCosts += ($baseAmount * $costItem->cost) / 100;
            }
        }

        return $totalCosts;
    }

    public function getTotalOwnerCostsAttribute()
    {
        // Calculate total costs that owner needs to pay
        $totalCosts = 0;

        foreach ($this->ownerCostItems as $costItem) {
            if ($costItem->type === 'fixed') {
                $totalCosts += $costItem->cost;
            } elseif ($costItem->type === 'percentage') {
                $baseAmount = $costItem->percentage_of ?? $this->total_rental_amount;
                $totalCosts += ($baseAmount * $costItem->cost) / 100;
            }
        }

        return $totalCosts;
    }

}
