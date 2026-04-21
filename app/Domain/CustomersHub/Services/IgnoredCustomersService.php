<?php

declare(strict_types=1);

namespace App\Domain\CustomersHub\Services;

use App\Models\CustomersHub\IgnoredCustomer;
use App\Support\PhoneNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class IgnoredCustomersService
{
    /**
     * Check whether a phone/customer combination is on the tenant's ignore list.
     *
     * At least one of $phone or $customerId must be non-null. When both are
     * provided the check is a logical OR (either match triggers ignored=true).
     */
    public function isIgnored(int $tenantUserId, ?string $phone, ?int $customerId = null): bool
    {
        $normalizedPhone = $phone ? PhoneNormalizer::normalize($phone) : null;

        if ($normalizedPhone === null && $customerId === null) {
            return false;
        }

        $query = IgnoredCustomer::where('tenant_user_id', $tenantUserId);

        if ($normalizedPhone !== null && $customerId !== null) {
            $query->where(function ($q) use ($normalizedPhone, $customerId) {
                $q->where('phone_normalized', $normalizedPhone)
                  ->orWhere('customer_id', $customerId);
            });
        } elseif ($normalizedPhone !== null) {
            $query->where('phone_normalized', $normalizedPhone);
        } else {
            $query->where('customer_id', $customerId);
        }

        return $query->exists();
    }

    /**
     * Add a customer/phone to the ignore list.
     *
     * Returns the newly created or already-existing IgnoredCustomer record.
     * At least one of $phone or $customerId is required.
     *
     * @throws \InvalidArgumentException
     */
    public function add(
        int $tenantUserId,
        ?string $phone,
        ?int $customerId,
        ?string $reason,
        ?int $createdBy
    ): IgnoredCustomer {
        if ($phone === null && $customerId === null) {
            throw new \InvalidArgumentException('At least one of phone or customer_id is required to add to ignore list.');
        }

        $normalizedPhone = $phone ? PhoneNormalizer::normalize($phone) : null;

        // Attempt to find an existing record to update (upsert semantics per the unique indexes)
        // We search by phone first, then customer_id.
        $existing = null;
        if ($normalizedPhone !== null) {
            $existing = IgnoredCustomer::where('tenant_user_id', $tenantUserId)
                ->where('phone_normalized', $normalizedPhone)
                ->first();
        }
        if ($existing === null && $customerId !== null) {
            $existing = IgnoredCustomer::where('tenant_user_id', $tenantUserId)
                ->where('customer_id', $customerId)
                ->first();
        }

        if ($existing !== null) {
            // Update existing entry with any new data
            $existing->fill(array_filter([
                'phone_normalized' => $normalizedPhone ?? $existing->phone_normalized,
                'customer_id'      => $customerId ?? $existing->customer_id,
                'reason'           => $reason ?? $existing->reason,
                'created_by'       => $createdBy ?? $existing->created_by,
            ], fn ($v) => $v !== null));
            $existing->save();
            return $existing;
        }

        return IgnoredCustomer::create([
            'tenant_user_id'  => $tenantUserId,
            'phone_normalized' => $normalizedPhone,
            'customer_id'      => $customerId,
            'reason'           => $reason,
            'created_by'       => $createdBy,
        ]);
    }

    /**
     * Remove an entry from the ignore list by its primary key (scoped to the tenant).
     */
    public function remove(int $tenantUserId, int $id): bool
    {
        $deleted = IgnoredCustomer::where('id', $id)
            ->where('tenant_user_id', $tenantUserId)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Paginated list of ignored customers for a tenant.
     *
     * @param  array{q?: string, per_page?: int, page?: int}  $filters
     */
    public function list(int $tenantUserId, array $filters = []): LengthAwarePaginator
    {
        $query = IgnoredCustomer::where('tenant_user_id', $tenantUserId)
            ->orderByDesc('id');

        if (!empty($filters['q'])) {
            $term = '%' . $filters['q'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('phone_normalized', 'like', $term)
                  ->orWhereHas('customer', function ($cq) use ($term) {
                      $cq->where('name', 'like', $term)
                         ->orWhere('phone_number', 'like', $term);
                  });
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);

        return $query->with('customer:id,name,phone_number')->paginate($perPage);
    }
}
