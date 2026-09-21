<?php

namespace App\Services\TenantWebsite;

final class PublicLogoUrl
{
    public function transform(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        if (! str_contains($value, '/')) {
            return asset('assets/front/img/user/' . $value);
        }

        return asset($value);
    }
}
