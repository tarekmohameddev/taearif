<?php

namespace App\Http\Controllers\OwnerRental;

use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get authenticated owner rental
     */
    private function getOwnerRental()
    {
        return Auth::user();
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        try {
            $ownerRental = $this->getOwnerRental();

            $stats = [
                'total_properties' => $ownerRental->properties()->count(),
                'active_properties' => $ownerRental->properties()->where('status', 1)->count(),
                'featured_properties' => $ownerRental->properties()->where('featured', 1)->count(),
                'total_rentals' => 0,
                'active_rentals' => 0,
            ];

            // Count rentals from assigned properties
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            if ($propertyIds->isNotEmpty()) {
                $stats['total_rentals'] = DB::table('rm_rentals')
                    ->whereIn('unit_id', $propertyIds)
                    ->count();

                $stats['active_rentals'] = DB::table('rm_rentals')
                    ->whereIn('unit_id', $propertyIds)
                    ->where('status', 'active')
                    ->count();
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'owner_rental' => $ownerRental,
                    'statistics' => $stats,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all assigned properties
     */
    public function properties(Request $request)
    {
        try {
            $ownerRental = $this->getOwnerRental();

            $perPage = $request->input('per_page', 15);
            $search = $request->input('search');
            $status = $request->input('status');
            $featured = $request->input('featured');

            $query = $ownerRental->properties()
                ->with(['contents', 'category', 'galleryImages', 'amenities']);

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

            $properties = $query->latest()->paginate($perPage);

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

    /**
     * Get single property details
     */
    public function propertyDetails($id)
    {
        try {
            $ownerRental = $this->getOwnerRental();

            // Check if owner rental has access to this property
            if (!$ownerRental->hasAccessToProperty($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this property',
                ], 403);
            }

            $property = Property::with([
                'contents',
                'category',
                'galleryImages',
                'amenities',
                'specifications',
                'rentals',
                'project',
                'building',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Property details retrieved successfully',
                'data' => $property,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Property not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get rentals for assigned properties
     */
    public function rentals(Request $request)
    {
        try {
            $ownerRental = $this->getOwnerRental();
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            if ($propertyIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No properties assigned',
                    'data' => [
                        'data' => [],
                        'total' => 0,
                    ],
                ], 200);
            }

            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');

            $query = DB::table('rm_rentals')
                ->whereIn('unit_id', $propertyIds)
                ->select('rm_rentals.*');

            if ($status) {
                $query->where('status', $status);
            }

            $rentals = $query->latest('created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Rentals retrieved successfully',
                'data' => $rentals,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rentals',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tenants for assigned properties
     */
    public function tenants(Request $request)
    {
        try {
            $ownerRental = $this->getOwnerRental();
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            if ($propertyIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No properties assigned',
                    'data' => [
                        'data' => [],
                        'total' => 0,
                    ],
                ], 200);
            }

            $perPage = $request->input('per_page', 15);

            // Get unique tenants from rentals (tenant info is stored directly in rm_rentals table)
            $tenants = DB::table('rm_rentals')
                ->whereIn('unit_id', $propertyIds)
                ->whereNotNull('tenant_full_name')
                ->select([
                    'tenant_full_name as name',
                    'tenant_phone as phone',
                    'tenant_email as email',
                    'tenant_job_title as job_title',
                    'tenant_social_status as social_status',
                    'tenant_national_id as national_id',
                    'move_in_date',
                    'status as rental_status',
                    'unit_id as property_id',
                    'created_at'
                ])
                ->distinct()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Tenants retrieved successfully',
                'data' => $tenants,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tenants',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial reports for assigned properties
     */
    public function financialReports(Request $request)
    {
        try {
            $ownerRental = $this->getOwnerRental();
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            if ($propertyIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No properties assigned',
                    'data' => [
                        'total_revenue' => 0,
                        'pending_payments' => 0,
                        'completed_payments' => 0,
                        'properties_count' => 0,
                    ],
                ], 200);
            }

            // Calculate financial statistics
            $totalRevenue = DB::table('rm_payments')
                ->join('rm_rentals', 'rm_payments.rental_id', '=', 'rm_rentals.id')
                ->whereIn('rm_rentals.unit_id', $propertyIds)
                ->sum('rm_payments.amount');

            // Since rm_payments table doesn't have status, we'll calculate different metrics
            $totalPaymentsCount = DB::table('rm_payments')
                ->join('rm_rentals', 'rm_payments.rental_id', '=', 'rm_rentals.id')
                ->whereIn('rm_rentals.unit_id', $propertyIds)
                ->count();

            $rentPayments = DB::table('rm_payments')
                ->join('rm_rentals', 'rm_payments.rental_id', '=', 'rm_rentals.id')
                ->whereIn('rm_rentals.unit_id', $propertyIds)
                ->where('rm_payments.payment_type', 'rent')
                ->sum('rm_payments.amount');

            $depositPayments = DB::table('rm_payments')
                ->join('rm_rentals', 'rm_payments.rental_id', '=', 'rm_rentals.id')
                ->whereIn('rm_rentals.unit_id', $propertyIds)
                ->where('rm_payments.payment_type', 'deposit')
                ->sum('rm_payments.amount');

            return response()->json([
                'success' => true,
                'message' => 'Financial reports retrieved successfully',
                'data' => [
                    'total_revenue' => (float) $totalRevenue,
                    'rent_payments' => (float) $rentPayments,
                    'deposit_payments' => (float) $depositPayments,
                    'total_payments_count' => $totalPaymentsCount,
                    'properties_count' => $propertyIds->count(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve financial reports',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get maintenance requests for assigned properties
     */
    public function maintenanceRequests(Request $request)
    {
        try {
            $ownerRental = $this->getOwnerRental();
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            if ($propertyIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No properties assigned',
                    'data' => [
                        'data' => [],
                        'total' => 0,
                    ],
                ], 200);
            }

            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');

            $query = DB::table('rm_maintenance_tickets')
                ->join('rm_rentals', 'rm_maintenance_tickets.rental_id', '=', 'rm_rentals.id')
                ->whereIn('rm_rentals.unit_id', $propertyIds)
                ->select('rm_maintenance_tickets.*');

            if ($status) {
                $query->where('rm_maintenance_tickets.status', $status);
            }

            $maintenanceRequests = $query->latest('rm_maintenance_tickets.created_at')->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance requests retrieved successfully',
                'data' => $maintenanceRequests,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve maintenance requests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

