<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Reservation;
use App\Models\User\RealestateManagement\Property;
use App\Http\Requests\TenantWebsite\Reservation\StoreRequest;

class ReservationController extends Controller
{
    protected function resolveTenant(string $tenantId): User
    {
        return User::where('username', $tenantId)->firstOrFail();
    }

    public function store(StoreRequest $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);

        $validated = $request->validated();
        $propertySlug = $validated['propertySlug'];

        $property = Property::query()
            ->where('user_id', $tenant->id)
            ->where('status', 1)
            ->whereHas('contents', function ($q) use ($propertySlug) {
                $q->where('slug', $propertySlug);
            })
            ->firstOrFail();

        $purpose = $property->purpose;
        $type = match ($purpose) {
            'rent', 'rented' => 'rent',
            'sale', 'sold' => 'buy',
            default => 'rent',
        };

        $reservation = Reservation::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'type' => $type,
            'status' => 'pending',
            'customer_name' => $validated['customerName'],
            'customer_phone' => $validated['customerPhone'],
            'desired_date' => $validated['desiredDate'] ?? null,
            'notes' => $validated['message'] ?? null,
            'metadata' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'property_slug' => $propertySlug,
            ],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $reservation->id,
                'status' => $reservation->status,
            ],
        ], 201);
    }
}


