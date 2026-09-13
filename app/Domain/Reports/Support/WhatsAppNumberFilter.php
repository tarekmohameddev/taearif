<?php

declare(strict_types=1);

namespace App\Domain\Reports\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WhatsAppNumberFilter
{
    /**
     * Resolve a phone number query param to wa_numbers.id.
     *
     * @return int|null null = no filter; 0 = number requested but not found
     */
    public static function resolveId(int $userId, ?string $number): ?int
    {
        if ($number === null || $number === '' || ! Schema::hasTable('wa_numbers')) {
            return $number === null || $number === '' ? null : 0;
        }

        $wanted = self::digits($number);
        if ($wanted === '') {
            return 0;
        }

        $rows = DB::table('wa_numbers')
            ->where('user_id', $userId)
            ->get(['id', 'phone_number']);

        foreach ($rows as $row) {
            if (self::digits((string) $row->phone_number) === $wanted) {
                return (int) $row->id;
            }
        }

        return 0;
    }

    public static function digits(string $number): string
    {
        return preg_replace('/\D+/', '', $number) ?? '';
    }
}
