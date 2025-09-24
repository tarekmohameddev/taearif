<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Support\Facades\Auth;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'user_id',
        'client_name',
        'client_email',
        'client_phone',
        'client_national_id',
        'property_id',
        'project_id',
        'priority',
        'budget_amount',
        'notes',
        'additional_notes',
        'assigned_to',
        'overall_status',
        'progress_percentage',
        'request_date',
        'expected_completion_date',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'progress_percentage' => 'integer',
        'request_date' => 'datetime',
        'expected_completion_date' => 'datetime',
    ];

    // Priority constants
    const PRIORITIES = [
        'منخفضة' => 'منخفضة',
        'متوسطة' => 'متوسطة',
        'عالية' => 'عالية',
        'عاجل' => 'عاجل',
    ];

    // Stage types constants
    const STAGE_TYPES = [
        'الحجز' => 'الحجز',
        'العقد' => 'العقد',
        'الإنجاز' => 'الإنجاز',
        'الاستلام' => 'الاستلام',
    ];

    // Boot method to auto-generate request number and apply tenant scoping
    protected static function boot()
    {
        parent::boot();

        // Global scope to filter by tenant
        static::addGlobalScope('tenant', function ($builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
                if ($tenantId) {
                    $builder->where('user_id', $tenantId);
                }
            }
        });

        static::creating(function ($model) {
            // Auto-set tenant ID if not set
            if (empty($model->user_id) && Auth::check()) {
                $user = Auth::user();
                $model->user_id = $user->isTenant() ? $user->id : $user->tenant_id;
            }
            
            if (empty($model->request_number)) {
                $model->request_number = static::generateRequestNumber();
            }
        });

        static::created(function ($model) {
            // Auto-create all 4 stages when a purchase request is created
            $model->createDefaultStages();
        });
    }

    /**
     * Generate unique request number in format PR-YYYY-XXX (per tenant)
     */
    public static function generateRequestNumber()
    {
        $year = date('Y');
        $prefix = "PR-{$year}-";
        
        // Get tenant ID for scoping
        $tenantId = null;
        if (Auth::check()) {
            $user = Auth::user();
            $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        }
        
        $query = static::withoutGlobalScope('tenant')
            ->where('request_number', 'LIKE', $prefix . '%');
            
        if ($tenantId) {
            $query->where('user_id', $tenantId);
        }
        
        $lastRequest = $query->orderBy('request_number', 'desc')->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Create default stages for the purchase request
     */
    public function createDefaultStages()
    {
        $stages = [
            ['stage_name' => 'الحجز', 'stage_order' => 1],
            ['stage_name' => 'العقد', 'stage_order' => 2],
            ['stage_name' => 'الإنجاز', 'stage_order' => 3],
            ['stage_name' => 'الاستلام', 'stage_order' => 4],
        ];
        
        foreach ($stages as $stage) {
            $this->stages()->create([
                'stage_name' => $stage['stage_name'],
                'stage_order' => $stage['stage_order'],
                'status' => 'الانتظار',
            ]);
        }
    }

    /**
     * Update overall progress based on completed stages
     */
    public function updateProgress()
    {
        $totalStages = $this->stages()->count();
        $completedStages = $this->stages()->where('status', 'مكتمل')->count();
        
        $progress = $totalStages > 0 ? round(($completedStages / $totalStages) * 100, 2) : 0.00;
        
        // Update overall status based on progress
        $overallStatus = 'pending';
        if ($progress > 0 && $progress < 100) {
            $overallStatus = 'in_progress';
        } elseif ($progress == 100) {
            $overallStatus = 'completed';
        }
        
        $this->update([
            'progress_percentage' => $progress,
            'overall_status' => $overallStatus
        ]);
        
        return $progress;
    }

    // Relationships
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stages()
    {
        return $this->hasMany(PurchaseRequestStage::class);
    }

    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope('tenant')->where('user_id', $tenantId);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByProgress($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('progress_percentage', '>=', $min);
        }
        if ($max !== null) {
            $query->where('progress_percentage', '<=', $max);
        }
        return $query;
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // Accessors
    public function getPriorityLabelAttribute()
    {
        return $this->priority;
    }

    public function getProgressStatusAttribute()
    {
        if ($this->progress_percentage == 0) return 'لم يبدأ';
        if ($this->progress_percentage == 100) return 'مكتمل';
        return 'قيد التنفيذ';
    }
}