<?php

namespace App\Contracts\Vercel;

/**
 * Marks {@see \App\Models\Api\ApiDomainSetting} (`api_domains_settings`) as the
 * authoritative store for tenant API domains, `/admin/domains`, and Vercel
 * attachment/health state.
 *
 * The legacy {@see \App\Domain\Domain\Models\CustomDomain} table
 * (`user_custom_domains`) is used only by the legacy admin API. New Vercel-backed
 * flows must write to `api_domains_settings` and must not rely on the legacy table
 * alone. Reconciliation reads both tables before declaring Vercel orphans.
 */
interface VercelDomainSourceOfTruth
{
    public const TABLE = 'api_domains_settings';

    public const MODEL = \App\Models\Api\ApiDomainSetting::class;
}
