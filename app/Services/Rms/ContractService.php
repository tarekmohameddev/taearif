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

        $this->validateNoOverlap($rentalId, $data['start_date'], $data['end_date'], $userId);

        return DB::transaction(function () use ($data, $userId, $rental) {
            $contract = RmContract::create([
                'user_id' => $userId,
                'rental_id' => $rental->id,
                'contract_number' => $data['contract_number'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
                'file_path' => $data['file_path'] ?? null,
                'property_id' => $data['property_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'property_name' => $data['property_name'] ?? null,
                'project_name' => $data['project_name'] ?? null,
                'water_fee_monthly' => $data['water_fee_monthly'] ?? 0,
                'office_commission_type' => $data['office_commission_type'] ?? null,
                'office_commission_value' => $data['office_commission_value'] ?? null,
                'platform_fee' => $data['platform_fee'] ?? 0,
                'grace_period_months' => $data['grace_period_months'] ?? 0,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($data['status'] === 'active') {
                $rental->status = 'active';
                $rental->save();
            }

            // if (!empty($data['generate_schedule'])) {
                app(InstallmentService::class)->generateSchedule($contract);
            // }

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

            return $contract;
        });
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
