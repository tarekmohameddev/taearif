<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\Rms\Contract\StoreContractRequest;
use App\Http\Requests\Rms\Contract\UpdateContractRequest;
use App\Http\Requests\Api\V1\Rms\TerminateContractRequest;
use App\Http\Requests\Api\V1\Rms\ChangeContractStatusRequest;
use App\Http\Resources\Rms\ContractResource;
use App\Constants\RmsConstants;
use Illuminate\Http\Request;
use App\Models\Api\Rms\RmContract;
use App\Services\Rms\ContractService;

class ContractController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $contractService;

    public function __construct(ContractService $contractService)
    {
        $this->contractService = $contractService;
    }

    public function listByRental($rentalId)
    {
        return $this->executeWithExceptionHandling(function () use ($rentalId) {
            $userId = $this->getUserId();

            // NOTE: Direct model access here - consider moving to service layer in Phase 4
            $contracts = RmContract::where('rental_id', $rentalId)
                ->where('user_id', $userId)
                ->with('rental:id,contract_number')
                ->orderBy('start_date')
                ->get();

            return $this->success(ContractResource::collection($contracts));
        }, 'list contracts by rental');
    }

    public function store(StoreContractRequest $request, $rentalId)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $rentalId) {
            $contract = $this->contractService->createContract(
                $rentalId,
                $request->validated(),
                $this->getUserId()
            );

            return $this->created(
                ContractResource::make($contract),
                'Contract created successfully'
            );
        }, 'create contract');
    }

    public function update(UpdateContractRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $contract = $this->contractService->updateContract(
                $id,
                $request->validated(),
                $this->getUserId()
            );

            return $this->success(
                ContractResource::make($contract),
                'Contract updated successfully'
            );
        }, 'update contract');
    }

    public function terminate(TerminateContractRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $validated = $request->validated();
            $contract = $this->contractService->terminateContract($id, $validated, $this->getUserId());

            return $this->success(
                ContractResource::make($contract),
                'Contract terminated successfully'
            );
        }, 'terminate contract');
    }

    public function changeStatus(ChangeContractStatusRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $validated = $request->validated();
            $contract = $this->contractService->changeContractStatus($id, $validated, $this->getUserId());

            return $this->success(
                ContractResource::make($contract),
                'Contract status changed successfully'
            );
        }, 'change contract status');
    }
}
