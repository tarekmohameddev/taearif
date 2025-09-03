<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RentalService
{
    public function listRentals($request)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

        return RmRental::with(['activeContract', 'property'])
            ->where('user_id', $ownerId)
            ->when($request->q, fn($q) => $q->where('tenant_full_name', 'like', "%{$request->q}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
            ->when($request->paying_plan, fn($q) => $q->where('paying_plan', $request->paying_plan))
            ->paginate($request->get('per_page', 15));
    }

    public function createRental($userId, array $data)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $data) {
            $rental = RmRental::create(array_merge($data, [
                'user_id' => $ownerId,
                'status' => 'draft',
            ]));

            $hasEnoughData = $data['move_in_date'] ?? null
                && $data['rental_period_months'] ?? null
                && $data['paying_plan'] ?? null
                && $data['base_rent_amount'] ?? null;

            if ($hasEnoughData) {
                $contract = RmContract::create([
                    'user_id' => $ownerId,
                    'rental_id' => $rental->id,
                    'contract_number' => 'CNT-' . now()->format('Y') . '-' . str_pad($rental->id, 5, '0', STR_PAD_LEFT),
                    'start_date' => $data['move_in_date'],
                    'end_date' => Carbon::parse($data['move_in_date'])->addMonths($data['rental_period_months'])->subDay(),
                    'status' => 'pending',
                ]);

                $this->generateInstallments($ownerId, $rental->id, $contract->id, $data);

                return [
                    'id' => $rental->id,
                    'status' => 'draft',
                    'contract' => [
                        'id' => $contract->id,
                        'status' => 'pending',
                    ]
                ];
            }

            return [
                'id' => $rental->id,
                'status' => 'draft'
            ];
        });
    }

    public function getRentalDetails($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['activeContract', 'upcomingInstallments', 'maintenanceOpen'])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return $rental;
    }

    public function updateRental($userId, $id, array $data, bool $regenerate = false)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $id, $data, $regenerate) {
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);
            $rental->update($data);

            if ($regenerate && $rental->activeContract) {
                RmPaymentInstallment::where('contract_id', $rental->activeContract->id)
                    ->where('status', 'pending')
                    ->delete();

                $this->generateInstallments(
                    $ownerId,
                    $rental->id,
                    $rental->activeContract->id,
                    $rental->toArray()
                );
            }

            return $rental->fresh();
        });
    }

    public function deleteRental($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);

        if ($rental->activeContract) {
            throw new \Exception('Cannot delete rental with active contract');
        }

        $rental->delete();
    }

    private function generateInstallments($userId, $rentalId, $contractId, array $data)
    {
        $plan = $data['paying_plan'];
        $amount = $data['base_rent_amount'];
        $start = Carbon::parse($data['move_in_date']);
        $months = (int) $data['rental_period_months'];

        $chunks = match($plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        $periods = ceil($months / $chunks);
        $installmentAmount = round($amount * $chunks, 2);

        for ($i = 0; $i < $periods; $i++) {
            RmPaymentInstallment::create([
                'user_id' => $userId,
                'rental_id' => $rentalId,
                'contract_id' => $contractId,
                'sequence_no' => $i + 1,
                'due_date' => $start->copy()->addMonths($i * $chunks),
                'amount' => $installmentAmount,
            ]);
        }
    }
}
