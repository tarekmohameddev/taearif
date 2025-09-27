<?php

namespace App\Models\Api\markting;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingChannel extends Model
{
    use HasFactory;

    protected $table = 'marketing_channels';

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'type',
        'number',
        'business_id',
        'phone_id',
        'access_token',
        'is_verified',
        'is_connected',
        'sent_messages_count',
        'received_messages_count',
        'additional_settings',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_connected' => 'boolean',
        'sent_messages_count' => 'integer',
        'received_messages_count' => 'integer',
        'additional_settings' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // Channel types constants
    const TYPE_WHATSAPP = 'whatsapp';
    const TYPE_FACEBOOK = 'facebook';
    const TYPE_TELEGRAM = 'telegram';
    const TYPE_INSTAGRAM = 'instagram';
    const TYPE_SMS = 'sms';

    public static function getChannelTypes()
    {
        return [
            self::TYPE_WHATSAPP => 'WhatsApp',
            self::TYPE_FACEBOOK => 'Facebook',
            self::TYPE_TELEGRAM => 'Telegram',
            self::TYPE_INSTAGRAM => 'Instagram',
            self::TYPE_SMS => 'SMS',
        ];
    }
}
