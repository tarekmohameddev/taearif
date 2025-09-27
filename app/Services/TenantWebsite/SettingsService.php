<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantSetting;
use App\Models\User;

class SettingsService
{
    public function update(User $tenant, array $settings): TenantSetting
    {
        return TenantSetting::updateOrCreate(
            ['user_id' => $tenant->id],
            ['settings' => $settings]
        );
    }
}


