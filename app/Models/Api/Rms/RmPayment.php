<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RentalCostItem;

class RmPayment extends Model
{
    use SoftDeletes;

    protected $table = 'rm_payments';

    protected $fillable = [
        'user_id',
        'rental_id',
        'contract_id',
        'installment_id',
        'cost_item_id',
        'installment_sequence',
        'payment_type',
        'amount',
        'payment_date',
        'payment_method',
        'bank_name',
        'receipt_image_path',
        'transfer_to',
        'reference',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function contract()
    {
        return $this->belongsTo(RmContract::class, 'contract_id');
    }

    public function installment()
    {
        return $this->belongsTo(RmPaymentInstallment::class, 'installment_id');
    }

    public function costItem()
    {
        return $this->belongsTo(RentalCostItem::class, 'cost_item_id');
    }

    // Scopes
    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    public function scopeByRental($query, $rentalId)
    {
        return $query->where('rental_id', $rentalId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopeByCostItem($query, $costItemId)
    {
        return $query->where('cost_item_id', $costItemId);
    }

    public function scopeByInstallmentSequence($query, $sequence)
    {
        return $query->where('installment_sequence', $sequence);
    }

    public function scopeForCostItems($query)
    {
        return $query->where('payment_type', 'cost_item')
                     ->whereNotNull('cost_item_id');
    }

    // Helper methods
    public function getPaymentTypeLabelAttribute()
    {
        if ($this->payment_type === 'cost_item' && $this->costItem) {
            return $this->costItem->name;
        }

        return match($this->payment_type) {
            'rent' => 'Rent Payment',
            'platform_fee' => 'Platform Fee',
            'water_fee' => 'Water Fee',
            'office_fee' => 'Office Fee',
            'deposit' => 'Deposit',
            'cost_item' => 'Cost Item',
            default => ucfirst(str_replace('_', ' ', $this->payment_type))
        };
    }

    public function getPaymentMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
            'online_payment' => 'Online Payment',
            'check' => 'Check',
            'other' => 'Other',
            default => ucfirst(str_replace('_', ' ', $this->payment_method))
        };
    }
}
