<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'properties' => [
                    [
                        'id' => 1,
                        'title' => 'Modern Apartment with City View',
                        'address' => '123 Main St, New York, NY 10001',
                        'price' => 750000,
                        'type' => 'Apartment',
                        'bedrooms' => 2,
                        'bathrooms' => 2,
                        'size' => 1200,
                        'features' => ['Balcony', 'Gym', 'Parking', 'Doorman'],
                        'status' => 'For Sale',
                        'thumbnail' => '/storage/properties/apartment1.jpg',
                        'featured' => true,
                        'created_at' => '2023-10-15T10:00:00.000000Z',
                        'updated_at' => '2023-10-15T10:00:00.000000Z'
                    ]
                ],
                'pagination' => [
                    'total' => 50,
                    'per_page' => 10,
                    'current_page' => 1,
                    'last_page' => 5,
                    'from' => 1,
                    'to' => 10
                ]
            ]
        ]);
        
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'property' => [
                    'id' => 1,
                    'title' => 'Modern Apartment with City View',
                    'address' => '123 Main St, New York, NY 10001',
                    'price' => 750000,
                    'type' => 'Apartment',
                    'bedrooms' => 2,
                    'bathrooms' => 2,
                    'size' => 1200,
                    'features' => ['Balcony', 'Gym', 'Parking', 'Doorman'],
                    'status' => 'For Sale',
                    'thumbnail' => '/storage/properties/apartment1.jpg',
                    'featured' => true,
                    'gallery' => [
                        '/storage/properties/apartment1-1.jpg',
                        '/storage/properties/apartment1-2.jpg',
                        '/storage/properties/apartment1-3.jpg'
                    ],
                    'description' => 'Detailed property description...',
                    'location' => [
                        'latitude' => 40.7128,
                        'longitude' => -74.0060
                    ],
                    'created_at' => '2023-10-15T10:00:00.000000Z',
                    'updated_at' => '2023-10-15T10:00:00.000000Z'
                ]
            ]
        ]);
        
    }

    public function store(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Property created successfully',
            'data' => [
                'property' => [
                    'id' => 10,
                    'title' => 'New Property Title',
                    'address' => 'Property Address',
                    'price' => 550000,
                    'type' => 'House',
                    'bedrooms' => 3,
                    'bathrooms' => 2.5,
                    'size' => 1800,
                    'features' => ['Garden', 'Pool', 'Garage', 'Fireplace'],
                    'status' => 'For Sale',
                    'thumbnail' => '/storage/properties/new-property.jpg',
                    'featured' => false,
                    'gallery' => [
                        '/storage/properties/new-property-1.jpg',
                        '/storage/properties/new-property-2.jpg',
                        '/storage/properties/new-property-3.jpg'
                    ],
                    'description' => 'Detailed property description...',
                    'location' => [
                        'latitude' => 40.7128,
                        'longitude' => -74.0060
                    ],
                    'created_at' => '2023-11-25T12:00:00.000000Z',
                    'updated_at' => '2023-11-25T12:00:00.000000Z'
                ]
            ]
        ]);
        
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Property updated successfully',
            'data' => [
                'property' => [
                    'id' => 10,
                    'title' => 'Updated Property Title',
                    'address' => 'Updated Address',
                    'price' => 600000,
                    'type' => 'House',
                    'bedrooms' => 4,
                    'bathrooms' => 3,
                    'size' => 2000,
                    'features' => ['Garden', 'Pool', 'Garage', 'Fireplace', 'Newly Renovated'],
                    'status' => 'For Sale',
                    'thumbnail' => '/storage/properties/updated-property.jpg',
                    'featured' => true,
                    'gallery' => [
                        '/storage/properties/new-property-1.jpg',
                        '/storage/properties/new-property-2.jpg',
                        '/storage/properties/new-property-3.jpg',
                        '/storage/properties/updated-property-1.jpg',
                        '/storage/properties/updated-property-2.jpg'
                    ],
                    'description' => 'Updated property description...',
                    'location' => [
                        'latitude' => 40.7128,
                        'longitude' => -74.0060
                    ],
                    'created_at' => '2023-11-25T12:00:00.000000Z',
                    'updated_at' => '2023-11-25T13:00:00.000000Z'
                ]
            ]
        ]);
        
    }

    public function destroy($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Property deleted successfully'
        ]);
    }

    public function toggleFeatured($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Property featured status updated',
            'data' => ['featured' => false]
        ]);
    }

    public function toggleFavorite($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Property favorite status updated',
            'data' => ['is_favorite' => false]
        ]);
    }
}
