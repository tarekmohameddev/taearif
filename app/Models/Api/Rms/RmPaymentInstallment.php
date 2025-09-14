<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;

class RmPaymentInstallment extends Model
{
    protected $table = 'rm_payment_installments';

    protected $fillable = [
        'user_id', 'rental_id', 'contract_id', 'sequence_no',
        'due_date', 'amount', 'status', 'payment_type', 'payment_status',
        'paid_amount', 'paid_at', 'reference', 'notes'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function contract()
    {
        return $this->belongsTo(RmContract::class, 'contract_id');
    }

    public function payments()
    {
        return $this->hasMany(RmPayment::class, 'installment_id');
    }
}
