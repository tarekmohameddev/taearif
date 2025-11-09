<?php

namespace App\Domain\Crm\Models;

use App\Domain\Admin\Models\Admin;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * Lead Model
 * 
 * Represents a potential customer in the CRM pipeline
 */
class Lead extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\LeadFactory::new();
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'company',
        'source',
        'status',
        'stage_id',
        'assigned_admin_id',
        'converted_user_id',
        'converted_at',
        'notes',
        'custom_fields',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'converted_at' => 'datetime',
        'custom_fields' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    /**
     * Get the CRM stage/card this lead belongs to.
     */
    public function stage()
    {
        return $this->belongsTo(AdminCrmCard::class, 'stage_id');
    }

    /**
     * Get the admin assigned to this lead.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(Admin::class, 'assigned_admin_id');
    }

    /**
     * Get the user this lead was converted to.
     */
    public function convertedUser()
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }

    /**
     * Get the activities for this lead.
     */
    public function activities()
    {
        return $this->hasMany(LeadActivity::class, 'lead_id');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by source.
     */
    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope a query to filter by stage.
     */
    public function scopeByStage($query, $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    /**
     * Scope a query to filter by assigned admin.
     */
    public function scopeAssignedTo($query, $adminId)
    {
        return $query->where('assigned_admin_id', $adminId);
    }

    /**
     * Scope a query to only include converted leads.
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted')
                    ->whereNotNull('converted_user_id');
    }

    /**
     * Scope a query to only include active (not converted or lost) leads.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['new', 'contacted', 'qualified']);
    }

    /**
     * Scope a query to search leads by name, email, company, or phone.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }
}

