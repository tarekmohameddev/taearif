<?php

namespace App\Services\TenantBrand;

final class TenantBrandPlaceholderPolicy
{
    public function isPlaceholder(string $value): bool
    {
        $value = trim($value);
        $lower = strtolower($value);

        return $value === ''
            || $value === '/images/main/logo.png'
            || str_contains($lower, 'placehold')
            || str_contains($lower, 'via.placeholder')
            || str_contains($lower, '/api/placeholder/')
            || preg_match('/\b200\s*[x×]\s*55\b/iu', $value) === 1
            || preg_match('/200%20?[x×]%20?55/iu', $value) === 1;
    }
}
