<?php

namespace App\Models\Api\Crm;

use App\Models\User;
use App\Models\ApiCustomer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CrmCard extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'crm_cards';

    protected $fillable = [
        'user_id',
        'card_customer_id',
		'card_request_id',
        'card_content',
        'card_procedure',
        'card_project',
        'card_property',
        'card_date',
        'reminder_sent_at',
    ];

    protected $casts = [
        'card_date' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    /**
     * Always scope queries by tenant (user_id).
     */
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    // --- Relations (optional stubs) ---
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function customer()
    {
        // Adjust target model/namespace to your actual api_customer model
        return $this->belongsTo(\App\Models\ApiCustomer::class, 'card_customer_id');
    }

    // Add these when/if you have models:
    // public function project() { return $this->belongsTo(\App\Models\Api\Project::class, 'card_project_id'); }
    // public function property() { return $this->belongsTo(\App\Models\User\RealestateManagement\Property::class, 'card_property_id'); }
}
