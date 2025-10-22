<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerRental\AssignPropertiesRequest;
use App\Http\Requests\OwnerRental\StoreOwnerRentalRequest;
use App\Http\Requests\OwnerRental\UpdateOwnerRentalRequest;
use App\Models\OwnerRental;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class OwnerRentalManagementController extends Controller
{
    /**
     * Get authenticated user
     */
    private function getAuthUser()
    {
        return Auth::guard('sanctum')->user();
    }

    /**
     * Display a listing of owner rentals for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $user = $this->getAuthUser();

            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $status = $request->input('status'); // 'active' or 'inactive'

            $query = OwnerRental::where('user_id', $user->id)
                ->with(['properties' => function ($query) {
                    $query->select('id', 'featured_image', 'price', 'status');
                }]);

            // Search by name, email, or phone
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%")
                      ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            }

            // Filter by status
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }

            $ownerRentals = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Owner rentals retrieved successfully',
                'data' => $ownerRentals,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve owner rentals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created owner rental.
     */
    public function store(StoreOwnerRentalRequest $request)
    {
        try {
            $user = $this->getAuthUser();

            DB::beginTransaction();

            $ownerRental = OwnerRental::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'id_number' => $request->id_number,
                'address' => $request->address,
                'city' => $request->city,
                'is_active' => $request->input('is_active', true),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Owner rental created successfully',
                'data' => $ownerRental,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create owner rental',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified owner rental.
     */
    public function show($id)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->with(['properties.contents', 'properties.category'])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Owner rental retrieved successfully',
                'data' => $ownerRental,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Owner rental not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified owner rental.
     */
    public function update(UpdateOwnerRentalRequest $request, $id)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            $updateData = [
                'name' => $request->input('name', $ownerRental->name),
                'email' => $request->input('email', $ownerRental->email),
                'phone' => $request->input('phone', $ownerRental->phone),
                'id_number' => $request->input('id_number', $ownerRental->id_number),
                'address' => $request->input('address', $ownerRental->address),
                'city' => $request->input('city', $ownerRental->city),
                'is_active' => $request->input('is_active', $ownerRental->is_active),
            ];

            // Only update password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $ownerRental->update($updateData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Owner rental updated successfully',
                'data' => $ownerRental->fresh(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update owner rental',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified owner rental.
     */
    public function destroy($id)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            // Detach all properties before deleting
            $ownerRental->properties()->detach();

            // Soft delete the owner rental
            $ownerRental->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Owner rental deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete owner rental',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign properties to owner rental.
     */
    public function assignProperties(AssignPropertiesRequest $request, $id)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            DB::beginTransaction();

            // Verify that all properties belong to the user
            $properties = Property::where('user_id', $user->id)
                ->whereIn('id', $request->property_ids)
                ->pluck('id');

            if ($properties->count() !== count($request->property_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some properties do not belong to you or do not exist',
                ], 403);
            }

            // Sync properties (this will replace existing assignments)
            $syncData = [];
            foreach ($properties as $propertyId) {
                $syncData[$propertyId] = [
                    'user_id' => $user->id,
                    'assigned_at' => now(),
                ];
            }

            $ownerRental->properties()->sync($syncData);

            DB::commit();

            $ownerRental->load('properties');

            return response()->json([
                'success' => true,
                'message' => 'Properties assigned successfully',
                'data' => [
                    'owner_rental' => $ownerRental,
                    'assigned_properties_count' => $ownerRental->properties->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific property from owner rental.
     */
    public function removeProperty($id, $propertyId)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            // Verify property belongs to user
            $property = Property::where('user_id', $user->id)
                ->where('id', $propertyId)
                ->firstOrFail();

            DB::beginTransaction();

            $ownerRental->properties()->detach($propertyId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Property removed from owner rental successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove property',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get properties assigned to owner rental.
     */
    public function getAssignedProperties($id, Request $request)
    {
        try {
            $user = $this->getAuthUser();

            $ownerRental = OwnerRental::where('user_id', $user->id)
                ->where('id', $id)
                ->firstOrFail();

            $perPage = $request->input('per_page', 15);

            $properties = $ownerRental->properties()
                ->with(['contents', 'category'])
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Assigned properties retrieved successfully',
                'data' => $properties,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve assigned properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all properties belonging to the authenticated user (for assignment selection).
     */
    public function getMyProperties(Request $request)
    {
        try {
            $user = $this->getAuthUser();

            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $status = $request->input('status');
            $featured = $request->input('featured');
            $exclude_assigned = $request->input('exclude_assigned', false);

            $query = Property::where('user_id', $user->id)
                ->with(['contents', 'category', 'galleryImages']);

            // Search functionality
            if ($search) {
                $query->whereHas('contents', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Filter by status
            if ($status !== null) {
                $query->where('status', $status);
            }

            // Filter by featured
            if ($featured !== null) {
                $query->where('featured', $featured);
            }

            // Exclude properties already assigned to any owner rental
            if ($exclude_assigned) {
                $query->whereDoesntHave('ownerRentals');
            }

            $properties = $query->latest()->paginate($perPage);

            // Add assignment info to each property
            $properties->getCollection()->transform(function ($property) {
                $property->assigned_to_owner_rentals = $property->ownerRentals->pluck('name')->toArray();
                $property->assigned_count = $property->ownerRentals->count();
                return $property;
            });

            return response()->json([
                'success' => true,
                'message' => 'Properties retrieved successfully',
                'data' => $properties,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve properties',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

