<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalCostItem extends Model
{
    use HasFactory;

    protected $table = 'rental_cost_items';

    protected $fillable = [
        'user_id',
        'rental_id',
        'name',
        'cost',
        'type',
        'payer',
        'payment_frequency',
        'percentage_of',
        'description',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'percentage_of' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query)
    {
        return $query->where('payer', 'tenant');
    }

    public function scopeForOwner($query)
    {
        return $query->where('payer', 'owner');
    }

    public function scopeOneTime($query)
    {
        return $query->where('payment_frequency', 'one_time');
    }

    public function scopePerInstallment($query)
    {
        return $query->where('payment_frequency', 'per_installment');
    }

    // Helper methods
    public function getCalculatedAmount($baseAmount = null)
    {
        if ($this->type === 'fixed') {
            return $this->cost;
        }

        if ($this->type === 'percentage' && $baseAmount) {
            return ($baseAmount * $this->cost) / 100;
        }

        return 0;
    }
}
