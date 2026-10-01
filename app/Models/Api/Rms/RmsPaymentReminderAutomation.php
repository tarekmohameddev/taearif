<?php

namespace App\Models\Api\Rms;

use Illuminate\Database\Eloquent\Model;

class RmsPaymentReminderAutomation extends Model
{
    protected $table = 'rms_payment_reminder_automations';
    protected $fillable = ['user_id', 'enabled', 'channel_type', 'channel_id', 'channel_name', 'sender_id', 'sending_time', 'timezone', 'rules'];
    protected $casts = ['enabled' => 'boolean', 'rules' => 'array'];
}
