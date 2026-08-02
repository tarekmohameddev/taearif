<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class AiCustomerProfile extends Model
{
    protected $table = 'ai_customer_profiles';

    protected $fillable = [
        'user_id',
        'phone',
        'name',
        'durable_facts',
        'first_contact_at',
        'last_contact_at',
        'conversation_count',
    ];

    protected $casts = [
        'durable_facts'    => 'array',
        'first_contact_at' => 'datetime',
        'last_contact_at'  => 'datetime',
    ];
}
