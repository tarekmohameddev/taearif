<?php

namespace App\Observers;

use App\Models\WhatsappAddon;
use App\Models\WhatsappAddonAudit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WhatsappAddonObserver
{
    /**
     * Handle the WhatsappAddon "updated" event.
     *
     * @param  \App\Models\WhatsappAddon  $whatsappAddon
     * @return void
     */
    public function updated(WhatsappAddon $whatsappAddon)
    {
        if ($whatsappAddon->isDirty('status')) {
            WhatsappAddonAudit::create([
                'tenant_id' => $whatsappAddon->user_id ?? optional($whatsappAddon->whatsappUser)->user_id,
                'whatsapp_addon_id' => $whatsappAddon->id,
                'entity_type' => 'addon',
                'action' => $whatsappAddon->status === WhatsappAddon::STATUS_APPROVED ? 'approve' : 'reject',
                'quantity' => $whatsappAddon->qty,
                'changed_by' => Auth::guard('admin')->id(), // Assuming admin guard for status changes
                'old_status' => $whatsappAddon->getOriginal('status'),
                'new_status' => $whatsappAddon->status,
                'note' => 'WhatsApp add-on status changed',
                'correlation_id' => (string) Str::uuid(),
                'ip_address' => request()?->ip(),
                'changed_at' => now(),
            ]);
        }
    }
}
