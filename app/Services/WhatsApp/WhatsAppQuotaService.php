<?php

namespace App\Services\WhatsApp;

use App\Models\EmployeeAddon;
use App\Models\Membership;
use App\Models\User;
use App\Models\WhatsappAddon;

class WhatsAppQuotaService
{
    public function breakdown(User $owner): array
    {
        $membership = Membership::with('package')
            ->where('user_id', $owner->id)
            ->where('status', 1)
            ->whereDate('expire_date', '>=', now())
            ->latest()
            ->first();

        $usage = (int) $owner->whatsappUsers()->where('status', 'active')->count();
        if (!$membership) {
            return [
                'base' => 0,
                'whatsapp_addons' => 0,
                'employee_addons' => 0,
                'quota' => 0,
                'usage' => $usage,
                'remaining' => 0,
                'is_over_limit' => $usage > 0,
            ];
        }

        $base = (int) optional($membership->package)->whatsapp_numbers_limit;
        $whatsappAddons = (int) WhatsappAddon::query()
            ->where(function ($query) use ($owner) {
                $query->where('user_id', $owner->id)
                    ->orWhere(function ($legacy) use ($owner) {
                        $legacy->whereNull('user_id')
                            ->whereHas('whatsappUser', fn ($numbers) => $numbers->where('user_id', $owner->id));
                    });
            })
            ->where('status', WhatsappAddon::STATUS_APPROVED)
            ->where(fn ($query) => $query->whereNull('expire_date')->orWhere('expire_date', '>=', now()))
            ->sum('qty');
        $employeeAddons = (int) EmployeeAddon::activeFor($owner->id)->sum('qty');
        $quota = $base + $whatsappAddons + $employeeAddons;

        return [
            'base' => $base,
            'whatsapp_addons' => $whatsappAddons,
            'employee_addons' => $employeeAddons,
            'quota' => $quota,
            'usage' => $usage,
            'remaining' => max(0, $quota - $usage),
            'is_over_limit' => $usage > $quota,
        ];
    }

    public function quota(User $owner): int
    {
        return $this->breakdown($owner)['quota'];
    }
}
