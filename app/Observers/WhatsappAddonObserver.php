<?php

namespace App\Observers;

use App\Models\WhatsappAddon;
use App\Models\WhatsappAddonAudit;
use Illuminate\Support\Facades\Auth;

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
                'whatsapp_addon_id' => $whatsappAddon->id,
                'changed_by' => Auth::guard('admin')->id(), // Assuming admin guard for status changes
                'old_status' => $whatsappAddon->getOriginal('status'),
                'new_status' => $whatsappAddon->status,
                'changed_at' => now(),
            ]);
        }
    }
}
