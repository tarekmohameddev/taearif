<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RmContract extends Model
{
    use SoftDeletes;

    protected $table = 'rm_contracts';

    protected $fillable = [
        'user_id', 'rental_id', 'contract_number',
        'start_date', 'end_date', 'status',
        'termination_reason', 'file_path', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }

    public function installments()
    {
        return $this->hasMany(RmPaymentInstallment::class, 'contract_id');
    }
}
