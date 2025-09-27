<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishService
{
    public function publish(User $tenant): array
    {
        return DB::transaction(function () use ($tenant) {
            $pages = TenantPage::where('user_id', $tenant->id)->get();
            foreach ($pages as $page) {
                $page->published_data = $page->components;
                $page->save();
            }

            $globals = TenantGlobalComponent::where('user_id', $tenant->id)->first();
            if ($globals) {
                $globals->published_data = $globals->data;
                $globals->save();
            }

            $settings = TenantSetting::firstOrCreate(
                ['user_id' => $tenant->id],
                ['settings' => []]
            );
            $settings->version = (string) ((int) $settings->version + 1);
            $settings->published_at = now();
            $settings->save();

            return [
                'version' => $settings->version,
                'publishedAt' => $settings->published_at->toISOString(),
            ];
        });
    }
}


