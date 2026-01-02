<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Models\ApiCustomerPropertyInterested;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;



class ApiCustomer extends Authenticatable

{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'api_customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'note',
        'type_id',  // 1, 2,
        'priority_id', // 1, 2, 3
        'responsible_employee_id',
        'stage_id', // 1, 2, 3, 4, 5
        'procedure_id', // 1 meeting 2 visit
        'city_id',
        'district_id',
        'phone_number',
        'password',
        'remember_token',
        'property_request_id',
        'source',
        'source_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    const PRIORITY_LABELS = [
        1 => 'Low',
        2 => 'Medium',
        3 => 'High',
    ];

    // Accessor to get readable label
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? 'Unknown';
    }
    /**
     * Set the password attribute to be hashed.
     *
     * @param  string  $value
     * @return void
     */

    /**
     * Define the relationship with the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function responsibleEmployee()
    {
        return $this->belongsTo(User::class, 'responsible_employee_id');
    }

    public function city()
    {
        return $this->belongsTo(\App\Models\User\UserCity::class, 'city_id');
    }

    public function district()
    {
        return $this->belongsTo(\App\Models\User\UserDistrict::class, 'district_id');
    }

    public function interestedCategories()
    {
        return $this->hasMany(ApiCustomerPropertyInterested::class, 'customer_id', 'id')
            ->select('customer_id', 'category_id')
            ->groupBy('customer_id', 'category_id');
    }

    public function stage()
    {
        return $this->belongsTo(\App\Models\Api\UserApiCustomerStage::class, 'stage_id');
    }

    public function procedure()
    {
        return $this->belongsTo(\App\Models\Api\UserApiCustomerProcedure::class, 'procedure_id');
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\Api\UserApiCustomerType::class, 'type_id');
    }

    public function priorityRef()
    {
        return $this->belongsTo(\App\Models\Api\UserApiCustomerPriority::class, 'priority_id');
    }
    //  type_id with name
    public function typeWithName()
    {
        return $this->belongsTo(\App\Models\Api\UserApiCustomerType::class, 'type_id')
            ->select('id', 'name');
    }

}
