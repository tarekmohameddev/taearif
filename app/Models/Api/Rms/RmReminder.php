<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;

class RmReminder extends Model
{
    protected $table = 'rm_reminders';

    protected $fillable = [
        'user_id', 'type', 'entity_type', 'entity_id', 'rental_id',
        'due_on', 'message', 'status', 'snooze_until'
    ];

    protected $casts = [
        'due_on' => 'date',
        'snooze_until' => 'date',
    ];

    public function rental()
    {
        return $this->belongsTo(RmRental::class, 'rental_id');
    }
}
