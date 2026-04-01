<?php

namespace App\Services;

use App\Domain\Domain\Models\CustomDomain;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\User;
use App\Models\UserStep;

class SiteSetupProgressService
{
    /**
     * Site setup checklist for the dashboard widget (5 steps).
     * Uses tenant owner id so employees see the same progress as the account owner.
     */
    public function getProgress(User $user): array
    {
        $ownerId = $user->tenantOwnerId();

        $steps = UserStep::firstOrCreate(['user_id' => $ownerId]);
        $hasDomain = CustomDomain::where('user_id', $ownerId)->exists();
        $hasWhatsApp = MarketingChannel::where('user_id', $ownerId)
            ->where('type', MarketingChannel::TYPE_WHATSAPP)
            ->where('is_connected', true)
            ->exists();

        $stepDefs = [
            [
                'key' => 'site_identity',
                'label' => 'هوية الموقع',
                'completed' => (bool) ($steps->logo_uploaded && $steps->website_named),
                'path' => '/basic-settings',
            ],
            [
                'key' => 'contact_data',
                'label' => 'بيانات التواصل',
                'completed' => (bool) $steps->contacts_social_info,
                'path' => '/contact-info',
            ],
            [
                'key' => 'first_property',
                'label' => 'أول عقار',
                'completed' => (bool) $steps->properties,
                'path' => '/properties/add',
            ],
            [
                'key' => 'domain_link',
                'label' => 'ربط الموقع',
                'completed' => $hasDomain,
                'path' => '/domain-settings',
            ],
            [
                'key' => 'integration',
                'label' => 'الرابط المتكامل',
                'completed' => $hasWhatsApp,
                'path' => '/whatsapp',
            ],
        ];

        $completedCount = collect($stepDefs)->filter(fn ($s) => $s['completed'])->count();
        $total = count($stepDefs);

        return [
            'steps' => $stepDefs,
            'completed_count' => $completedCount,
            'total_steps' => $total,
            'progress_percentage' => $total > 0 ? (int) (($completedCount / $total) * 100) : 0,
        ];
    }
}
