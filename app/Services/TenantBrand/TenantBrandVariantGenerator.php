<?php

namespace App\Services\TenantBrand;

use App\Models\TenantBrandSourceAsset;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Throwable;

final class TenantBrandVariantGenerator
{
    public function __construct(private TenantBrandSourceLocator $locator)
    {
    }

    public function generate(string $sourceUrl): ?TenantBrandSourceAsset
    {
        $urlHash = hash('sha256', $sourceUrl);

        return Cache::store('redis')->lock('brand-variant-lock:v1:' . $urlHash, 60)->block(5, function () use ($sourceUrl, $urlHash) {
            $source = $this->locator->locate($sourceUrl);
            if ($source === null) {
                TenantBrandSourceAsset::updateOrCreate(
                    ['source_url_hash' => $urlHash],
                    [
                        'source_url' => $sourceUrl,
                        'status' => 'failed',
                        'last_error_code' => 'source_unmappable',
                        'last_verified_at' => now(),
                    ]
                );

                return null;
            }

            $sourceHash = hash_file('sha256', $source['path']);
            $existing = TenantBrandSourceAsset::where('source_url_hash', $urlHash)->first();
            $expectedVariantUrl = $existing?->variant_path
                ? rtrim((string) config('tenant_brand.variant_base_url'), '/') . '/' . basename($existing->variant_path)
                : null;
            if ($existing?->status === 'ready'
                && hash_equals((string) $existing->source_content_hash, $sourceHash)
                && $existing->variant_path
                && Storage::disk('public')->exists($existing->variant_path)) {
                $existing->forceFill([
                    'variant_url' => $expectedVariantUrl,
                    'last_referenced_at' => now(),
                    'last_verified_at' => now(),
                ])->save();

                return $existing;
            }

            try {
                $manager = new ImageManager(['driver' => 'gd']);
                $image = $manager->make($source['path']);
                if ($image->height() > (int) config('tenant_brand.variant_height', 160)
                    || $image->width() > (int) config('tenant_brand.variant_max_width', 800)) {
                    $image->resize(
                        (int) config('tenant_brand.variant_max_width', 800),
                        (int) config('tenant_brand.variant_height', 160),
                        static function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        }
                    );
                }

                $bytes = (string) $image->encode('webp', (int) config('tenant_brand.webp_quality', 82));
                $variantHash = hash('sha256', $bytes);
                $variantPath = 'tenant-brand/' . $variantHash . '.webp';
                if (! Storage::disk('public')->exists($variantPath)) {
                    Storage::disk('public')->put($variantPath, $bytes, ['visibility' => 'public']);
                }

                $stored = Storage::disk('public')->get($variantPath);
                if (! hash_equals($variantHash, hash('sha256', $stored))) {
                    throw new \RuntimeException('Variant content hash verification failed.');
                }

                $size = strlen($stored);
                $inline = $size <= (int) config('tenant_brand.inline_max_bytes', 8192)
                    ? 'data:image/webp;base64,' . base64_encode($stored)
                    : null;

                return TenantBrandSourceAsset::updateOrCreate(
                    ['source_url_hash' => $urlHash],
                    [
                        'source_url' => $sourceUrl,
                        'source_content_hash' => $sourceHash,
                        'active_asset_content_hash' => $variantHash,
                        'variant_disk' => 'public',
                        'variant_path' => $variantPath,
                        'variant_url' => rtrim((string) config('tenant_brand.variant_base_url'), '/') . '/' . basename($variantPath),
                        'variant_mime' => 'image/webp',
                        'variant_size' => $size,
                        'inline_data' => $inline,
                        'status' => 'ready',
                        'last_verified_at' => now(),
                        'last_referenced_at' => now(),
                        'last_error_code' => null,
                    ]
                );
            } catch (Throwable $exception) {
                TenantBrandSourceAsset::updateOrCreate(
                    ['source_url_hash' => $urlHash],
                    [
                        'source_url' => $sourceUrl,
                        'source_content_hash' => $sourceHash,
                        'status' => 'failed',
                        'last_error_code' => substr(class_basename($exception), 0, 255),
                        'last_verified_at' => now(),
                    ]
                );

                report($exception);

                return null;
            }
        });
    }
}
