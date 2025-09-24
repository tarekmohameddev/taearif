<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRequestStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_request_id',
        'stage_name',
        'stage_order',
        'status',
        'notes',
        'started_at',
        'completed_at',
        'updated_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Stage status constants
    const STATUSES = [
        'الانتظار' => 'الانتظار',
        'قيد التنفيذ' => 'قيد التنفيذ',
        'مكتمل' => 'مكتمل',
    ];

    // Stage types constants
    const STAGE_TYPES = [
        'الحجز' => 'الحجز',
        'العقد' => 'العقد',
        'الإنجاز' => 'الإنجاز',
        'الاستلام' => 'الاستلام',
    ];

    // Boot method to handle status changes
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($model) {
            // If status changed to completed, set completed_at
            if ($model->isDirty('status') && $model->status === 'مكتمل') {
                $model->update(['completed_at' => now()]);
            }
            
            // If status changed to in progress, set started_at
            if ($model->isDirty('status') && $model->status === 'قيد التنفيذ' && !$model->started_at) {
                $model->update(['started_at' => now()]);
            }

            // Update overall progress when stage status changes
            if ($model->isDirty('status')) {
                $model->purchaseRequest->updateProgress();
            }
        });
    }

    // Relationships
    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByStageName($query, $stageName)
    {
        return $query->where('stage_name', $stageName);
    }

    public function scopeByStageOrder($query, $order)
    {
        return $query->where('stage_order', $order);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'مكتمل');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'الانتظار');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'قيد التنفيذ');
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        return $this->status;
    }

    public function getStageNameLabelAttribute()
    {
        return $this->stage_name;
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'مكتمل';
    }

    public function getIsInProgressAttribute()
    {
        return $this->status === 'قيد التنفيذ';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'الانتظار';
    }

    // Methods
    public function markAsCompleted($notes = null, $userId = null)
    {
        $this->update([
            'status' => 'مكتمل',
            'completed_at' => now(),
            'notes' => $notes ?: $this->notes,
            'updated_by' => $userId,
        ]);

        return $this;
    }

    public function markAsInProgress($notes = null, $userId = null)
    {
        $this->update([
            'status' => 'قيد التنفيذ',
            'started_at' => $this->started_at ?: now(),
            'notes' => $notes ?: $this->notes,
            'updated_by' => $userId,
        ]);

        return $this;
    }

    public function markAsPending($notes = null, $userId = null)
    {
        $this->update([
            'status' => 'الانتظار',
            'completed_at' => null,
            'started_at' => null,
            'notes' => $notes ?: $this->notes,
            'updated_by' => $userId,
        ]);

        return $this;
    }
}