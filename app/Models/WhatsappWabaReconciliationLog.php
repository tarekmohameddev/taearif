<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappWabaReconciliationLog extends Model
{
    protected $fillable = [
        'whatsapp_user_id',
        'tenant_owner_id',
        'admin_id',
        'phone_id',
        'old_waba_id',
        'verified_waba_id',
        'result',
        'reason_code',
        'subscription_result',
    ];
}
