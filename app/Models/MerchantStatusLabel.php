<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantStatusLabel extends Model
{
    protected $table = 'merchant_status_labels';

    protected $fillable = [
        'user_id',
        'status_id',
        'name_ar',
        'name_en',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(PropertyRequestStatus::class, 'status_id');
    }
}
