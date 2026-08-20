<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmPaymentInstallment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ContractService
{
    public function createContract($rentalId, array $data, $userId)
    {
        $rental = RmRental::where('id', $rentalId)->where('user_id', $userId)->firstOrFail();

        // Check if rental already has an active or pending contract
        $hasActiveContract = RmContract::where('rental_id', $rentalId)
            ->where('user_id', $userId)
            ->whereIn('status', ['active', 'pending'])
            ->exists();
        
        if ($hasActiveContract) {
            throw new \Exception('This rental already has an active or pending contract. Please end the existing contract before creating a new one.');
        }

        $this->validateNoOverlap($rentalId, $data['start_date'], $data['end_date'], $userId);

        return DB::transaction(function () use ($data, $userId, $rental) {
            $contract = RmContract::create([
                'user_id' => $userId,
                'rental_id' => $rental->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
                'file_path' => $data['file_path'] ?? null,
                'property_id' => $data['property_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'property_name' => $data['property_name'] ?? null,
                'project_name' => $data['project_name'] ?? null,
                'grace_period_months' => $data['grace_period_months'] ?? 0,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($data['status'] === 'active') {
                $rental->status = 'active';
                $rental->save();
                
                // Update property status when contract becomes active
                if ($rental->property_id) {
                    $property = \App\Models\User\RealestateManagement\Property::where('id', $rental->property_id)
                        ->where('user_id', $userId)
                        ->first();
                    if ($property) {
                        $property->updatePropertyStatus();
                    }
                }
            }

            if (!empty($data['generate_schedule'])) {
                app(InstallmentService::class)->generateSchedule($contract);
            }

            return $contract;
        });
    }

    public function updateContract($contractId, array $data, $userId)
    {
        $contract = RmContract::where('id', $contractId)->where('user_id', $userId)->firstOrFail();

        if (isset($data['start_date']) && isset($data['end_date'])) {
            $this->validateNoOverlap($contract->rental_id, $data['start_date'], $data['end_date'], $userId, $contract->id);
        }

        $data['updated_by'] = $userId;
        $contract->update($data);

        return $contract;
    }

    public function terminateContract($contractId, array $data, $userId)
    {
        $contract = RmContract::where('id', $contractId)
            ->where('user_id', $userId)
            ->where('status', '!=', 'terminated')
            ->firstOrFail();

        return DB::transaction(function () use ($contract, $data) {
            $contract->update([
                'status' => 'terminated',
                'termination_reason' => $data['termination_reason'],
                'end_date' => $data['terminate_on']
            ]);

            RmPaymentInstallment::where('contract_id', $contract->id)
                ->where('status', 'pending')
                ->update(['status' => 'void']);

            $rental = $contract->rental;
            if ($rental) {
                $rental->update(['status' => 'inactive']);
                if ($rental->unit_id) {
                    $property = \App\Models\User\RealestateManagement\Property::find($rental->unit_id);
                    $property?->updatePropertyStatus();
                }
            }

            return $contract;
        });
    }

    public function changeContractStatus($contractId, array $data, $userId)
    {
        $contract = RmContract::where('id', $contractId)->where('user_id', $userId)->firstOrFail();

        return DB::transaction(function () use ($contract, $data, $userId) {
            $oldStatus = $contract->status;
            $newStatus = $data['status'];

            // Prepare update data
            $updateData = [
                'status' => $newStatus,
                'updated_by' => $userId
            ];

            // Add reason if provided
            if (isset($data['reason'])) {
                $updateData['termination_reason'] = $data['reason'];
            }

            // Add effective date if provided
            if (isset($data['effective_date'])) {
                if ($newStatus === 'terminated') {
                    $updateData['end_date'] = $data['effective_date'];
                }
            }

            // Update contract
            $contract->update($updateData);

            // Handle status-specific logic
            $this->handleStatusChange($contract, $oldStatus, $newStatus);

            return $contract->fresh();
        });
    }


    protected function handleStatusChange($contract, $oldStatus, $newStatus)
    {
        switch ($newStatus) {
            case 'active':
                // If activating a contract, deactivate other active contracts for the same rental
                if ($oldStatus !== 'active') {
                    RmContract::where('rental_id', $contract->rental_id)
                        ->where('id', '!=', $contract->id)
                        ->where('status', 'active')
                        ->update(['status' => 'expired']);

                    // Update rental status to active
                    $contract->rental->update(['status' => 'active']);
                }
                break;

            case 'pending':
                // If setting to pending from active, check if rental should be updated
                if ($oldStatus === 'active') {
                    // Check if there are other active contracts
                    $hasOtherActiveContracts = RmContract::where('rental_id', $contract->rental_id)
                        ->where('id', '!=', $contract->id)
                        ->where('status', 'active')
                        ->exists();

                    if (!$hasOtherActiveContracts) {
                        $contract->rental->update(['status' => 'inactive']);
                    }
                }
                break;

            case 'expired':
                // If expiring a contract, check if rental should be updated
                if ($oldStatus === 'active') {
                    // Check if there are other active contracts
                    $hasOtherActiveContracts = RmContract::where('rental_id', $contract->rental_id)
                        ->where('id', '!=', $contract->id)
                        ->where('status', 'active')
                        ->exists();

                    if (!$hasOtherActiveContracts) {
                        $contract->rental->update(['status' => 'inactive']);
                    }
                }
                break;

            case 'terminated':
                // Void pending installments
                RmPaymentInstallment::where('contract_id', $contract->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'void']);

                // Update rental status if this was the active contract
                if ($oldStatus === 'active') {
                    $contract->rental->update(['status' => 'inactive']);
                }
                break;
        }
    }

    protected function validateNoOverlap($rentalId, $start, $end, $userId, $excludeId = null)
    {
        $query = RmContract::where('user_id', $userId)
            ->where('rental_id', $rentalId)
            ->whereIn('status', ['active', 'pending'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$start])
                    ->orWhereRaw('? BETWEEN start_date AND end_date', [$end]);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'contract' => ['Another contract overlaps with this period.']
            ]);
        }
    }
}
