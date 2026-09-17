<?php

namespace App\Models;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappAddonAudit extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_addons_audit';

    protected $fillable = [
        'tenant_id',
        'whatsapp_addon_id',
        'whatsapp_number_id',
        'entity_type',
        'action',
        'quantity',
        'old_quota',
        'new_quota',
        'changed_by',
        'old_status',
        'new_status',
        'note',
        'correlation_id',
        'ip_address',
        'metadata',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'old_status' => 'string',
        'new_status' => 'string',
        'entity_type' => 'string',
        'quantity' => 'integer',
        'old_quota' => 'integer',
        'new_quota' => 'integer',
        'metadata' => 'array',
    ];

    public function addon()
    {
        return $this->belongsTo(WhatsappAddon::class, 'whatsapp_addon_id');
    }

    public function whatsappNumber()
    {
        return $this->belongsTo(WhatsappUser::class, 'whatsapp_number_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'changed_by');
    }
}
