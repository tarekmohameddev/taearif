<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappAddon extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'whatsapp_addons';

    protected $fillable = [
        'whatsapp_number_id',
        'plan_id',
        'qty',
        'amount',
        'status',
        'expire_date',
        'payment_ref',
        'gateway_transaction_id',
    ];

    protected $casts = [
        'qty' => 'integer',
        'amount' => 'decimal:2',
        'status' => 'string',
        'expire_date' => 'datetime',
    ];

    public function whatsappUser()
    {
        return $this->belongsTo(WhatsappUser::class, 'whatsapp_number_id');
    }

    public function plan()
    {
        return $this->belongsTo(WhatsappAddonPlan::class, 'plan_id');
    }

    public function audits()
    {
        return $this->hasMany(WhatsappAddonAudit::class);
    }

    public function latestAudit()
    {
        return $this->hasOne(WhatsappAddonAudit::class)->latestOfMany();
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }
}

