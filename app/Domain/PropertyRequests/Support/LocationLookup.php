<?php

declare(strict_types=1);

namespace App\Domain\PropertyRequests\Support;

use App\Models\User\UserCity;
use App\Models\User\UserDistrict;

class LocationLookup
{
    public function resolveCityIdByName(?string $name): ?int
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null) {
            return null;
        }

        $matches = UserCity::query()
            ->where(function ($q) use ($normalized) {
                $q->whereRaw('LOWER(name_ar) = ?', [$normalized])
                    ->orWhereRaw('LOWER(name_en) = ?', [$normalized]);
            })
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return count($matches) === 1 ? $matches[0] : null;
    }

    public function resolveDistrictIdByName(?string $name, ?int $cityId = null): ?int
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null) {
            return null;
        }

        $query = UserDistrict::query()
            ->where(function ($q) use ($normalized) {
                $q->whereRaw('LOWER(name_ar) = ?', [$normalized])
                    ->orWhereRaw('LOWER(name_en) = ?', [$normalized]);
            });

        if ($cityId !== null && $cityId > 0) {
            $query->where('city_id', $cityId);
        }

        $matches = $query->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        return count($matches) === 1 ? $matches[0] : null;
    }

    public function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        return mb_strtolower($trimmed);
    }
}
