<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TenantWebsite\PropertyPublicResource;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Services\PropertyTranslationService;
use Illuminate\Http\Request;

class PropertyPreviewController extends Controller
{
    use ResolvesTenant;

    public function __construct(private PropertyTranslationService $translator) {}

    public function show(Request $request, string $tenantId, string $token)
    {
        $tenant = $this->resolveTenant($request, $tenantId);
        $property = Property::with(['category', 'contents', 'galleryImages', 'proertyAmenities.amenity', 'UserPropertyCharacteristics.UserFacade', 'building', 'project.contents'])
            ->where('user_id', $tenant->id)->where('share_token', $token)->firstOrFail();
        $states = $property->contents->pluck('state_id')->filter()->unique()->values();
        $districts = UserDistrict::with('city')->whereIn('id', $states)->get()->keyBy('id');
        return response()->json(['property' => PropertyPublicResource::toDetailArray($property, 0, $districts, $this->translator)]);
    }
}
