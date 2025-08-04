<?php

namespace App\Models\Api\Rms;

use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmReminder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmPaymentInstallment;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User\RealestateManagement\Property;

class RmRental extends Model
{
    use SoftDeletes;

    protected $table = 'rm_rentals';

    protected $fillable = [
        'user_id', 'property_id', 'unit_label',
        'tenant_full_name', 'tenant_phone', 'tenant_email', 'tenant_job_title',
        'tenant_social_status', 'tenant_national_id',
        'base_rent_amount', 'currency', 'deposit_amount',
        'move_in_date', 'paying_plan', 'rental_period_months',
        'status', 'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'move_in_date' => 'date',
    ];

    public function contracts()
    {
        return $this->hasMany(RmContract::class, 'rental_id');
    }

    public function activeContract()
    {
        return $this->hasOne(RmContract::class, 'rental_id')->where('status', 'active');
    }

    public function installments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'rental_id');
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

    public function upcomingInstallments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'rental_id')->where('due_date', '>', now());
    }

    public function maintenanceOpen()
    {
        return $this->hasMany(RmMaintenanceTicket::class, 'rental_id')->where('status', 'open');
    }

}
