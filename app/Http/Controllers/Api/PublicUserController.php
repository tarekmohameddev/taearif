<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\Property;


class PublicUserController extends Controller
{
    //
    public function show($id)
    {
        $user = User::findOrFail($id);

        $properties = Property::with([
            'project',
            'proertyAmenities.amenity',
            'userPropertyCharacteristics',
            'contents',
            'galleryImages'
        ])
        ->where('user_id', $user->id)
        ->get();

        $formattedProperties = $properties->map(function ($property) {
            $content = $property->contents->first();

            return [
                'id' => $property->id,
                'project_id' => $property->project_id,
                'payment_method' => $property->payment_method,
                'title' => optional($content)->title ?? '',
                'address' => optional($content)->address ?? '',
                'price' => $property->price ?? '0.00',
                'pricePerMeter' => $property->pricePerMeter,
                'purpose' => $property->purpose,
                'type' => $property->type ?? '',
                'beds' => $property->beds,
                'bath' => $property->bath,
                'area' => $property->area,
                'features' => $property->features ?? [],
                'status' => (int) $property->status,
                'featured_image' => asset($property->featured_image),
                'floor_planning_image' => collect($property->floor_planning_image)->map(fn($img) => asset($img))->toArray(),
                'gallery' => $property->galleryImages->pluck('image')->map(fn($image) => asset($image))->toArray(),
                'description' => optional($content)->description ?? '',
                'latitude' => $property->latitude ? (float) $property->latitude : null,
                'longitude' => $property->longitude ? (float) $property->longitude : null,
                'featured' => (bool) $property->featured,
                'city_id' => optional($content)->city_id,
                'state_id' => optional($content)->state_id,
                'category_id' => $property->category_id,
                'size' => $property->size ?? null,
                'faqs' => $property->faqs ?? [],
            ];
        });

        return ["Properties"=> $formattedProperties->values()->all()];
    }

}
