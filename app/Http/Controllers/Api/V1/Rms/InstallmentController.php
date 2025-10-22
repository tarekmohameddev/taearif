<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\Rms\Installment\UpdateInstallmentRequest;
use Illuminate\Http\Request;
use App\Services\Rms\InstallmentService;

class InstallmentController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $installmentService;

    public function __construct(InstallmentService $installmentService)
    {
        $this->installmentService = $installmentService;
    }

    public function index(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $filters = $request->only(['rental_id', 'contract_id', 'status', 'from', 'to']);
            $installments = $this->installmentService->listInstallments($this->getUserId(), $filters);

            return $this->success($installments);
        }, 'list installments');
    }

    public function update(UpdateInstallmentRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $updated = $this->installmentService->updateInstallment(
                $id,
                $request->validated(),
                $this->getUserId()
            );

            return $this->success($updated, 'Installment updated successfully');
        }, 'update installment');
    }

    public function regenerate(Request $request, $rentalId)
    {
        return $this->executeWithExceptionHandling(function () use ($rentalId) {
            $this->installmentService->regenerateSchedule($rentalId, $this->getUserId());

            return $this->success(null, 'Installment schedule regenerated successfully');
        }, 'regenerate installment schedule');
    }
}
