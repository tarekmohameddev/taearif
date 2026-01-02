<?php

namespace Modules\WhatsappAI\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\WhatsappUser;
use App\Models\ApiCustomer;
use App\Models\Api\ApiCustomerInquiry;

class WhatsappConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'whatsapp_user_id',
        'customer_id',
        'customer_phone',
        'customer_name',
        'status',
        'last_message_at',
        'message_count',
        'is_real_estate_inquiry',
        'inquiry_type',
        'property_type',
        'budget_min',
        'budget_max',
        'currency',
        'bedrooms',
        'bathrooms',
        'city',
        'district',
        'urgency',
        'furnished',
        'ai_summary',
        'extracted_data',
        'inquiry_id',
        'processed_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'processed_at' => 'datetime',
        'is_real_estate_inquiry' => 'boolean',
        'furnished' => 'boolean',
        'extracted_data' => 'array',
        'message_count' => 'integer',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappUser()
    {
        return $this->belongsTo(WhatsappUser::class);
    }

    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class);
    }

    public function messages()
    {
        return $this->hasMany(WhatsappMessage::class, 'conversation_id');
    }

    public function inquiry()
    {
        return $this->belongsTo(ApiCustomerInquiry::class, 'inquiry_id');
    }

    /**
     * Scopes
     */
    public function scopeCollecting($query)
    {
        return $query->where('status', 'collecting');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeRealEstateOnly($query)
    {
        return $query->where('is_real_estate_inquiry', true);
    }

    public function scopeActive($query)
    {
        return $query->where('last_message_at', '>=', now()->subMinutes(config('whatsappai.session_timeout', 5)));
    }

    /**
     * Check if conversation is still active (within timeout window)
     */
    public function isActive(): bool
    {
        if (!$this->last_message_at) {
            return false;
        }

        $timeout = config('whatsappai.session_timeout', 5);
        return $this->last_message_at->diffInMinutes(now()) < $timeout;
    }

    /**
     * Mark conversation as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }
}

