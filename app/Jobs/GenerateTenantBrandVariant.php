<?php

namespace App\Jobs;

use App\Services\TenantBrand\TenantBrandRefreshService;
use App\Services\TenantBrand\TenantBrandVariantGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class GenerateTenantBrandVariant
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public string $sourceUrl, public int $tenantId)
    {
    }

    public function handle(
        TenantBrandVariantGenerator $generator,
        TenantBrandRefreshService $refreshService
    ): void {
        if ($generator->generate($this->sourceUrl)) {
            $refreshService->refreshSafely($this->tenantId);
        }
    }
}
