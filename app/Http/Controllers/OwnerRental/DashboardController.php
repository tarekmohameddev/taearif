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
     * Check property associations (temporary method for debugging)
     */
    public function checkProperty($id)
    {
        try {
            $property = DB::table('user_properties')
                ->leftJoin('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
                ->leftJoin('user_projects', 'user_properties.project_id', '=', 'user_projects.id')
                ->leftJoin('user_project_contents', 'user_projects.id', '=', 'user_project_contents.project_id')
                ->leftJoin('buildings', 'user_properties.building_id', '=', 'buildings.id')
                ->where('user_properties.id', $id)
                ->select(
                    'user_properties.id as property_id',
                    'user_properties.project_id',
                    'user_properties.building_id',
                    'user_properties.building as building_string',
                    'user_property_contents.title as property_title',
                    'user_project_contents.title as project_title',
                    'buildings.name as building_name'
                )
                ->first();

            return response()->json([
                'success' => true,
                'data' => $property
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check property',
                'error' => $e->getMessage(),
            ], 500);
        }
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

            if ($propertyIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard data retrieved successfully',
                    'data' => [
                        'summary_cards' => [
                            'total_properties' => 0,
                            'due_rent' => 0,
                            'overdue_rent' => 0,
                            'collection_rate' => 0
                        ],
                        'properties_table' => []
                    ],
                ], 200);
            }

            // Get all rentals for assigned properties
            $rentalRecords = DB::table('rm_rentals')
                ->whereIn('unit_id', $propertyIds)
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->get();

            // Calculate summary statistics
            $totalProperties = $propertyIds->count();
            $totalDueRent = 0;
            $totalOverdueRent = 0;
            $totalPaidAmount = 0;
            $totalDueAmount = 0;
            $propertiesTable = [];

            foreach ($rentalRecords as $rental) {
                // Get property information with image
                $property = DB::table('user_properties')
                    ->leftJoin('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
                    ->where('user_properties.id', $rental->unit_id)
                    ->select(
                        'user_properties.*',
                        'user_property_contents.title as property_title'
                    )
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
                        ->select('id', 'name')
                        ->first();
                }

                // Get payment installments for this rental
                $installments = DB::table('rm_payment_installments')
                    ->where('rental_id', $rental->id)
                    ->whereIn('status', ['pending', 'partial', 'overdue'])
                    ->orderBy('due_date', 'asc')
                    ->get();

                // Calculate due and overdue amounts
                $dueRent = 0;
                $overdueRent = 0;
                $nextDueDate = null;
                $lastPaymentDate = null;

                foreach ($installments as $installment) {
                    $dueDate = \Carbon\Carbon::parse($installment->due_date);
                    $today = now();

                    if ($dueDate->isFuture()) {
                        // Future due date
                        if ($nextDueDate === null) {
                            $nextDueDate = $installment->due_date;
                            $dueRent = (float) $installment->amount - (float) $installment->paid_amount;
                        }
                    } else {
                        // Past due date
                        $overdueRent += (float) $installment->amount - (float) $installment->paid_amount;
                    }
                }

                // Get last payment date
                $lastPayment = DB::table('rm_payments')
                    ->where('rental_id', $rental->id)
                    ->where('payment_type', 'rent')
                    ->orderBy('payment_date', 'desc')
                    ->first();

                if ($lastPayment) {
                    $lastPaymentDate = $lastPayment->payment_date;
                }

                // Calculate total amounts for collection rate
                $totalDueAmount += $dueRent + $overdueRent;

                // Get total paid amount for this rental
                $rentalPaidAmount = DB::table('rm_payments')
                    ->where('rental_id', $rental->id)
                    ->where('payment_type', 'rent')
                    ->sum('amount');
                $totalPaidAmount += (float) $rentalPaidAmount;

                // Determine status
                $status = 'محدث'; // Updated (default)
                $statusColor = 'green';
                if ($overdueRent > 0) {
                    $status = 'متأخر'; // Overdue
                    $statusColor = 'red';
                }

                // Build property image URL
                $propertyImageUrl = null;
                if ($property && $property->featured_image) {
                    // Handle different image path formats
                    $imagePath = $property->featured_image;
                    if (str_starts_with($imagePath, 'http')) {
                        $propertyImageUrl = $imagePath;
                    } else {
                        // Clean the path and ensure it starts with properties/
                        $cleanPath = ltrim($imagePath, '/');

                        // If path already contains 'properties/', use it as is
                        if (str_starts_with($cleanPath, 'properties/')) {
                            $propertyImageUrl = asset($cleanPath);
                        } else {
                            // If path doesn't contain 'properties/', add it
                            $propertyImageUrl = asset('properties/' . $cleanPath);
                        }
                    }
                }

                // Add to summary totals
                $totalDueRent += $dueRent;
                $totalOverdueRent += $overdueRent;

                // Build properties table data
                $propertiesTable[] = [
                    'property' => [
                        'id' => $property->id ?? null,
                        'title' => $property->property_title ?? 'N/A',
                        'unit_number' => $property->building ?? ($property->id ?? 'N/A'),
                        'image_url' => $propertyImageUrl
                    ],
                    'project' => [
                        'id' => $project->id ?? null,
                        'name' => $project->project_title ?? null
                    ],
                    'building' => [
                        'id' => $building->id ?? null,
                        'name' => $building->name ?? null
                    ],
                    'due_rent' => $dueRent,
                    'overdue_rent' => $overdueRent,
                    'due_date' => $nextDueDate,
                    'last_payment' => $lastPaymentDate,
                    'status' => [
                        'text' => $status,
                        'color' => $statusColor
                    ],
                    'currency' => $rental->currency ?? 'SAR'
                ];
            }

            // Calculate collection rate
            $collectionRate = 0;
            if ($totalDueAmount > 0) {
                $collectionRate = round(($totalPaidAmount / $totalDueAmount) * 100, 1);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data retrieved successfully',
                'data' => [
                    'summary_cards' => [
                        'total_properties' => $totalProperties,
                        'due_rent' => $totalDueRent,
                        'overdue_rent' => $totalOverdueRent,
                        'collection_rate' => $collectionRate
                    ],
                    'properties_table' => $propertiesTable
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

