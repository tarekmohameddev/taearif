<?php

namespace App\Http\Controllers\OwnerRental;

use App\Http\Controllers\Controller;
use App\Models\OwnerRental;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get authenticated owner rental
     *
     * @return \App\Models\OwnerRental
     */
    private function getOwnerRental(): OwnerRental
    {
        /** @var \App\Models\OwnerRental $ownerRental */
        $ownerRental = Auth::user();
        return $ownerRental;
    }

    /**
     * Get dashboard statistics with rental payment information
     */
    public function dashboard()
    {
        try {
            $ownerRental = $this->getOwnerRental();

            // Get property IDs assigned to owner rental
            $propertyIds = $ownerRental->properties()->pluck('user_properties.id');

            // Initialize statistics
            $stats = [
                'total_properties' => $ownerRental->properties()->count(),
                'active_properties' => $ownerRental->properties()->where('status', 1)->count(),
                'featured_properties' => $ownerRental->properties()->where('featured', 1)->count(),
                'total_rentals' => 0,
                'active_rentals' => 0,
            ];

            $rentals = [];
            $totalAmountDue = 0;
            $totalOverdue = 0;

            if ($propertyIds->isNotEmpty()) {
                // Get rentals with relationships
                $rentalRecords = DB::table('rm_rentals')
                    ->whereIn('unit_id', $propertyIds)
                    ->whereNull('deleted_at')
                    ->get();

                $stats['total_rentals'] = $rentalRecords->count();
                $stats['active_rentals'] = $rentalRecords->where('status', 'active')->count();

                foreach ($rentalRecords as $rental) {
                    // Get property information
                    $property = DB::table('user_properties')
                        ->leftJoin('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
                        ->where('user_properties.id', $rental->unit_id)
                        ->select('user_properties.*', 'user_property_contents.title as property_title')
                        ->first();

                    // Get project information
                    $project = null;
                    if ($rental->project_id) {
                        $project = DB::table('user_projects')
                            ->leftJoin('user_project_contents', 'user_projects.id', '=', 'user_project_contents.project_id')
                            ->where('user_projects.id', $rental->project_id)
                            ->select('user_projects.*', 'user_project_contents.title as project_title')
                            ->first();
                    }

                    // Get building information
                    $building = null;
                    if ($rental->building_id) {
                        $building = DB::table('buildings')
                            ->where('id', $rental->building_id)
                            ->first();
                    }

                    // Get next payment due (upcoming installment)
                    $nextInstallment = DB::table('rm_payment_installments')
                        ->where('rental_id', $rental->id)
                        ->whereIn('status', ['pending', 'active'])
                        ->whereDate('due_date', '>=', now()->toDateString())
                        ->orderBy('due_date', 'asc')
                        ->first();

                    // Calculate overdue amount (past due installments that are unpaid)
                    $overdueAmount = DB::table('rm_payment_installments')
                        ->where('rental_id', $rental->id)
                        ->whereIn('status', ['pending', 'active'])
                        ->whereDate('due_date', '<', now()->toDateString())
                        ->sum('amount');

                    $amountDue = $nextInstallment ? (float) $nextInstallment->amount : 0;
                    $overdueAmount = (float) $overdueAmount;

                    $totalAmountDue += $amountDue;
                    $totalOverdue += $overdueAmount;

                    // Build rental data with requested keys
                    $rentals[] = [
                        'rental_id' => $rental->id,
                        'property_name' => $property ? ($property->property_title ?? 'N/A') : 'N/A',
                        'project_name' => $project ? ($project->project_title ?? 'N/A') : 'N/A',
                        'building_name' => $building ? ($building->name ?? 'N/A') : 'N/A',
                        'amount_due' => $amountDue,
                        'due_date' => $nextInstallment ? $nextInstallment->due_date : null,
                        'overdue_amount' => $overdueAmount,
                        'currency' => $rental->currency ?? 'SAR',
                        'tenant_name' => $rental->tenant_full_name,
                        'rental_status' => $rental->status,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'owner_rental' => [
                        'id' => $ownerRental->id,
                        'name' => $ownerRental->name,
                        'email' => $ownerRental->email,
                    ],
                    'statistics' => $stats,
                    'rentals' => $rentals,
                    'summary' => [
                        'total_amount_due' => $totalAmountDue,
                        'total_overdue' => $totalOverdue,
                        'properties_count' => $propertyIds->count(),
                        'active_rentals' => $stats['active_rentals'],
                    ],
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

