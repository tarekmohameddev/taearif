<?php

namespace App\Models\CustomersHub;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmHubNote extends Model
{
    protected $table = 'crm_hub_notes';

    protected $fillable = [
        'employee_id',
        'note',
    ];

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
