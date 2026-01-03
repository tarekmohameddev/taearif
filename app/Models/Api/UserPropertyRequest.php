<?php

namespace App\Models\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPropertyRequest extends Model
{
    use HasFactory;

    protected $table = 'users_property_requests';
// wants_similar_offers purchase_method region
    protected $fillable = [
        'user_id',
        'region', // nullable
        'property_type',
        'category_id',
        'city_id',
        'districts_id',
        'area_from',
        'area_to',
        'purchase_method', // nullable
        'budget_from',
        'budget_to',
        'seriousness',
        'purchase_goal',
        'wants_similar_offers', // nullable
        'full_name',
        'phone',
        'contact_on_whatsapp',
        'notes',
        'is_read',
        'is_active',
        'status_id',
    ];

    protected $casts = [
        'wants_similar_offers' => 'boolean',
        'contact_on_whatsapp'  => 'boolean',
        'budget_from'          => 'float',
        'budget_to'            => 'float',
        'area_from'            => 'integer',
        'area_to'              => 'integer',
        'status_id'            => 'integer',
    ];

    protected $hidden = [
        'statusOption',
        'status_id',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusOption()
    {
        return $this->belongsTo(\App\Models\PropertyRequestStatus::class, 'status_id');
    }

    public function toArray(): array
    {
        $array = parent::toArray();

        $array['status'] = $this->statusOption
            ? [
                'id' => $this->statusOption->id,
                'name_ar' => $this->statusOption->name_ar,
                'name_en' => $this->statusOption->name_en,
            ]
            : null;

        return $array;
    }

    /**
     * Scope a query to filter by status_id.
     */
    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function getStatusNameAttribute(): ?string
    {
        if ($this->relationLoaded('statusOption') && $this->statusOption) {
            return $this->statusOption->name_ar ?? $this->statusOption->name_en;
        }

        if ($this->statusOption) {
            return $this->statusOption->name_ar ?? $this->statusOption->name_en;
        }

        return null;
    }
}
