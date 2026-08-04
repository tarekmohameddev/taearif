<?php

namespace App\Services;

use App\Models\Api\ApiDomainSetting;
use App\Models\Api\FooterSetting;
use App\Models\Api\marketing\MarketingChannel;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Models\User\RealestateManagement\Property;
use App\Models\UserStep;
use App\Models\WhatsappUser;

class SiteSetupProgressService
{
    private const TOTAL_STEPS = 5;

    private const PLACEHOLDER_PHONES = [
        '+966 5XXXXXXXX',
        '+9665XXXXXXXX',
        '5XXXXXXXX',
    ];

    private const PLACEHOLDER_EMAILS = [
        'info@example.com',
    ];

    /**
     * Canonical FE setup-progress payload (5 steps).
     * Derives each status from tenant-owner data; employees share owner progress.
     *
     * @return array<string, mixed>|null Null when owner cannot be resolved (fail closed).
     */
    public function getProgress(User $user): ?array
    {
        $ownerId = $user->tenantOwnerId();
        if (! $ownerId) {
            return null;
        }

        $owner = User::find($ownerId);
        if (! $owner) {
            return null;
        }

        $statuses = [
            'site_identity' => (bool) $owner->onboarding_completed,
            'contact_info' => $this->isContactInfoComplete($owner),
            'first_property' => Property::where('user_id', $ownerId)->exists(),
            'integrated_link' => $this->hasIntegratedLink($ownerId),
            'connect_site' => ApiDomainSetting::where('user_id', $ownerId)
                ->where('status', 'active')
                ->exists(),
        ];

        $stepDefs = [
            [
                'id' => 'site_identity',
                'label_ar' => 'هوية الموقع',
                'order' => 1,
                'href_incomplete' => null,
            ],
            [
                'id' => 'contact_info',
                'label_ar' => 'بيانات التواصل',
                'order' => 2,
                'href_incomplete' => '/dashboard/my-account/personal',
            ],
            [
                'id' => 'first_property',
                'label_ar' => 'أول عقار',
                'order' => 3,
                'href_incomplete' => '/dashboard/properties/add',
            ],
            [
                'id' => 'integrated_link',
                'label_ar' => 'الرابط المتكامل',
                'order' => 4,
                'href_incomplete' => '/dashboard/apps',
            ],
            [
                'id' => 'connect_site',
                'label_ar' => 'ربط الموقع',
                'order' => 5,
                'href_incomplete' => '/dashboard/my-account/domains',
            ],
        ];

        $steps = [];
        $done = 0;

        foreach ($stepDefs as $def) {
            $complete = (bool) ($statuses[$def['id']] ?? false);
            if ($complete) {
                $done++;
            }

            $steps[] = [
                'id' => $def['id'],
                'label_ar' => $def['label_ar'],
                'status' => $complete,
                'href' => $complete ? null : $def['href_incomplete'],
                'order' => $def['order'],
                'locked' => false,
            ];
        }

        $progress = self::TOTAL_STEPS > 0
            ? round($done / self::TOTAL_STEPS, 4)
            : 0.0;

        return [
            'progress' => $progress,
            'done' => $done,
            'total' => self::TOTAL_STEPS,
            'headline_key' => $this->headlineKeyForDone($done),
            'dismissed' => false,
            'steps' => $steps,
        ];
    }

    /**
     * Map POST /steps/complete to owner user_steps flags, then return GET-shaped progress.
     *
     * @return array{ok: bool, progress: ?array, error?: string, status?: int}
     */
    public function completeStep(User $user, string $step): array
    {
        $ownerId = $user->tenantOwnerId();
        if (! $ownerId) {
            return ['ok' => false, 'progress' => null, 'error' => 'Unable to resolve tenant owner.', 'status' => 403];
        }

        $canonical = $step === 'properties' ? 'first_property' : $step;

        $noopSteps = ['site_identity', 'integrated_link', 'connect_site'];
        $flagMap = [
            'first_property' => 'properties',
            'contact_info' => 'contacts_social_info',
        ];

        if (in_array($canonical, $noopSteps, true)) {
            // no-op on user_steps; GET remains source of truth
        } elseif (isset($flagMap[$canonical])) {
            $column = $flagMap[$canonical];
            $steps = UserStep::firstOrCreate(['user_id' => $ownerId]);
            $steps->{$column} = true;
            $steps->save();
        } else {
            return [
                'ok' => false,
                'progress' => null,
                'error' => 'The selected step is invalid.',
                'status' => 422,
            ];
        }

        $progress = $this->getProgress($user);
        if ($progress === null) {
            return ['ok' => false, 'progress' => null, 'error' => 'Unable to resolve tenant owner.', 'status' => 403];
        }

        return ['ok' => true, 'progress' => $progress];
    }

    /**
     * Sync contacts_social_info on the owner row when contact_info derives true.
     */
    public function syncContactsSocialInfo(User $owner): void
    {
        $ownerId = (int) ($owner->id ?: 0);
        if (! $ownerId) {
            return;
        }

        if (! $this->isContactInfoComplete($owner)) {
            return;
        }

        $steps = UserStep::firstOrCreate(['user_id' => $ownerId]);
        if ($steps->contacts_social_info) {
            return;
        }

        $steps->contacts_social_info = true;
        $steps->save();
    }

    /**
     * Sync properties flag on the owner row after a property is created.
     * Early-returns if already true.
     */
    public function syncPropertiesFlag(User $user): void
    {
        $ownerId = $user->tenantOwnerId();
        if (! $ownerId) {
            return;
        }

        $steps = UserStep::firstOrCreate(['user_id' => $ownerId]);
        if ($steps->properties) {
            return;
        }

        $steps->properties = true;
        $steps->save();
    }

    public function isContactInfoComplete(User $owner): bool
    {
        $footer = FooterSetting::where('user_id', $owner->id)->first();
        $general = is_array($footer?->general) ? $footer->general : [];

        $phone = trim((string) ($general['phone'] ?? ''));
        if ($phone === '' || $this->isPlaceholderPhone($phone)) {
            return false;
        }

        $basic = BasicSetting::where('user_id', $owner->id)->first();
        $basicEmail = trim((string) ($basic?->email ?? ''));
        $footerEmail = trim((string) ($general['email'] ?? ''));

        $email = $basicEmail !== '' ? $basicEmail : $footerEmail;
        if ($email === '' || $this->isPlaceholderEmail($email)) {
            return false;
        }

        return true;
    }

    private function hasIntegratedLink(int $ownerId): bool
    {
        $whatsappActive = WhatsappUser::where('user_id', $ownerId)
            ->where('status', 'active')
            ->exists();

        if ($whatsappActive) {
            return true;
        }

        return MarketingChannel::where('user_id', $ownerId)
            ->where('type', MarketingChannel::TYPE_WHATSAPP)
            ->where('is_connected', true)
            ->exists();
    }

    private function headlineKeyForDone(int $done): string
    {
        return match (true) {
            $done <= 0 => 'start',
            $done === 1 => 'early',
            $done <= 3 => 'mid',
            $done === 4 => 'almost',
            default => 'done',
        };
    }

    private function isPlaceholderPhone(string $phone): bool
    {
        $normalized = preg_replace('/\s+/', ' ', trim($phone)) ?? '';
        $compact = preg_replace('/\s+/', '', $normalized) ?? '';

        foreach (self::PLACEHOLDER_PHONES as $placeholder) {
            $pNorm = preg_replace('/\s+/', ' ', $placeholder) ?? '';
            $pCompact = preg_replace('/\s+/', '', $placeholder) ?? '';
            if (strcasecmp($normalized, $pNorm) === 0 || strcasecmp($compact, $pCompact) === 0) {
                return true;
            }
        }

        return false;
    }

    private function isPlaceholderEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        return in_array($email, array_map('strtolower', self::PLACEHOLDER_EMAILS), true);
    }
}
