<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * CustomerAssignedPropertyService
 *
 * Handles assigning properties (listings) to customers via api_customer_assigned_property pivot.
 */
class CustomerAssignedPropertyService
{
    /**
     * Attach a property to a customer. Both must belong to the tenant user.
     *
     * @return array{customerId: int, propertyId: int, attachedAt: string}|false False if duplicate or validation fails
     */
    public function attach(int $userId, int $customerId, int $propertyId): array|false
    {
        $customerExists = DB::table('api_customers')
            ->where('id', $customerId)
            ->where('user_id', $userId)
            ->exists();

        if (!$customerExists) {
            return false;
        }

        $propertyExists = DB::table('user_properties')
            ->where('id', $propertyId)
            ->where('user_id', $userId)
            ->exists();

        if (!$propertyExists) {
            return false;
        }

        try {
            $now = Carbon::now();
            DB::table('api_customer_assigned_property')->insert([
                'customer_id' => $customerId,
                'property_id' => $propertyId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            return false;
        }

        return [
            'customerId' => $customerId,
            'propertyId' => $propertyId,
            'attachedAt' => $now->toIso8601String(),
        ];
    }

    /**
     * List properties assigned to a customer with optional pagination.
     *
     * @return array{properties: array, total: int, pagination?: array}
     */
    public function listForCustomer(int $userId, int $customerId, int $limit = 100, int $offset = 0): array
    {
        $customerExists = DB::table('api_customers')
            ->where('id', $customerId)
            ->where('user_id', $userId)
            ->exists();

        if (!$customerExists) {
            return ['properties' => [], 'total' => 0];
        }

        $baseQuery = DB::table('api_customer_assigned_property as acap')
            ->where('acap.customer_id', $customerId)
            ->join('user_properties as p', 'acap.property_id', '=', 'p.id')
            ->where('p.user_id', $userId)
            ->leftJoin(
                DB::raw('(SELECT property_id, MIN(id) AS content_id FROM user_property_contents GROUP BY property_id) AS first_pc'),
                'first_pc.property_id',
                '=',
                'p.id'
            )
            ->leftJoin('user_property_contents as pc', function ($join) {
                $join->on('pc.property_id', '=', 'p.id')
                    ->on('pc.id', '=', DB::raw('first_pc.content_id'));
            })
            ->select([
                'p.id',
                'pc.title',
                'pc.address',
                'p.price',
                'p.purpose',
                'p.type',
                'acap.created_at as attached_at',
            ]);

        $total = (clone $baseQuery)->count();

        $rows = (clone $baseQuery)
            ->orderBy('acap.created_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $properties = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'title' => $row->title,
                'address' => $row->address,
                'price' => $row->price,
                'purpose' => $row->purpose,
                'type' => $row->type,
                'attachedAt' => $row->attached_at ? Carbon::parse($row->attached_at)->toIso8601String() : null,
            ];
        })->values()->all();

        $result = [
            'customerId' => $customerId,
            'properties' => $properties,
            'total' => $total,
        ];

        if ($limit > 0 && $total > $limit) {
            $result['pagination'] = [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $total,
            ];
        }

        return $result;
    }

    /**
     * Detach a property from a customer. Returns true if a row was deleted.
     */
    public function detach(int $userId, int $customerId, int $propertyId): bool
    {
        $customerExists = DB::table('api_customers')
            ->where('id', $customerId)
            ->where('user_id', $userId)
            ->exists();

        if (!$customerExists) {
            return false;
        }

        $propertyExists = DB::table('user_properties')
            ->where('id', $propertyId)
            ->where('user_id', $userId)
            ->exists();

        if (!$propertyExists) {
            return false;
        }

        $deleted = DB::table('api_customer_assigned_property')
            ->where('customer_id', $customerId)
            ->where('property_id', $propertyId)
            ->delete();

        return $deleted > 0;
    }
}
