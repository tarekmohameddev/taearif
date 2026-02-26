<?php

namespace App\Domain\Communication\Email\Services;

use App\Models\ApiCustomer;
use InvalidArgumentException;

class EmailRecipientResolverService
{
    /**
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualEmails
     * @return array<int, array{email:string, name:?string, customer_id:?int}>
     */
    public function resolve(int $userId, array $customerIds = [], array $manualEmails = []): array
    {
        $maxManual = (int) config('communication.email.max_manual_recipients', 5000);
        if (count($manualEmails) > $maxManual) {
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
                ->get(['id', 'name', 'email']);

            foreach ($customers as $customer) {
                $email = $this->normalizeEmail((string) ($customer->email ?? ''));
                if ($email === null || isset($seen[$email])) {
                    continue;
                }
                $seen[$email] = true;
                $resolved[] = [
                    'email' => $email,
                    'name' => $customer->name,
                    'customer_id' => (int) $customer->id,
                ];
            }
        }

        foreach ($manualEmails as $manualEmail) {
            $email = $this->normalizeEmail((string) $manualEmail);
            if ($email === null || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $resolved[] = [
                'email' => $email,
                'name' => null,
                'customer_id' => null,
            ];
        }

        return $resolved;
    }

    public function normalizeEmail(string $value): ?string
    {
        $email = trim(strtolower($value));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return $email;
    }
}

