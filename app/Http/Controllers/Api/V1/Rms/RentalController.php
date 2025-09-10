<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\RentalService;

class RentalController extends Controller
{
    protected $rentalService;

    public function __construct(RentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    public function index(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $this->rentalService->listRentals($request)
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_full_name' => 'required|string|max:150',
            'tenant_phone' => 'required|string|max:32',
            'tenant_email' => 'nullable|email',
            'tenant_job_title' => 'nullable|string|max:120',
            'tenant_social_status' => 'nullable|in:single,married,divorced,widowed,other',
            'tenant_national_id' => 'nullable|string|max:20',
            'property_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'unit_label' => 'nullable|string|max:100',
            'property_number' => 'nullable|string|max:100',
            'move_in_date' => 'nullable|date',
            'rental_period_months' => 'nullable|integer',
            'paying_plan' => 'nullable|in:monthly,quarterly,semi_annual,annual',
            'base_rent_amount' => 'nullable|numeric',
            'currency' => 'nullable|string|size:3',
            'deposit_amount' => 'nullable|numeric',
            'platform_fee' => 'nullable|numeric|min:0',
            'water_fee' => 'nullable|numeric|min:0',
            'office_commission_type' => 'nullable|in:percentage,amount',
            'office_commission_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $rental = $this->rentalService->createRental(auth()->id(), $data);

        return response()->json(['status' => true, 'data' => $rental], 201);
    }

    public function show($id)
    {
        $rental = $this->rentalService->getRentalDetails(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $rental]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'tenant_full_name', 'tenant_phone', 'tenant_email', 'tenant_job_title',
            'tenant_social_status', 'tenant_national_id', 'property_id', 'project_id', 'unit_label', 'property_number',
            'move_in_date', 'rental_period_months', 'paying_plan',
            'base_rent_amount', 'currency', 'deposit_amount', 'platform_fee', 'water_fee', 
            'office_commission_type', 'office_commission_value', 'notes'
        ]);

        $regenerate = $request->boolean('regenerate_schedule', false);
        $rental = $this->rentalService->updateRental(auth()->id(), $id, $data, $regenerate);

        return response()->json(['status' => true, 'data' => $rental]);
    }

    public function destroy($id)
    {
        $this->rentalService->deleteRental(auth()->id(), $id);
        return response()->json(null, 204);
    }

    public function propertyDetails($id)
    {
        $details = $this->rentalService->getPropertyDetails(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $details]);
    }
}
