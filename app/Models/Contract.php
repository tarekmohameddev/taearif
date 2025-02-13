<?php

namespace App\Models;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'subject',
        'contract_value',
        'contract_type',
        'start_date',
        'end_date',
        'description',
        'contract_status',
        'is_signed',
    ];

    /**
     * Get the customer that owns the contract.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): HasOne
    {
        return $this->hasOne(Sale::class, 'contract_id');
    }

}



