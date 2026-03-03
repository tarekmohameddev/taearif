<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestStage;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Project;
use App\Http\Requests\Pms\ListPurchaseRequestsRequest;
use App\Http\Requests\Pms\StorePurchaseRequestRequest;
use App\Http\Requests\Pms\UpdatePurchaseRequestRequest;
use App\Http\Requests\Pms\TransitionStageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseRequestController extends Controller
{
    /**
     * Display a listing of purchase requests
     */
    public function index(ListPurchaseRequestsRequest $request)
    {
        $query = PurchaseRequest::with(['property', 'project', 'assignedUser', 'stages']);

        // Apply filters
        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('overall_status') && $request->overall_status) {
            $query->where('overall_status', $request->overall_status);
        }

        if ($request->has('assigned_to') && $request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('progress_min') && $request->progress_min !== null) {
            $query->where('progress_percentage', '>=', $request->progress_min);
        }

        if ($request->has('progress_max') && $request->progress_max !== null) {
            $query->where('progress_percentage', '<=', $request->progress_max);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('request_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('request_date', '<=', $request->date_to);
        }

        // Search by client name, email, or phone
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Validate pagination parameters
        $perPage = max(1, min(100, (int) $perPage)); // Limit between 1 and 100
        $page = max(1, (int) $page);
        
        $purchaseRequests = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $purchaseRequests->items(),
            'pagination' => [
                'current_page' => $purchaseRequests->currentPage(),
                'per_page' => $purchaseRequests->perPage(),
                'total' => $purchaseRequests->total(),
                'last_page' => $purchaseRequests->lastPage(),
                'from' => $purchaseRequests->firstItem(),
                'to' => $purchaseRequests->lastItem(),
                'has_more_pages' => $purchaseRequests->hasMorePages(),
                'links' => [
                    'first' => $purchaseRequests->url(1),
                    'last' => $purchaseRequests->url($purchaseRequests->lastPage()),
                    'prev' => $purchaseRequests->previousPageUrl(),
                    'next' => $purchaseRequests->nextPageUrl(),
                ]
            ],
            'message' => 'Purchase requests retrieved successfully'
        ]);
    }

    /**
     * Store a newly created purchase request
     */
    public function store(StorePurchaseRequestRequest $request)
    {
        $user = Auth::user();
        $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        
        $validated = $request->validated();

        $validated['request_date'] = now();
        // user_id will be auto-set by the model's boot method

        $purchaseRequest = PurchaseRequest::create($validated);

        // Auto-start first stage (الحجز) as in progress
        try {
            $firstStage = $purchaseRequest->stages()->where('stage_order', 1)->first();
            if ($firstStage && $firstStage->status === 'الانتظار') {
                $firstStage->update([
                    'status' => 'قيد التنفيذ',
                    'started_at' => now(),
                    'updated_by' => Auth::id(),
                    'notes' => $firstStage->notes ?: 'تم بدء مرحلة الحجز',
                ]);

                // Ensure overall status reflects progress start
                $purchaseRequest->update([
                    'overall_status' => 'in_progress',
                ]);
            }
        } catch (\Throwable $e) {
            // Non-fatal: if this fails, creation should still succeed
        }

        return response()->json([
            'success' => true,
            'data' => $purchaseRequest->load(['property', 'project', 'assignedUser', 'stages']),
            'message' => 'Purchase request created successfully'
        ], 201);
    }

    /**
     * Display the specified purchase request
     */
    public function show($id)
    {
        $purchaseRequest = PurchaseRequest::with([
            'property.contents',
            'project.contents', 
            'assignedUser',
            'stages' => function($query) {
                $query->orderBy('stage_order');
            },
            'stages.updatedBy'
        ])->findOrFail($id);

        // Transform the data to match the interface requirements
        $transformedData = [
            'id' => $purchaseRequest->id,
            'request_number' => $purchaseRequest->request_number,
            'request_date' => $purchaseRequest->request_date,
            'overall_status' => $purchaseRequest->overall_status,
            'progress_percentage' => $purchaseRequest->progress_percentage,
            'priority' => $purchaseRequest->priority,
            'expected_completion_date' => $purchaseRequest->expected_completion_date,
            
            // Client Information
            'client' => [
                'name' => $purchaseRequest->client_name,
                'email' => $purchaseRequest->client_email,
                'phone' => $purchaseRequest->client_phone,
                'national_id' => $purchaseRequest->client_national_id,
                'rating' => 4.8, // This would need to be calculated from actual data
            ],
            
            // Property Information
            'property' => $purchaseRequest->property ? [
                'id' => $purchaseRequest->property->id,
                'title' => $purchaseRequest->property->firstContent ? $purchaseRequest->property->firstContent->title : 'No Title',
                'type' => $purchaseRequest->property->type,
                'area' => $purchaseRequest->property->area,
                'beds' => $purchaseRequest->property->beds,
                'bath' => $purchaseRequest->property->bath,
                'price' => $purchaseRequest->property->price,
                'developer' => $purchaseRequest->property->developer ?? 'N/A',
                'location' => $purchaseRequest->property->location ?? 'N/A',
                'total_purchases' => 3, // This would need to be calculated from actual data
            ] : null,
            
            // Project Information (if applicable)
            'project' => $purchaseRequest->project ? [
                'id' => $purchaseRequest->project->id,
                'title' => $purchaseRequest->project->firstContent ? $purchaseRequest->project->firstContent->title : 'No Title',
                'min_price' => $purchaseRequest->project->min_price,
                'max_price' => $purchaseRequest->project->max_price,
                'developer' => $purchaseRequest->project->developer,
                'completion_date' => $purchaseRequest->project->completion_date,
            ] : null,
            
            // Stages Information
            'stages' => $purchaseRequest->stages->map(function($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->stage_name,
                    'order' => $stage->stage_order,
                    'status' => $stage->status,
                    'notes' => $stage->notes,
                    'started_at' => $stage->started_at,
                    'completed_at' => $stage->completed_at,
                    'updated_by' => $stage->updatedBy ? [
                        'id' => $stage->updatedBy->id,
                        'name' => $stage->updatedBy->first_name . ' ' . $stage->updatedBy->last_name,
                        'email' => $stage->updatedBy->email,
                    ] : null,
                    'documents' => [], // This would need to be implemented based on your document system
                ];
            }),
            
            // Additional Information
            'budget_amount' => $purchaseRequest->budget_amount,
            'notes' => $purchaseRequest->notes,
            'additional_notes' => $purchaseRequest->additional_notes,
            'assigned_user' => $purchaseRequest->assignedUser ? [
                'id' => $purchaseRequest->assignedUser->id,
                'name' => $purchaseRequest->assignedUser->first_name . ' ' . $purchaseRequest->assignedUser->last_name,
                'email' => $purchaseRequest->assignedUser->email,
                'role' => $purchaseRequest->assignedUser->account_type,
            ] : null,
            
            // Metadata
            'created_at' => $purchaseRequest->created_at,
            'updated_at' => $purchaseRequest->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'message' => 'Purchase request retrieved successfully'
        ]);
    }

    /**
     * Update the specified purchase request
     */
    public function update(UpdatePurchaseRequestRequest $request, $id)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        
        $validated = $request->validated();

        $purchaseRequest->update($validated);

        return response()->json([
            'success' => true,
            'data' => $purchaseRequest->load(['property', 'project', 'assignedUser', 'stages']),
            'message' => 'Purchase request updated successfully'
        ]);
    }

    /**
     * Remove the specified purchase request
     */
    public function destroy($id)
    {
        $purchaseRequest = PurchaseRequest::findOrFail($id);
        $purchaseRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Purchase request deleted successfully'
        ]);
    }

    /**
     * Transition to next stage
     */
    public function transitionStage(TransitionStageRequest $request, $id)
    {
        $purchaseRequest = PurchaseRequest::with('stages')->findOrFail($id);
        $user = Auth::user();

        $validated = $request->validated();

        // Get current stage
        $currentStage = $purchaseRequest->stages()
            ->where('stage_name', $validated['current_stage_name'])
            ->first();

        if (!$currentStage) {
            return response()->json([
                'success' => false,
                'message' => 'Current stage not found'
            ], 404);
        }

        // Check if current stage is in progress
        if ($currentStage->status !== 'قيد التنفيذ') {
            return response()->json([
                'success' => false,
                'message' => 'Current stage must be in progress to transition'
            ], 400);
        }

        // Validate that all requirements are met
        $allRequirementsMet = collect($validated['requirements_met'])->every(function($met) {
            return $met === true;
        });

        if (!$allRequirementsMet) {
            return response()->json([
                'success' => false,
                'message' => 'All requirements must be met before transitioning to next stage'
            ], 400);
        }

        // Get next stage
        $nextStageName = $this->getNextStageName($validated['current_stage_name']);
        $nextStage = $purchaseRequest->stages()
            ->where('stage_name', $nextStageName)
            ->first();

        if (!$nextStage) {
            return response()->json([
                'success' => false,
                'message' => 'Next stage not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Disable model events temporarily to avoid recursive calls
            PurchaseRequestStage::unsetEventDispatcher();

            // Complete current stage
            $currentStage->update([
                'status' => 'مكتمل',
                'completed_at' => now(),
                'updated_by' => $user->id,
                'notes' => $currentStage->notes . ($validated['additional_notes'] ? "\n\nAdditional Notes: " . $validated['additional_notes'] : ''),
            ]);

            // Start next stage
            $nextStage->update([
                'status' => 'قيد التنفيذ',
                'started_at' => now(),
                'updated_by' => $user->id,
                'notes' => $validated['additional_notes'],
            ]);

            // Re-enable model events
            PurchaseRequestStage::setEventDispatcher(app('events'));

            // Update purchase request with additional data
            $updateData = [];
            if ($validated['payment_amount']) {
                $updateData['budget_amount'] = $validated['payment_amount'];
            }
            if ($validated['expected_completion_date']) {
                $updateData['expected_completion_date'] = $validated['expected_completion_date'];
            }
            
            // Calculate progress manually to avoid deadlock
            $totalStages = $purchaseRequest->stages()->count();
            $completedStages = $purchaseRequest->stages()->where('status', 'مكتمل')->count();
            $progress = $totalStages > 0 ? round(($completedStages / $totalStages) * 100, 2) : 0.00;
            
            // Update overall status based on progress
            $overallStatus = 'pending';
            if ($progress > 0 && $progress < 100) {
                $overallStatus = 'in_progress';
            } elseif ($progress == 100) {
                $overallStatus = 'completed';
            }
            
            $updateData['progress_percentage'] = $progress;
            $updateData['overall_status'] = $overallStatus;
            
            if (!empty($updateData)) {
                $purchaseRequest->update($updateData);
            }

            DB::commit();

            // Refresh relationships
            $purchaseRequest = $purchaseRequest->fresh(['stages' => function($query) {
                $query->orderBy('stage_order');
            }, 'stages.updatedBy']);

            return response()->json([
                'success' => true,
                'data' => [
                    'purchase_request' => $purchaseRequest,
                    'transitioned_from' => $currentStage->stage_name,
                    'transitioned_to' => $nextStage->stage_name,
                    'requirements_met' => $validated['requirements_met'],
                    'inspection_date' => $validated['inspection_date'],
                    'payment_amount' => $validated['payment_amount'],
                ],
                'message' => "Successfully transitioned from {$currentStage->stage_name} to {$nextStage->stage_name}"
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to transition stage: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get next stage name
     */
    private function getNextStageName($currentStageName)
    {
        $stageOrder = [
            'الحجز' => 'العقد',
            'العقد' => 'الإنجاز',
            'الإنجاز' => 'الاستلام',
            'الاستلام' => null, // Last stage
        ];

        return $stageOrder[$currentStageName] ?? null;
    }

    /**
     * Simple stage transition (alternative method without complex transactions)
     */
    public function simpleTransitionStage(TransitionStageRequest $request, $id)
    {
        $purchaseRequest = PurchaseRequest::with('stages')->findOrFail($id);
        $user = Auth::user();

        $validated = $request->validated();

        // Validate that all requirements are met
        $allRequirementsMet = collect($validated['requirements_met'])->every(function($met) {
            return $met === true;
        });

        if (!$allRequirementsMet) {
            return response()->json([
                'success' => false,
                'message' => 'All requirements must be met before transitioning to next stage'
            ], 400);
        }

        // Get current stage
        $currentStage = $purchaseRequest->stages()
            ->where('stage_name', $validated['current_stage_name'])
            ->first();

        if (!$currentStage) {
            return response()->json([
                'success' => false,
                'message' => 'Current stage not found'
            ], 404);
        }

        // Get next stage
        $nextStageName = $this->getNextStageName($validated['current_stage_name']);
        $nextStage = null;
        
        if ($nextStageName) {
            $nextStage = $purchaseRequest->stages()
                ->where('stage_name', $nextStageName)
                ->first();
        }

        // Update current stage to completed
        $currentStage->timestamps = false; // Disable timestamps to avoid issues
        $currentStage->update([
            'status' => 'مكتمل',
            'completed_at' => now(),
            'updated_by' => $user->id,
            'notes' => $currentStage->notes . ($validated['additional_notes'] ? "\n\nAdditional Notes: " . $validated['additional_notes'] : ''),
        ]);

        // Update next stage if it exists (not the final stage)
        if ($nextStage) {
            $nextStage->timestamps = false;
            $nextStage->update([
                'status' => 'قيد التنفيذ',
                'started_at' => now(),
                'updated_by' => $user->id,
                'notes' => $validated['additional_notes'],
            ]);
        }

        // Update purchase request data
        $updateData = [];
        if ($validated['payment_amount']) {
            $updateData['budget_amount'] = $validated['payment_amount'];
        }
        if ($validated['expected_completion_date']) {
            $updateData['expected_completion_date'] = $validated['expected_completion_date'];
        }
        
        // Calculate and update progress
        $totalStages = 4; // Fixed number of stages
        $completedStages = $purchaseRequest->stages()->where('status', 'مكتمل')->count();
        $progress = round(($completedStages / $totalStages) * 100, 2);
        
        $overallStatus = 'pending';
        if ($progress > 0 && $progress < 100) {
            $overallStatus = 'in_progress';
        } elseif ($progress == 100) {
            $overallStatus = 'completed';
        }
        
        $updateData['progress_percentage'] = $progress;
        $updateData['overall_status'] = $overallStatus;
        
        if (!empty($updateData)) {
            $purchaseRequest->update($updateData);
        }

        // Refresh relationships
        $purchaseRequest = $purchaseRequest->fresh(['stages' => function($query) {
            $query->orderBy('stage_order');
        }, 'stages.updatedBy']);

        // Prepare response data
        $responseData = [
            'purchase_request' => $purchaseRequest,
            'transitioned_from' => $currentStage->stage_name,
            'requirements_met' => $validated['requirements_met'],
            'inspection_date' => $validated['inspection_date'],
            'payment_amount' => $validated['payment_amount'],
        ];

        if ($nextStage) {
            $responseData['transitioned_to'] = $nextStage->stage_name;
            $message = "Successfully transitioned from {$currentStage->stage_name} to {$nextStage->stage_name}";
        } else {
            $responseData['transitioned_to'] = null;
            $responseData['is_final_stage'] = true;
            $message = "Successfully completed final stage: {$currentStage->stage_name}. Purchase request is now completed!";
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
            'message' => $message
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function dashboard(Request $request)
    {
        $totalRequests = PurchaseRequest::count();
        $pendingRequests = PurchaseRequest::where('overall_status', 'pending')->count();
        $inProgressRequests = PurchaseRequest::where('overall_status', 'in_progress')->count();
        $completedRequests = PurchaseRequest::where('overall_status', 'completed')->count();

        // Priority breakdown
        $priorityStats = PurchaseRequest::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        // Recent requests with pagination
        $recentRequestsQuery = PurchaseRequest::with(['property', 'project', 'assignedUser'])
            ->latest();

        // Apply pagination to recent requests
        $perPage = $request->get('recent_per_page', 10);
        $page = $request->get('recent_page', 1);
        
        // Validate pagination parameters
        $perPage = max(1, min(50, (int) $perPage)); // Limit between 1 and 50 for dashboard
        $page = max(1, (int) $page);
        
        $recentRequests = $recentRequestsQuery->paginate($perPage, ['*'], 'recent_page', $page);

        // Progress statistics
        $avgProgress = PurchaseRequest::avg('progress_percentage') ?? 0;

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => [
                    'total_requests' => $totalRequests,
                    'pending_requests' => $pendingRequests,
                    'in_progress_requests' => $inProgressRequests,
                    'completed_requests' => $completedRequests,
                    'average_progress' => round($avgProgress, 2),
                ],
                'priority_breakdown' => $priorityStats,
                'recent_requests' => [
                    'data' => $recentRequests->items(),
                    'pagination' => [
                        'current_page' => $recentRequests->currentPage(),
                        'per_page' => $recentRequests->perPage(),
                        'total' => $recentRequests->total(),
                        'last_page' => $recentRequests->lastPage(),
                        'from' => $recentRequests->firstItem(),
                        'to' => $recentRequests->lastItem(),
                        'has_more_pages' => $recentRequests->hasMorePages(),
                        'links' => [
                            'first' => $recentRequests->url(1),
                            'last' => $recentRequests->url($recentRequests->lastPage()),
                            'prev' => $recentRequests->previousPageUrl(),
                            'next' => $recentRequests->nextPageUrl(),
                        ]
                    ]
                ],
            ],
            'message' => 'Dashboard data retrieved successfully'
        ]);
    }

    /**
     * Get available properties for selection
     */
    public function getProperties(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        
        $query = Property::with('contents')
            ->where('user_id', $tenantId)
            ->where('status', 1);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('contents', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })->orWhere('type', 'like', "%{$search}%");
        }

        // Type filter
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Price range filters
        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Validate pagination parameters
        $perPage = max(1, min(100, (int) $perPage));
        $page = max(1, (int) $page);
        
        $properties = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data
        $transformedData = collect($properties->items())->map(function($property) {
            return [
                'id' => $property->id,
                'price' => $property->price,
                'type' => $property->type,
                'area' => $property->area,
                'beds' => $property->beds,
                'bath' => $property->bath,
                'title' => $property->firstContent ? $property->firstContent->title : 'No Title',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
                'last_page' => $properties->lastPage(),
                'from' => $properties->firstItem(),
                'to' => $properties->lastItem(),
                'has_more_pages' => $properties->hasMorePages(),
                'links' => [
                    'first' => $properties->url(1),
                    'last' => $properties->url($properties->lastPage()),
                    'prev' => $properties->previousPageUrl(),
                    'next' => $properties->nextPageUrl(),
                ]
            ],
            'message' => 'Properties retrieved successfully'
        ]);
    }

    /**
     * Get available projects for selection
     */
    public function getProjects(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        
        $query = Project::with('contents')
            ->where('user_id', $tenantId)
            ->where('published', 1);

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('contents', function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            })->orWhere('developer', 'like', "%{$search}%");
        }

        // Developer filter
        if ($request->has('developer') && $request->developer) {
            $query->where('developer', 'like', "%{$request->developer}%");
        }

        // Price range filters
        if ($request->has('min_price') && $request->min_price !== null) {
            $query->where('min_price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price !== null) {
            $query->where('max_price', '<=', $request->max_price);
        }

        // Completion date filter
        if ($request->has('completion_date_from') && $request->completion_date_from) {
            $query->whereDate('completion_date', '>=', $request->completion_date_from);
        }

        if ($request->has('completion_date_to') && $request->completion_date_to) {
            $query->whereDate('completion_date', '<=', $request->completion_date_to);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Validate pagination parameters
        $perPage = max(1, min(100, (int) $perPage));
        $page = max(1, (int) $page);
        
        $projects = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data
        $transformedData = collect($projects->items())->map(function($project) {
            return [
                'id' => $project->id,
                'min_price' => $project->min_price,
                'max_price' => $project->max_price,
                'units' => $project->units,
                'completion_date' => $project->completion_date,
                'developer' => $project->developer,
                'title' => $project->firstContent ? $project->firstContent->title : 'No Title',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
                'last_page' => $projects->lastPage(),
                'from' => $projects->firstItem(),
                'to' => $projects->lastItem(),
                'has_more_pages' => $projects->hasMorePages(),
                'links' => [
                    'first' => $projects->url(1),
                    'last' => $projects->url($projects->lastPage()),
                    'prev' => $projects->previousPageUrl(),
                    'next' => $projects->nextPageUrl(),
                ]
            ],
            'message' => 'Projects retrieved successfully'
        ]);
    }

    /**
     * Get available staff for assignment
     */
    public function getStaff(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        
        $query = \App\Models\User::where('status', 1)
            ->where(function($query) use ($tenantId) {
                $query->where('id', $tenantId) // Include the tenant themselves
                      ->orWhere('tenant_id', $tenantId); // Include their employees
            })
            ->select('id', 'first_name', 'last_name', 'email', 'account_type');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Role filter
        if ($request->has('role') && $request->role) {
            $query->where('account_type', $request->role);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'first_name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        
        // Validate pagination parameters
        $perPage = max(1, min(100, (int) $perPage));
        $page = max(1, (int) $page);
        
        $staff = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform the data
        $transformedData = collect($staff->items())->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name,
                'email' => $user->email,
                'role' => $user->account_type,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformedData,
            'pagination' => [
                'current_page' => $staff->currentPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
                'last_page' => $staff->lastPage(),
                'from' => $staff->firstItem(),
                'to' => $staff->lastItem(),
                'has_more_pages' => $staff->hasMorePages(),
                'links' => [
                    'first' => $staff->url(1),
                    'last' => $staff->url($staff->lastPage()),
                    'prev' => $staff->previousPageUrl(),
                    'next' => $staff->nextPageUrl(),
                ]
            ],
            'message' => 'Staff retrieved successfully'
        ]);
    }
}