<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Http\Requests\Rms\Expense\StoreExpenseRequest;
use App\Http\Requests\Rms\Expense\UpdateExpenseRequest;
use App\Http\Requests\Rms\Expense\UploadImageRequest;
use App\Http\Resources\Rms\ExpenseResource;
use App\Services\Rms\ExpenseService;
use Illuminate\Http\Request;

class ExpenseController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }
    /**
     * Get expenses for a rental
     */
    public function index(Request $request, $rentalId)
    {
        return $this->executeWithExceptionHandling(function () use ($rentalId) {
            $expenses = $this->expenseService->getExpensesByRental(
                $this->getUserId(),
                $rentalId
            );

            return $this->success(ExpenseResource::collection($expenses));
        }, 'list expenses');
    }

    /**
     * Create new expense for a rental
     */
    public function store(StoreExpenseRequest $request, $rentalId)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $rentalId) {
            $expense = $this->expenseService->createExpense(
                $this->getUserId(),
                $rentalId,
                $request->validated()
            );

            return $this->created(
                ExpenseResource::make($expense),
                'Expense created successfully'
            );
        }, 'create expense');
    }

    /**
     * Update expense
     */
    public function update(UpdateExpenseRequest $request, $rentalId, $expenseId)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $rentalId, $expenseId) {
            $expense = $this->expenseService->updateExpense(
                $this->getUserId(),
                $rentalId,
                $expenseId,
                $request->validated()
            );

            return $this->success(
                ExpenseResource::make($expense),
                'Expense updated successfully'
            );
        }, 'update expense');
    }

    /**
     * Delete expense
     */
    public function destroy($rentalId, $expenseId)
    {
        return $this->executeWithExceptionHandling(function () use ($rentalId, $expenseId) {
            $this->expenseService->deleteExpense(
                $this->getUserId(),
                $rentalId,
                $expenseId
            );

            return $this->success(null, 'Expense deleted successfully');
        }, 'delete expense');
    }

    /**
     * Upload image for expense
     */
    public function uploadImage(UploadImageRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            // @phpstan-ignore-next-line - Laravel Request file() method exists
            $imageFile = $request->file('image');

            $uploadedData = $this->expenseService->uploadImage(
                $imageFile,
                $imageFile->getClientOriginalExtension()
            );

            return $this->created($uploadedData, 'Image uploaded successfully');
        }, 'upload expense image');
    }
}
