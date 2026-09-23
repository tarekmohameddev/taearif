<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class TwoYearSubscriptionPackageSeeder extends Seeder
{
    /**
     * Stable slug identifying the two-year fixed-duration package.
     */
    public const SLUG = 'premium-two-years';

    /**
     * Source package whose entitlements are cloned (Premium Annual Package).
     */
    public const SOURCE_PACKAGE_ID = 24;

    /**
     * Entitlement/display fields copied from the source package.
     */
    private const ENTITLEMENT_FIELDS = [
        'icon',
        'subtitle',
        'featured',
        'new_features',
        'features',
        'meta_keywords',
        'meta_description',
        'number_of_vcards',
        'project_limit_number',
        'real_estate_limit_number',
        'video_size_limit',
        'file_size_limit',
        'serial_number',
        'whatsapp_numbers_limit',
        'employees_limit',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $source = Package::find(self::SOURCE_PACKAGE_ID);

        if (!$source) {
            throw new \RuntimeException(
                'Unable to seed Two-Year package: source package ID ' . self::SOURCE_PACKAGE_ID
                . ' (Premium Annual) was not found. Configure the correct source package before seeding.'
            );
        }

        $entitlements = [];

        foreach (self::ENTITLEMENT_FIELDS as $field) {
            $entitlements[$field] = $source->{$field};
        }

        Package::updateOrCreate(
            ['slug' => self::SLUG],
            array_merge([
                'title' => 'الباقة المميزة لمدة سنتين',
                'title_en' => 'Premium Two-Year Plan',
                'slug' => self::SLUG,
                'price' => 996.00,
                'term' => 'yearly',
                'duration_months' => 24,
                'is_trial' => '0',
                'trial_days' => 0,
                'status' => '1',
                'is_active' => true,
            ], $entitlements)
        );

        Cache::forget('payment_active_packages');
    }
}