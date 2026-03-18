<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\ApiCustomer;
use InvalidArgumentException;

class WaRecipientResolverService
{
    /**
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualPhones
     * @return array<int, array{phone:string, name:?string, customer_id:?int}>
     */
    public function resolve(int $userId, array $customerIds = [], array $manualPhones = []): array
    {
        $maxManual = (int) config('communication.whatsapp.max_manual_recipients');
        if (count($manualPhones) > $maxManual) {
            throw new InvalidArgumentException('Too many manual recipients.');
        }

        $resolved = [];
        $seen = [];

        $ids = collect($customerIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $customers = ApiCustomer::query()
                ->where('user_id', $userId)
                ->whereIn('id', $ids->all())
                ->get(['id', 'name', 'phone_number']);

            foreach ($customers as $customer) {
                $phone = $this->normalizePhone((string) ($customer->phone_number ?? ''));
                if ($phone === null || isset($seen[$phone])) {
                    continue;
                }
                $seen[$phone] = true;
                $resolved[] = [
                    'phone' => $phone,
                    'name' => $customer->name,
                    'customer_id' => (int) $customer->id,
                ];
            }
        }

        foreach ($manualPhones as $manualPhone) {
            $phone = $this->normalizePhone((string) $manualPhone);
            if ($phone === null || isset($seen[$phone])) {
                continue;
            }
            $seen[$phone] = true;
            $resolved[] = [
                'phone' => $phone,
                'name' => null,
                'customer_id' => null,
            ];
        }

        return $resolved;
    }

    public function normalizePhone(string $value): ?string
    {
        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+');
        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === null || strlen($digits) < 8 || strlen($digits) > 16) {
            return null;
        }

        return $hasPlus ? '+' . $digits : $digits;
    }
}
