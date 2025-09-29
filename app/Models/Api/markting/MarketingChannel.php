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
        'crm_integration_enabled',
        'appointment_system_integration_enabled',
        'integration_settings',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_connected' => 'boolean',
        'sent_messages_count' => 'integer',
        'received_messages_count' => 'integer',
        'additional_settings' => 'array',
        'crm_integration_enabled' => 'boolean',
        'appointment_system_integration_enabled' => 'boolean',
        'integration_settings' => 'array',
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

    /**
     * Get system integration settings
     */
    public function getSystemIntegrationSettings()
    {
        return [
            'crm_integration_enabled' => $this->crm_integration_enabled,
            'appointment_system_integration_enabled' => $this->appointment_system_integration_enabled,
            'integration_settings' => $this->integration_settings ?? [],
        ];
    }

    /**
     * Update system integration settings
     */
    public function updateSystemIntegrationSettings($settings)
    {
        $allowedSettings = [
            'crm_integration_enabled',
            'appointment_system_integration_enabled',
            'integration_settings'
        ];

        $updateData = [];
        foreach ($allowedSettings as $setting) {
            if (array_key_exists($setting, $settings)) {
                $updateData[$setting] = $settings[$setting];
            }
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }

        return $this->fresh();
    }

    /**
     * Check if CRM integration is enabled
     */
    public function isCrmIntegrationEnabled()
    {
        return $this->crm_integration_enabled;
    }

    /**
     * Check if Appointment System integration is enabled
     */
    public function isAppointmentSystemIntegrationEnabled()
    {
        return $this->appointment_system_integration_enabled;
    }
}
