<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantGlobalComponent;
use App\Models\User;

class GlobalService
{
    public function update(User $tenant, array $data): TenantGlobalComponent
    {
        return TenantGlobalComponent::updateOrCreate(
            ['user_id' => $tenant->id],
            ['data' => $data]
        );
    }
}


