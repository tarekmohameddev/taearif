<?php

namespace App\Services\Rms;

use App\Models\RmExpense;
use App\Models\Api\Rms\RmRental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;

/**
 * ExpenseService
 *
 * Handles all business logic for rental expenses.
 * This service was created in Phase 4 to move logic out of ExpenseController.
 */
class ExpenseService
{
    /**
     * Get all expenses for a specific rental
     *
     * @param int $userId Owner/User ID
     * @param int $rentalId Rental ID
     * @return Collection
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getExpensesByRental(int $userId, int $rentalId): Collection
    {
        // Verify rental belongs to user
        $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

        return $rental->expenses()
            ->with('rental')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Create a new expense for a rental
     *
     * @param int $userId Owner/User ID
     * @param int $rentalId Rental ID
     * @param array $data Expense data
     * @return RmExpense
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function createExpense(int $userId, int $rentalId, array $data): RmExpense
    {
        // Verify rental belongs to user
        $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

        // Prepare expense data
        $expenseData = array_merge($data, [
            'user_id' => $userId,
            'rental_id' => $rentalId,
            'is_active' => $data['is_active'] ?? true,
        ]);

        // Create expense
        $expense = RmExpense::create($expenseData);

        // Load rental for Resource transformation
        $expense->load('rental');

        return $expense;
    }

    /**
     * Update an existing expense
     *
     * @param int $userId Owner/User ID
     * @param int $rentalId Rental ID
     * @param int $expenseId Expense ID
     * @param array $data Update data
     * @return RmExpense
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function updateExpense(int $userId, int $rentalId, int $expenseId, array $data): RmExpense
    {
        $expense = RmExpense::where('user_id', $userId)
            ->where('rental_id', $rentalId)
            ->findOrFail($expenseId);

        // Check if expense can be modified
        if (!$expense->canBeModified()) {
            throw new \Exception('Cannot modify expense for expired contracts', 403);
        }

        // Update expense
        $expense->update($data);

        // Reload rental for calculations
        $expense->load('rental');

        return $expense;
    }

    /**
     * Delete an expense
     *
     * @param int $userId Owner/User ID
     * @param int $rentalId Rental ID
     * @param int $expenseId Expense ID
     * @return void
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Exception
     */
    public function deleteExpense(int $userId, int $rentalId, int $expenseId): void
    {
        $expense = RmExpense::where('user_id', $userId)
            ->where('rental_id', $rentalId)
            ->findOrFail($expenseId);

        // Check if expense can be modified
        if (!$expense->canBeModified()) {
            throw new \Exception('Cannot delete expense for expired contracts', 403);
        }

        DB::transaction(function () use ($expense) {
            // Delete associated image if exists
            if ($expense->image_path && Storage::disk('public')->exists($expense->image_path)) {
                Storage::disk('public')->delete($expense->image_path);
            }

            $expense->delete();
        });
    }

    /**
     * Upload image for expense
     *
     * @param \Illuminate\Http\UploadedFile $file Uploaded file
     * @param string $originalExtension File extension
     * @return array
     */
    public function uploadImage($file, string $originalExtension): array
    {
        // Generate unique filename
        $filename = \Illuminate\Support\Str::uuid() . '.' . $originalExtension;

        // Store the image
        $path = $file->storeAs('expenses', $filename, 'public');

        return [
            'image_path' => $path,
            'image_url' => asset('storage/' . $path),
            'filename' => $filename,
        ];
    }
}

