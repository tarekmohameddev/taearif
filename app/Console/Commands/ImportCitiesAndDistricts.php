<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Support\LocationLookupCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

/**
 * One-time (re-runnable) sync of Saudi cities and districts from nzl-backend.
 *
 * nzl ids are preserved verbatim on both tables: taearif records created before
 * this sync already reference them, so a generated id would orphan live data.
 * Locally created locations get ids from a reserved range instead -- see
 * database/migrations/2026_08_09_000001_prepare_user_locations_for_nzl_sync.php.
 */
class ImportCitiesAndDistricts extends Command
{
    protected $signature = 'import:cities-districts {--dry-run : Report what would change without writing}';
    protected $description = 'Import Saudi cities and districts from nzl-backend, preserving their ids';

    const CITIES_URL = 'https://nzl-backend.com/api/cities?country_id=1';
    const DISTRICTS_URL = 'https://nzl-backend.com/api/districts';
    const SAUDI_COUNTRY_ID = 1;

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN - no writes will be performed.');
        }

        $cities = $this->fetch(self::CITIES_URL, 120, 'cities');
        if ($cities === null) {
            return 1;
        }

        $districts = $this->fetch(self::DISTRICTS_URL, 180, 'districts');
        if ($districts === null) {
            return 1;
        }

        $districts = $this->saudiOnly($districts);

        $cityIds = $this->importCities($cities, $dryRun);
        $projectedCities = $this->importDistricts($districts, $dryRun);
        $this->backfillCitiesFromDistricts($projectedCities, $cityIds, $dryRun);

        if (! $dryRun) {
            LocationLookupCache::flush();
            $this->info('Cleared cached /api/cities and /api/districts payloads.');
        }

        $this->newLine();
        $this->info(sprintf(
            '%s -> cities: %d, districts: %d',
            $dryRun ? 'Totals before this dry run' : 'Totals',
            UserCity::count(),
            UserDistrict::count()
        ));

        return 0;
    }

    /**
     * @return array|null  null signals a failed request (caller aborts).
     */
    private function fetch(string $url, int $timeout, string $label)
    {
        $this->info("Fetching {$label}...");

        try {
            $response = Http::timeout($timeout)->get($url);
        } catch (\Throwable $e) {
            $this->error("Failed to fetch {$label}: " . $e->getMessage());

            return null;
        }

        if (! $response->successful()) {
            $this->error("Failed to fetch {$label}. HTTP status: " . $response->status());

            return null;
        }

        $rows = $response->json('data');

        if (! is_array($rows) || empty($rows)) {
            $this->error("Fetched {$label} but the payload was empty or malformed.");

            return null;
        }

        return $rows;
    }

    private function saudiOnly(array $districts): array
    {
        return array_values(array_filter($districts, function ($row) {
            $en = isset($row['country_name_en']) ? $row['country_name_en'] : '';
            $ar = isset($row['country_name_ar']) ? $row['country_name_ar'] : '';

            return $en === 'Saudi Arabia' || $ar === 'السعودية';
        }));
    }

    /**
     * @return array  ids of every city the table will hold once this step commits,
     *                as a lookup map. Returned rather than re-queried so --dry-run
     *                does not re-report cities this step would already have created.
     */
    private function importCities(array $cities, bool $dryRun): array
    {
        $existingIds = UserCity::pluck('id')->all();
        $existingIds = array_flip(array_map('intval', $existingIds));

        $toCreate = [];
        $toUpdate = [];

        foreach ($cities as $row) {
            if (isset($existingIds[(int) $row['id']])) {
                $toUpdate[] = $row;
            } else {
                $toCreate[] = $row;
            }
        }

        if (! $dryRun) {
            DB::transaction(function () use ($cities) {
                foreach ($cities as $row) {
                    UserCity::updateOrCreate(
                        ['id' => $row['id']],
                        [
                            'name_ar' => $row['name_ar'],
                            'name_en' => $row['name_en'],
                            'country_id' => $row['country_id'],
                            'region_id' => isset($row['region_id']) ? $row['region_id'] : null,
                            'latitude' => isset($row['latitude']) ? $row['latitude'] : null,
                            'longitude' => isset($row['longitude']) ? $row['longitude'] : null,
                        ]
                    );
                }
            });
        }

        $this->info(sprintf(
            'Cities: %d fetched, %d created, %d updated',
            count($cities),
            count($toCreate),
            count($toUpdate)
        ));

        foreach ($cities as $row) {
            $existingIds[(int) $row['id']] = true;
        }

        return $existingIds;
    }

    /**
     * Applies three rules per incoming district:
     *   (a) unknown id            -> create with the nzl id verbatim
     *   (b) known id, same city   -> refresh display names only
     *   (c) known id, other city  -> skip entirely and report
     *
     * (c) is not hypothetical: nzl reused id 11302270959 for a district in a
     * different city. Re-parenting an existing row would silently relocate every
     * property/request/customer pointing at it, so city_id is never rewritten.
     *
     * @return array  cityId => ['name_ar' => ..., 'name_en' => ...] for every city the
     *                districts table will reference once this run commits. Built in
     *                memory rather than re-queried so --dry-run projects accurately.
     */
    private function importDistricts(array $districts, bool $dryRun): array
    {
        $existing = UserDistrict::select('id', 'city_id', 'name_ar', 'name_en', 'city_name_ar', 'city_name_en')
            ->get()
            ->keyBy('id');

        $create = [];
        $update = [];
        $conflicts = [];
        $projectedCities = [];

        foreach ($districts as $row) {
            $id = (int) $row['id'];
            $incomingCityId = (int) $row['city_id'];
            $local = $existing->get($id);

            if ($local === null) {
                $create[] = $row;
                $projectedCities[$incomingCityId] = [
                    'name_ar' => $row['city_name_ar'],
                    'name_en' => $row['city_name_en'],
                ];
                continue;
            }

            if ((int) $local->city_id !== $incomingCityId) {
                $conflicts[] = [
                    $id,
                    $local->name_ar,
                    (int) $local->city_id,
                    $row['name_ar'],
                    $incomingCityId,
                ];
                continue;
            }

            if ($local->name_ar !== $row['name_ar'] || $local->name_en !== $row['name_en']) {
                $update[] = $row;
            }
        }

        // Rows already present keep their existing city, including skipped conflicts.
        // These overwrite payload-derived names so stored values win on disagreement.
        foreach ($existing as $local) {
            $projectedCities[(int) $local->city_id] = [
                'name_ar' => $local->city_name_ar,
                'name_en' => $local->city_name_en,
            ];
        }

        if (! $dryRun) {
            DB::transaction(function () use ($create, $update) {
                foreach (array_chunk($create, 500) as $chunk) {
                    $now = now();
                    $rows = [];
                    foreach ($chunk as $row) {
                        $rows[] = [
                            'id' => $row['id'],
                            'name_ar' => $row['name_ar'],
                            'name_en' => $row['name_en'],
                            'city_id' => $row['city_id'],
                            'city_name_ar' => $row['city_name_ar'],
                            'city_name_en' => $row['city_name_en'],
                            'country_name_ar' => $row['country_name_ar'],
                            'country_name_en' => $row['country_name_en'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    UserDistrict::insert($rows);
                }

                foreach ($update as $row) {
                    UserDistrict::where('id', $row['id'])->update([
                        'name_ar' => $row['name_ar'],
                        'name_en' => $row['name_en'],
                    ]);
                }
            });
        }

        $this->info(sprintf(
            'Districts: %d fetched (Saudi only), %d created, %d renamed, %d skipped',
            count($districts),
            count($create),
            count($update),
            count($conflicts)
        ));

        if (! empty($conflicts)) {
            $this->newLine();
            $this->warn('Skipped - id exists locally under a different city. Review manually; city_id is never rewritten:');
            $this->table(
                ['District ID', 'Local name', 'Local city', 'Incoming name', 'Incoming city'],
                $conflicts
            );
        }

        return $projectedCities;
    }

    /**
     * Some cities are reachable only through the districts payload (as the
     * denormalized city_name_* columns) and never appear in nzl's /cities list.
     * region_id stays null: the region code embedded in a district id agrees with
     * nzl's own region_id for just 37 of 397 known cities, so it is not derivable.
     */
    private function backfillCitiesFromDistricts(array $projectedCities, array $existingIds, bool $dryRun): void
    {
        $rows = [];
        $unnamed = [];

        foreach ($projectedCities as $cityId => $name) {
            if (isset($existingIds[$cityId])) {
                continue;
            }

            if (empty($name['name_ar'])) {
                $unnamed[] = $cityId;
                continue;
            }

            $rows[] = [
                'id' => $cityId,
                'name_ar' => $name['name_ar'],
                'name_en' => $name['name_en'],
                'country_id' => self::SAUDI_COUNTRY_ID,
                'region_id' => null,
                'latitude' => null,
                'longitude' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! $dryRun && ! empty($rows)) {
            DB::transaction(function () use ($rows) {
                foreach (array_chunk($rows, 500) as $chunk) {
                    UserCity::insert($chunk);
                }
            });
        }

        $this->info('Cities backfilled from districts: ' . count($rows));

        if (! empty($unnamed)) {
            $this->warn('Skipped backfill (no usable city name on any district row): ' . implode(', ', $unnamed));
        }
    }
}
