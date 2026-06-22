<?php

namespace App\Services\Advertising;

use App\Jobs\ScrapeAdvertisingImport;
use App\Models\AdvertisingImport;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\Storage;

class AdvertisingImportService
{
    public function createFromUrl(int $userId, string $url, string $platform): AdvertisingImport
    {
        $import = AdvertisingImport::create([
            'user_id' => $userId,
            'source_url' => $url,
            'platform' => $platform,
            'status' => 'pending',
        ]);

        ScrapeAdvertisingImport::dispatch($import->id);

        return $import;
    }

    public function syncPriceFromRawData(Property $property, AdvertisingImport $import): void
    {
        $price = data_get($import->raw_data, 'price');
        if ($price !== null && is_numeric($price)) {
            $property->update(['price' => $price]);
            $import->update(['status' => 'imported', 'property_id' => $property->id]);
        }
    }

    public function storeImportedImages(array $imageUrls, int $importId): array
    {
        $paths = [];
        foreach ($imageUrls as $index => $url) {
            try {
                $contents = @file_get_contents($url);
                if ($contents === false) {
                    continue;
                }
                $ext = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION) ?: 'jpg';
                $path = "imported/{$importId}/image_{$index}.{$ext}";
                Storage::disk('public')->put($path, $contents);
                $paths[] = $path;
            } catch (\Throwable) {
                continue;
            }
        }

        return $paths;
    }
}
