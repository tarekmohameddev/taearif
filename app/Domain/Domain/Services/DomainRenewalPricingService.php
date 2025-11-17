<?php

namespace App\Domain\Domain\Services;

use App\Domain\Domain\Models\CustomDomain;
use App\Models\DomainRenewalPricing;

class DomainRenewalPricingService
{
    /**
     * Build pricing options for a given domain with precedence:
     * 1. Per-domain pricing (custom_domain_id)
     * 2. Per-registrar pricing (registrar)
     * 3. Config fallback (global defaults)
     *
     * @return array{currency:string,options:array<int,array{period:string,label:string,years:int,price:float,currency:string,source?:string}>}
     */
    public function getOptionsForDomain(CustomDomain $domain): array
    {
        $domainId = $domain->id;
        $registrar = $domain->apiDomainSetting?->registrar;
        $config = config('domain-renewal');
        $defaultCurrency = $config['currency'] ?? 'SAR';
        $periods = $config['periods'] ?? [];

        $options = [];
        foreach ($periods as $periodKey => $def) {
            $pricing = $this->resolvePricingForPeriod($domainId, $registrar, $periodKey, $def, $defaultCurrency);
            
            $options[] = [
                'period' => $periodKey,
                'label' => $pricing['label'],
                'years' => $pricing['years'],
                'price' => $pricing['price'],
                'currency' => $pricing['currency'],
                'source' => $pricing['source'] ?? 'config',
            ];
        }

        return [
            'currency' => $options[0]['currency'] ?? $defaultCurrency,
            'options' => $options,
        ];
    }

    /**
     * Resolve pricing for a specific period with precedence.
     *
     * @param  int  $domainId
     * @param  string|null  $registrar
     * @param  string  $periodKey
     * @param  array  $configDef
     * @param  string  $defaultCurrency
     * @return array{label:string,years:int,price:float,currency:string,source:string}
     */
    protected function resolvePricingForPeriod(
        int $domainId,
        ?string $registrar,
        string $periodKey,
        array $configDef,
        string $defaultCurrency
    ): array {
        // 1. Try per-domain pricing
        $pricing = DomainRenewalPricing::active()
            ->forDomain($domainId)
            ->forPeriod($periodKey)
            ->validDateRange()
            ->first();

        if ($pricing) {
            return [
                'label' => $pricing->label,
                'years' => $pricing->years,
                'price' => (float) $pricing->price,
                'currency' => $pricing->currency ?? $defaultCurrency,
                'source' => 'domain',
            ];
        }

        // 2. Try per-registrar pricing
        if ($registrar) {
            $pricing = DomainRenewalPricing::active()
                ->forRegistrar($registrar)
                ->forPeriod($periodKey)
                ->validDateRange()
                ->first();

            if ($pricing) {
                return [
                    'label' => $pricing->label,
                    'years' => $pricing->years,
                    'price' => (float) $pricing->price,
                    'currency' => $pricing->currency ?? $defaultCurrency,
                    'source' => 'registrar',
                ];
            }
        }

        // 3. Fallback to config
        $base = (float) ($configDef['base_yearly_price'] ?? config('domain-renewal.base_yearly_price') ?? 50.00);
        $years = (int) ($configDef['years'] ?? 1);
        $multiplier = (float) ($configDef['multiplier'] ?? $years * 1.0);
        $label = (string) ($configDef['label'] ?? "{$years} year(s)");
        $price = round($base * $multiplier, 2);

        return [
            'label' => $label,
            'years' => $years,
            'price' => $price,
            'currency' => $defaultCurrency,
            'source' => 'config',
        ];
    }

    /**
     * Resolve a single option by key for a domain.
     *
     * @param  CustomDomain  $domain
     * @param  string  $period
     * @return array{period:string,label:string,years:int,price:float,currency:string,source?:string}|null
     */
    public function resolveOption(CustomDomain $domain, string $period): ?array
    {
        $domainId = $domain->id;
        $registrar = $domain->apiDomainSetting?->registrar;
        $config = config('domain-renewal');
        $periods = $config['periods'] ?? [];

        if (!isset($periods[$period])) {
            return null;
        }

        $def = $periods[$period];
        $pricing = $this->resolvePricingForPeriod(
            $domainId,
            $registrar,
            $period,
            $def,
            $config['currency'] ?? 'SAR'
        );

        return [
            'period' => $period,
            'label' => $pricing['label'],
            'years' => $pricing['years'],
            'price' => $pricing['price'],
            'currency' => $pricing['currency'],
            'source' => $pricing['source'],
        ];
    }
}


