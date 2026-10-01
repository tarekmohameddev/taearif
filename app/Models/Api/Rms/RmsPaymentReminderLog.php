<?php

namespace App\Models\Api\Rms;

use App\Models\Message;
use Illuminate\Database\Eloquent\Model;

class RmsPaymentReminderLog extends Model
{
    protected $table = 'rms_payment_reminder_logs';
    protected $guarded = [];
    protected $casts = [
        'scheduled_at' => 'datetime', 'attempted_at' => 'datetime', 'sent_at' => 'datetime',
        'local_day' => 'date', 'outstanding_balance' => 'decimal:2',
    ];

    public function communicationMessage()
    {
        return $this->belongsTo(Message::class, 'communication_message_id');
    }
}
