<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RmExpense extends Model
{
    use HasFactory;

    protected $table = 'rm_expenses';

    protected $fillable = [
        'user_id',
        'rental_id',
        'expense_name',
        'image_path',
        'amount_type',
        'amount_value',
        'cost_center',
        'is_active',
    ];

    protected $casts = [
        'amount_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rental that owns the expense.
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Api\Rms\RmRental::class);
    }

    /**
     * Check if the expense can be modified based on contract status.
     */
    public function canBeModified(): bool
    {
        // Check if the rental has an expired contract
        if ($this->rental && $this->rental->contracts) {
            $expiredContracts = $this->rental->contracts()
                ->where('status', 'expired')
                ->exists();
            
            if ($expiredContracts) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the full image URL.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }
}
