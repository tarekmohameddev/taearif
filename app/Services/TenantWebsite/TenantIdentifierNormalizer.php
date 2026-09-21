<?php

namespace App\Services\TenantWebsite;

final class TenantIdentifierNormalizer
{
    public function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = rtrim($value, '/');
        $value = preg_replace('/:\d+$/', '', $value) ?? $value;
        $value = rtrim($value, '.');

        return preg_replace('/^www\./', '', $value) ?? $value;
    }
}
