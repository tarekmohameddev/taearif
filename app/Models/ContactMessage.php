<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactMessage extends Model
{
    use SoftDeletes;

    public const SOURCES = [
        'contact_form_section',
        'contact_us_home_page',
        'contact_map_section',
        'hero4_contact_panel',
    ];

    public const STATUSES = [
        'active',
        'archived',
    ];

    protected $table = 'contact_messages';

    protected $fillable = [
        'tenant_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'message',
        'source',
        'is_read',
        'read_at',
        'status',
        'customer_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function customer()
    {
        return $this->belongsTo(ApiCustomer::class, 'customer_id');
    }
}
