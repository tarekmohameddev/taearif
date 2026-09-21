<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TenantBrand\TenantBrandCache;
use App\Services\TenantBrand\TenantBrandPayloadBuilder;
use App\Services\TenantBrand\TenantBrandVariantGenerator;
use App\Services\TenantWebsite\TenantIdentifierNormalizer;
use Illuminate\Console\Command;

final class BackfillTenantBrandVariants extends Command
{
    protected $signature = 'tenant-brand:backfill
        {--tenant-id=* : Restrict to one or more tenant IDs}
        {--chunk=100 : Query chunk size}
        {--dry-run : Resolve and report without generating or warming}';

    protected $description = 'Generate tenant brand variants and warm canonical brand cache keys.';

    public function handle(
        TenantBrandPayloadBuilder $builder,
        TenantBrandVariantGenerator $generator,
        TenantBrandCache $cache,
        TenantIdentifierNormalizer $normalizer
    ): int {
        $query = User::query()->where('account_type', 'tenant')->orderBy('id');
        $ids = array_values(array_filter(array_map('intval', (array) $this->option('tenant-id'))));
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $counts = ['visited' => 0, 'no_source' => 0, 'ready' => 0, 'unmappable' => 0, 'warmed' => 0];
        $dryRun = (bool) $this->option('dry-run');
        $query->chunkById(max(1, (int) $this->option('chunk')), function ($tenants) use (
            $builder,
            $generator,
            $cache,
            $normalizer,
            $dryRun,
            &$counts
        ) {
            foreach ($tenants as $tenant) {
                $counts['visited']++;
                $sourceUrl = $builder->resolveSource($tenant);
                if ($sourceUrl === null) {
                    $counts['no_source']++;
                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $asset = $generator->generate($sourceUrl);
                if ($asset) {
                    $counts['ready']++;
                } else {
                    $counts['unmappable']++;
                }

                $identifier = $normalizer->normalize((string) $tenant->username);
                if ($identifier !== '') {
                    $cache->rebuild($identifier);
                    $counts['warmed']++;
                }
            }
        });

        $this->line(json_encode($counts, JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
