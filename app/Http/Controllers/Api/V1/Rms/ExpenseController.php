<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use App\Models\RmExpense;
use App\Models\Api\Rms\RmRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExpenseController extends Controller
{
    /**
     * Get expenses for a rental
     */
    public function index(Request $request, $rentalId)
    {
        try {
            $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            // Verify rental belongs to user
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($rentalId);

            $expenses = $rental->expenses()->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => true,
                'data' => $expenses->map(function ($expense) use ($rental) {
                    // Use the actual base_rent_amount column value, not the accessor
                    $baseRentAmount = $rental->getAttributes()['base_rent_amount'] ?? 0;
                    $calculatedAmount = $expense->amount_type === 'percentage'
                        ? ($baseRentAmount * $expense->amount_value) / 100
                        : $expense->amount_value;

                    return [
                        'id' => $expense->id,
                        'expense_name' => $expense->expense_name,
                        'image_path' => $expense->image_path,
                        'image_url' => $expense->image_url,
                        'amount_type' => $expense->amount_type,
                        'amount_value' => (float) $expense->amount_value,
                        'calculated_amount' => (float) $calculatedAmount,
                        'cost_center' => $expense->cost_center,
                        'is_active' => $expense->is_active,
                        'can_be_modified' => $expense->canBeModified(),
                        'created_at' => $expense->created_at,
                        'updated_at' => $expense->updated_at,
                    ];
                })
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rental not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new expense for a rental
     */
    public function store(Request $request, $rentalId)
    {
        try {
            $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            // Verify rental belongs to user
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($rentalId);

            $data = $request->validate([
                'expense_name' => 'required|string|max:255',
                'image_path' => 'nullable|string',
                'amount_type' => 'required|in:percentage,fixed',
                'amount_value' => 'required|numeric|min:0',
                'cost_center' => 'required|in:tenant,owner',
                'is_active' => 'nullable|boolean',
            ]);

            $data['user_id'] = $ownerId;
            $data['rental_id'] = $rentalId;
            $data['is_active'] = $data['is_active'] ?? true;

            $expense = RmExpense::create($data);

            // Use the actual base_rent_amount column value, not the accessor
            $baseRentAmount = $rental->getAttributes()['base_rent_amount'] ?? 0;
            $calculatedAmount = $expense->amount_type === 'percentage'
                ? ($baseRentAmount * $expense->amount_value) / 100
                : $expense->amount_value;

            return response()->json([
                'status' => true,
                'message' => 'Expense created successfully',
                'data' => [
                    'id' => $expense->id,
                    'expense_name' => $expense->expense_name,
                    'image_path' => $expense->image_path,
                    'image_url' => $expense->image_url,
                    'amount_type' => $expense->amount_type,
                    'amount_value' => (float) $expense->amount_value,
                    'calculated_amount' => (float) $calculatedAmount,
                    'cost_center' => $expense->cost_center,
                    'is_active' => $expense->is_active,
                    'can_be_modified' => $expense->canBeModified(),
                    'created_at' => $expense->created_at,
                    'updated_at' => $expense->updated_at,
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rental not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update expense
     */
    public function update(Request $request, $rentalId, $expenseId)
    {
        try {
            $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            $expense = RmExpense::where('user_id', $ownerId)
                ->where('rental_id', $rentalId)
                ->findOrFail($expenseId);

            // Check if expense can be modified
            if (!$expense->canBeModified()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot modify expense for expired contracts'
                ], 403);
            }

            $data = $request->validate([
                'expense_name' => 'sometimes|string|max:255',
                'image_path' => 'nullable|string',
                'amount_type' => 'sometimes|in:percentage,fixed',
                'amount_value' => 'sometimes|numeric|min:0',
                'cost_center' => 'sometimes|in:tenant,owner',
                'is_active' => 'nullable|boolean',
            ]);

            $expense->update($data);

            // Get fresh rental data for calculation
            $rental = $expense->rental;
            // Use the actual base_rent_amount column value, not the accessor
            $baseRentAmount = $rental->getAttributes()['base_rent_amount'] ?? 0;
            $calculatedAmount = $expense->amount_type === 'percentage'
                ? ($baseRentAmount * $expense->amount_value) / 100
                : $expense->amount_value;

            return response()->json([
                'status' => true,
                'message' => 'Expense updated successfully',
                'data' => [
                    'id' => $expense->id,
                    'expense_name' => $expense->expense_name,
                    'image_path' => $expense->image_path,
                    'image_url' => $expense->image_url,
                    'amount_type' => $expense->amount_type,
                    'amount_value' => (float) $expense->amount_value,
                    'calculated_amount' => (float) $calculatedAmount,
                    'cost_center' => $expense->cost_center,
                    'is_active' => $expense->is_active,
                    'can_be_modified' => $expense->canBeModified(),
                    'created_at' => $expense->created_at,
                    'updated_at' => $expense->updated_at,
                ]
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expense not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete expense
     */
    public function destroy($rentalId, $expenseId)
    {
        try {
            $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            $expense = RmExpense::where('user_id', $ownerId)
                ->where('rental_id', $rentalId)
                ->findOrFail($expenseId);

            // Check if expense can be modified
            if (!$expense->canBeModified()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete expense for expired contracts'
                ], 403);
            }

            // Delete associated image if exists
            if ($expense->image_path && Storage::disk('public')->exists($expense->image_path)) {
                Storage::disk('public')->delete($expense->image_path);
            }

            $expense->delete();

            return response()->json([
                'status' => true,
                'message' => 'Expense deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Expense not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload image for expense
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        try {
            // Generate unique filename
            $filename = Str::uuid() . '.' . $request->image->getClientOriginalExtension();

            // Store the image
            $path = $request->image->storeAs('expenses', $filename, 'public');

            return response()->json([
                'status' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'image_path' => $path,
                    'image_url' => asset('storage/' . $path)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload image',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }
}
