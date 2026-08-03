<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\PropertyExternalLink;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Manage external listing links for properties.
 * Agents use these endpoints to attach Bayut / Aqar / etc. URLs to properties
 * so the bot can resolve inbound listing links to the correct internal property.
 */
class PropertyExternalLinkController extends BaseApiController
{
    /**
     * List all external links for a property.
     */
    public function index(int $propertyId): JsonResponse
    {
        $property = Property::where('user_id', auth()->id())->findOrFail($propertyId);

        $links = PropertyExternalLink::where('property_id', $property->id)
            ->orderByDesc('created_at')
            ->get(['id', 'platform', 'url', 'label', 'active', 'created_at']);

        return response()->json(['data' => $links]);
    }

    /**
     * Attach a new external link to a property.
     */
    public function store(Request $request, int $propertyId): JsonResponse
    {
        $property = Property::where('user_id', auth()->id())->findOrFail($propertyId);

        $validated = $request->validate([
            'platform' => 'required|string|max:60',
            'url'      => 'required|url|max:2048',
            'label'    => 'nullable|string|max:120',
            'active'   => 'boolean',
        ]);

        $link = PropertyExternalLink::create([
            'property_id' => $property->id,
            'user_id'     => (int) auth()->id(),
            'platform'    => $validated['platform'],
            'url'         => rtrim($validated['url'], '/'),
            'label'       => $validated['label'] ?? null,
            'active'      => $validated['active'] ?? true,
        ]);

        Cache::forget('listing.links.' . $property->id);

        return response()->json($link, 201);
    }

    /**
     * Update a link (e.g. change label or toggle active).
     */
    public function update(Request $request, int $propertyId, int $linkId): JsonResponse
    {
        $property = Property::where('user_id', auth()->id())->findOrFail($propertyId);
        $link     = PropertyExternalLink::where('property_id', $property->id)->findOrFail($linkId);

        $validated = $request->validate([
            'platform' => 'sometimes|string|max:60',
            'url'      => 'sometimes|url|max:2048',
            'label'    => 'sometimes|nullable|string|max:120',
            'active'   => 'sometimes|boolean',
        ]);

        if (isset($validated['url'])) {
            $validated['url'] = rtrim($validated['url'], '/');
        }

        $link->update($validated);
        Cache::forget('listing.links.' . $property->id);

        return response()->json(['success' => true, 'link' => $link]);
    }

    /**
     * Delete an external link.
     */
    public function destroy(int $propertyId, int $linkId): JsonResponse
    {
        $property = Property::where('user_id', auth()->id())->findOrFail($propertyId);
        $link     = PropertyExternalLink::where('property_id', $property->id)->findOrFail($linkId);

        $link->delete();
        Cache::forget('listing.links.' . $property->id);

        return response()->json(['success' => true]);
    }
}
