<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PropertyShareController extends Controller
{
    public function store(Request $request, int $id)
    {
        $property = $this->property($request, $id);
        if (! $property->share_token) {
            do { $token = Str::random(64); } while (Property::where('share_token', $token)->exists());
            $property->forceFill(['share_token' => $token])->save();
        }

        return response()->json(['status' => 'success', 'data' => [
            'url' => $this->previewUrl($property), 'share_token' => $property->share_token,
        ]]);
    }

    public function destroy(Request $request, int $id)
    {
        $this->property($request, $id)->forceFill(['share_token' => null])->save();
        return response()->json(['status' => 'success']);
    }

    private function property(Request $request, int $id): Property
    {
        $tenantId = (int) $request->user()->tenantOwnerId();
        $ids = array_merge([$tenantId], User::where('tenant_id', $tenantId)->pluck('id')->all());
        return Property::whereKey($id)->whereIn('user_id', $ids)->firstOrFail();
    }

    private function previewUrl(Property $property): string
    {
        $domain = ApiDomainSetting::where('user_id', $property->user_id)->preferredActive()->first()?->custom_name;
        $base = $domain ? 'https://' . trim($domain, '/') : 'https://' . $property->user->username . '.taearif.com';
        return rtrim($base, '/') . '/property-preview/' . $property->share_token;
    }
}
