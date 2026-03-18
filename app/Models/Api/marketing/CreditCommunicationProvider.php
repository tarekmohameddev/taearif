<?php

namespace App\Models\Api\marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class CreditCommunicationProvider extends Model
{
    protected $fillable = [
        'provider_type',
        'is_enabled',
        'name',
        'api_url',
        'api_key',
        'phone_number_id',
        'business_account_id',
        'access_token',
        'webhook_verify_token',
        'instance_name',
        'evolution_api_key',
        'sms_provider',
        'account_sid',
        'from_number',
        'config',
        'last_tested_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'config' => 'array',
        'last_tested_at' => 'datetime',
    ];

    // Hide sensitive fields from JSON
    protected $hidden = [
        'api_key',
        'access_token',
        'webhook_verify_token',
        'evolution_api_key',
    ];

    /**
     * Encrypt/decrypt api_key
     */
    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Encrypt/decrypt access_token
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Encrypt/decrypt webhook_verify_token
     */
    protected function webhookVerifyToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Encrypt/decrypt evolution_api_key
     */
    protected function evolutionApiKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? decrypt($value) : null,
            set: fn ($value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Scope: Only enabled providers
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->where('status', 'active');
    }

    /**
     * Scope: WhatsApp Meta Cloud providers
     */
    public function scopeWhatsAppMeta($query)
    {
        return $query->where('provider_type', 'whatsapp_meta');
    }

    /**
     * Scope: WhatsApp Evolution providers
     */
    public function scopeWhatsAppEvolution($query)
    {
        return $query->where('provider_type', 'whatsapp_evolution');
    }

    /**
     * Scope: SMS providers
     */
    public function scopeSms($query)
    {
        return $query->where('provider_type', 'sms');
    }

    /**
     * Check if provider is configured
     */
    public function isConfigured(): bool
    {
        return $this->status === 'configured' || $this->status === 'active';
    }

    /**
     * Get human-readable provider name
     */
    public function getProviderName(): string
    {
        return match($this->provider_type) {
            'whatsapp_meta' => 'WhatsApp Meta Cloud',
            'whatsapp_evolution' => 'WhatsApp Evolution API',
            'sms' => 'SMS Gateway',
            default => 'Unknown Provider',
        };
    }

    /**
     * Get masked API key for display
     */
    public function getMaskedApiKey(): string
    {
        $key = null;
        
        // Determine which key to mask based on provider type
        if ($this->provider_type === 'whatsapp_meta' && $this->access_token) {
            $key = $this->access_token;
        } elseif ($this->provider_type === 'whatsapp_evolution' && $this->evolution_api_key) {
            $key = $this->evolution_api_key;
        } elseif ($this->provider_type === 'sms' && $this->api_key) {
            $key = $this->api_key;
        }
        
        if (!$key || strlen($key) < 8) {
            return '••••••••';
        }
        
        return substr($key, 0, 4) . '••••••••' . substr($key, -4);
    }
}
