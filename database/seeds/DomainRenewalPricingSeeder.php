<?php

namespace Database\Seeders;

use App\Models\DomainRenewalPricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DomainRenewalPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds global default pricing rules from config.
     *
     * @return void
     */
    public function run()
    {
        $config = config('domain-renewal');
        $currency = $config['currency'] ?? 'SAR';
        $base = (float) ($config['base_yearly_price'] ?? 50.00);
        $periods = $config['periods'] ?? [];

        foreach ($periods as $periodKey => $def) {
            $years = (int) ($def['years'] ?? 1);
            $multiplier = (float) ($def['multiplier'] ?? $years * 1.0);
            $label = (string) ($def['label'] ?? "{$years} year(s)");
            $price = round($base * $multiplier, 2);

            // Check if global default already exists
            $existing = DomainRenewalPricing::whereNull('custom_domain_id')
                ->whereNull('registrar')
                ->where('period_key', $periodKey)
                ->first();

            if (!$existing) {
                DomainRenewalPricing::create([
                    'custom_domain_id' => null,
                    'registrar' => null,
                    'period_key' => $periodKey,
                    'label' => $label,
                    'years' => $years,
                    'price' => $price,
                    'currency' => $currency,
                    'active' => true,
                ]);
            }
        }

        // ---------------------------------------------------------------------
        // Optional examples: per-registrar and per-domain overrides
        // Adjust or remove these as needed for your environment.
        // ---------------------------------------------------------------------

        // Per-registrar override (GoDaddy, 1 year at 55 SAR)
        DomainRenewalPricing::updateOrCreate(
            [
                'custom_domain_id' => null,
                'registrar' => 'GoDaddy',
                'period_key' => '1_year',
            ],
            [
                'label' => 'سنة واحدة',
                'years' => 1,
                'price' => 55.00,
                'currency' => $currency,
                'active' => true,
            ]
        );

        // Per-registrar override (Namecheap, 2 years at 100 SAR)
        DomainRenewalPricing::updateOrCreate(
            [
                'custom_domain_id' => null,
                'registrar' => 'Namecheap',
                'period_key' => '2_years',
            ],
            [
                'label' => 'سنتان',
                'years' => 2,
                'price' => 100.00,
                'currency' => $currency,
                'active' => true,
            ]
        );

        // Per-domain override (only if domain exists to avoid FK errors)
        $exampleDomainId = 13;
        $domainExists = DB::table('user_custom_domains')->where('id', $exampleDomainId)->exists();
        if ($domainExists) {
            DomainRenewalPricing::updateOrCreate(
                [
                    'custom_domain_id' => $exampleDomainId,
                    'registrar' => null,
                    'period_key' => '1_year',
                ],
                [
                    'label' => 'سنة واحدة',
                    'years' => 1,
                    'price' => 50.00,
                    'currency' => $currency,
                    'active' => true,
                ]
            );
        }
    }
}
