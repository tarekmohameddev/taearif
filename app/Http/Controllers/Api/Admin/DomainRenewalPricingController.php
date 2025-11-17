<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Models\DomainRenewalPricing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DomainRenewalPricingController extends BaseController
{
    /**
     * Display a listing of pricing rules with filters.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = DomainRenewalPricing::query()->with('customDomain');

            // Filters
            if ($request->has('custom_domain_id')) {
                $query->where('custom_domain_id', $request->input('custom_domain_id'));
            }

            if ($request->has('registrar')) {
                $query->where('registrar', $request->input('registrar'));
            }

            if ($request->has('period_key')) {
                $query->where('period_key', $request->input('period_key'));
            }

            if ($request->has('active')) {
                $query->where('active', filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN));
            }

            $perPage = min($request->input('per_page', 20), 100);
            $pricings = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse(
                \App\Http\Resources\Admin\DomainRenewalPricingCollection::make($pricings),
                'Pricing rules retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to retrieve pricing rules.',
                'PRICING_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Store a newly created pricing rule.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'custom_domain_id' => 'nullable|exists:user_custom_domains,id',
                'registrar' => 'nullable|string|max:100',
                'period_key' => 'required|string|in:1_year,2_years',
                'label' => 'required|string|max:255',
                'years' => 'required|integer|min:1',
                'price' => 'required|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'active' => 'boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after:starts_at',
                'supersede_existing' => 'boolean',
            ]);

            // Require starts_at when supersede_existing is true
            if (!empty($validated['supersede_existing']) && empty($validated['starts_at'])) {
                throw ValidationException::withMessages([
                    'starts_at' => ['starts_at is required when supersede_existing is true.'],
                ]);
            }

            // Validate that starts_at is in the future when supersede_existing is true
            if (!empty($validated['supersede_existing']) && !empty($validated['starts_at'])) {
                $startsAt = \Carbon\Carbon::parse($validated['starts_at']);
                if ($startsAt->isPast()) {
                    throw ValidationException::withMessages([
                        'starts_at' => ['starts_at must be in the future when supersede_existing is true.'],
                    ]);
                }
            }

            // Business rule: at least one of custom_domain_id or registrar must be provided
            if (empty($validated['custom_domain_id']) && empty($validated['registrar'])) {
                throw ValidationException::withMessages([
                    'custom_domain_id' => ['Either custom_domain_id or registrar must be provided.'],
                    'registrar' => ['Either custom_domain_id or registrar must be provided.'],
                ]);
            }

            // Prevent duplicate active rules (unless date ranges don't overlap or supersede_existing is true)
            $existing = null;
            if (!empty($validated['custom_domain_id'])) {
                $existing = DomainRenewalPricing::where('custom_domain_id', $validated['custom_domain_id'])
                    ->where('period_key', $validated['period_key'])
                    ->where('active', true)
                    ->first();
            } elseif (!empty($validated['registrar'])) {
                $existing = DomainRenewalPricing::where('registrar', $validated['registrar'])
                    ->where('period_key', $validated['period_key'])
                    ->where('active', true)
                    ->first();
            }

            if ($existing) {
                // Check if ranges overlap
                $hasOverlap = $this->hasOverlap(
                    $validated['starts_at'] ?? null,
                    $validated['ends_at'] ?? null,
                    $existing
                );

                if ($hasOverlap) {
                    // If supersede_existing is true and starts_at is provided, truncate existing rule
                    if (!empty($validated['supersede_existing']) && !empty($validated['starts_at'])) {
                        $newStartsAt = \Carbon\Carbon::parse($validated['starts_at']);
                        
                        // Validate that new starts_at is after existing starts_at (if existing has one)
                        if ($existing->starts_at) {
                            $existingStartsAt = \Carbon\Carbon::parse($existing->starts_at);
                            if ($newStartsAt->lte($existingStartsAt)) {
                                throw ValidationException::withMessages([
                                    'starts_at' => ['starts_at must be after the existing rule\'s starts_at when superseding.'],
                                ]);
                            }
                        }

                        // Use transaction to ensure atomicity
                        DB::transaction(function () use ($existing, $newStartsAt) {
                            // Set existing rule's ends_at to one second before new rule starts
                            $existing->ends_at = $newStartsAt->copy()->subSecond();
                            $existing->save();
                        });
                    } else {
                        // No supersede, throw error
                        $scope = !empty($validated['custom_domain_id']) ? 'domain' : 'registrar';
                        throw ValidationException::withMessages([
                            'period_key' => ["An active pricing rule already exists for this {$scope} and period with overlapping date range."],
                        ]);
                    }
                }
            }

            // Remove supersede_existing from validated data (it's not a database field)
            unset($validated['supersede_existing']);

            $pricing = DomainRenewalPricing::create($validated);
            $pricing->load('customDomain');

            return $this->successResponse(
                new \App\Http\Resources\Admin\DomainRenewalPricingResource($pricing),
                'Pricing rule created successfully',
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to create pricing rule.',
                'PRICING_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified pricing rule.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $pricing = DomainRenewalPricing::with('customDomain')->findOrFail($id);

            return $this->successResponse(
                new \App\Http\Resources\Admin\DomainRenewalPricingResource($pricing),
                'Pricing rule retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                'Pricing rule not found.',
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to retrieve pricing rule.',
                'PRICING_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Update the specified pricing rule.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $pricing = DomainRenewalPricing::findOrFail($id);

            $validated = $request->validate([
                'custom_domain_id' => 'nullable|exists:user_custom_domains,id',
                'registrar' => 'nullable|string|max:100',
                'period_key' => 'sometimes|string|in:1_year,2_years',
                'label' => 'sometimes|string|max:255',
                'years' => 'sometimes|integer|min:1',
                'price' => 'sometimes|numeric|min:0',
                'currency' => 'nullable|string|max:10',
                'active' => 'boolean',
                'starts_at' => 'nullable|date',
                'ends_at' => 'nullable|date|after:starts_at',
            ]);

            // Business rule: at least one of custom_domain_id or registrar must be provided
            $customDomainId = $validated['custom_domain_id'] ?? $pricing->custom_domain_id;
            $registrar = $validated['registrar'] ?? $pricing->registrar;

            if (empty($customDomainId) && empty($registrar)) {
                throw ValidationException::withMessages([
                    'custom_domain_id' => ['Either custom_domain_id or registrar must be provided.'],
                    'registrar' => ['Either custom_domain_id or registrar must be provided.'],
                ]);
            }

            // Prevent duplicate active rules (excluding current record)
            if (!empty($customDomainId) && isset($validated['period_key'])) {
                $periodKey = $validated['period_key'] ?? $pricing->period_key;
                $existing = DomainRenewalPricing::where('id', '!=', $id)
                    ->where('custom_domain_id', $customDomainId)
                    ->where('period_key', $periodKey)
                    ->where('active', true)
                    ->where(function ($q) use ($validated, $pricing) {
                        $startsAt = $validated['starts_at'] ?? $pricing->starts_at;
                        $endsAt = $validated['ends_at'] ?? $pricing->ends_at;
                        $q->whereNull('starts_at')
                            ->orWhere(function ($subQ) use ($startsAt) {
                                $subQ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $startsAt ?? now());
                            });
                    })
                    ->where(function ($q) use ($validated, $pricing) {
                        $endsAt = $validated['ends_at'] ?? $pricing->ends_at;
                        if ($endsAt) {
                            $q->whereNull('starts_at')
                                ->orWhere('starts_at', '<=', $endsAt);
                        }
                    })
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'period_key' => ['An active pricing rule already exists for this domain and period with overlapping date range.'],
                    ]);
                }
            } elseif (!empty($registrar) && isset($validated['period_key'])) {
                $periodKey = $validated['period_key'] ?? $pricing->period_key;
                $existing = DomainRenewalPricing::where('id', '!=', $id)
                    ->where('registrar', $registrar)
                    ->where('period_key', $periodKey)
                    ->where('active', true)
                    ->where(function ($q) use ($validated, $pricing) {
                        $startsAt = $validated['starts_at'] ?? $pricing->starts_at;
                        $q->whereNull('starts_at')
                            ->orWhere(function ($subQ) use ($startsAt) {
                                $subQ->whereNull('ends_at')
                                    ->orWhere('ends_at', '>=', $startsAt ?? now());
                            });
                    })
                    ->where(function ($q) use ($validated, $pricing) {
                        $endsAt = $validated['ends_at'] ?? $pricing->ends_at;
                        if ($endsAt) {
                            $q->whereNull('starts_at')
                                ->orWhere('starts_at', '<=', $endsAt);
                        }
                    })
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'period_key' => ['An active pricing rule already exists for this registrar and period with overlapping date range.'],
                    ]);
                }
            }

            $pricing->update($validated);
            $pricing->load('customDomain');

            return $this->successResponse(
                new \App\Http\Resources\Admin\DomainRenewalPricingResource($pricing),
                'Pricing rule updated successfully'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                'Pricing rule not found.',
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to update pricing rule.',
                'PRICING_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Check if two date ranges overlap.
     * Treats null as unbounded (null starts_at = -infinity, null ends_at = +infinity).
     *
     * @param  string|null  $newStartsAt
     * @param  string|null  $newEndsAt
     * @param  DomainRenewalPricing  $existing
     * @return bool
     */
    private function hasOverlap(?string $newStartsAt, ?string $newEndsAt, DomainRenewalPricing $existing): bool
    {
        $existingStartsAt = $existing->starts_at ? \Carbon\Carbon::parse($existing->starts_at) : null;
        $existingEndsAt = $existing->ends_at ? \Carbon\Carbon::parse($existing->ends_at) : null;
        $newStarts = $newStartsAt ? \Carbon\Carbon::parse($newStartsAt) : null;
        $newEnds = $newEndsAt ? \Carbon\Carbon::parse($newEndsAt) : null;

        // If existing has no start date, it's active from the beginning
        // If existing has no end date, it's active forever
        // Same logic applies to new range

        // Check overlap: ranges overlap if one starts before the other ends
        $existingStart = $existingStartsAt ?? \Carbon\Carbon::minValue();
        $existingEnd = $existingEndsAt ?? \Carbon\Carbon::maxValue();
        $newStart = $newStarts ?? \Carbon\Carbon::minValue();
        $newEnd = $newEnds ?? \Carbon\Carbon::maxValue();

        return $existingStart->lte($newEnd) && $newStart->lte($existingEnd);
    }

    /**
     * Remove the specified pricing rule.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $pricing = DomainRenewalPricing::findOrFail($id);
            $pricing->delete();

            return $this->successResponse(
                null,
                'Pricing rule deleted successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse(
                'Pricing rule not found.',
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to delete pricing rule.',
                'PRICING_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }
}
