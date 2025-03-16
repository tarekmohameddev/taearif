<?php

namespace App\Http\Controllers\Api\project;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "data" => [
                "projects" => [
                    [
                        "id" => 1,
                        "name" => "Skyline Residences",
                        "location" => "Downtown, New York",
                        "price" => "From $750,000",
                        "status" => "Pre-construction",
                        "completion_date" => "2025",
                        "units" => 120,
                        "developer" => "Urban Development Group",
                        "description" => "Luxury high-rise condominiums with panoramic city views",
                        "thumbnail" => "/storage/projects/skyline.jpg",
                        "featured" => true,
                        "created_at" => now(),
                        "updated_at" => now(),
                    ]
                ],
                "pagination" => [
                    "total" => 30,
                    "per_page" => 10,
                    "current_page" => 1,
                    "last_page" => 3,
                    "from" => 1,
                    "to" => 10
                ]
            ]
        ]);
    }

    /**
     * Get a single project.
     */
    public function show($id): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "data" => [
                "project" => [
                    "id" => $id,
                    "name" => "Skyline Residences",
                    "location" => "Downtown, New York",
                    "price" => "From $750,000",
                    "status" => "Pre-construction",
                    "completion_date" => "2025",
                    "units" => 120,
                    "developer" => "Urban Development Group",
                    "description" => "Luxury high-rise condominiums with panoramic city views",
                    "thumbnail" => "/storage/projects/skyline.jpg",
                    "featured" => true,
                    "gallery" => [
                        "/storage/projects/skyline-1.jpg",
                        "/storage/projects/skyline-2.jpg",
                        "/storage/projects/skyline-3.jpg"
                    ],
                    "amenities" => [
                        "Swimming Pool",
                        "Fitness Center",
                        "Rooftop Garden",
                        "24/7 Security"
                    ],
                    "created_at" => now(),
                    "updated_at" => now(),
                ]
            ]
        ]);
    }

    /**
     * Store a new project.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Project created successfully",
            "data" => [
                "project" => [
                    "id" => 10,
                    "name" => $request->input('name', "New Project Name"),
                    "location" => $request->input('location', "Project Location"),
                    "price" => "From $500,000",
                    "status" => "Pre-construction",
                    "completion_date" => "2026",
                    "units" => 85,
                    "developer" => "Developer Name",
                    "description" => "Project description...",
                    "thumbnail" => "/storage/projects/new-project.jpg",
                    "featured" => false,
                    "gallery" => [
                        "/storage/projects/new-project-1.jpg",
                        "/storage/projects/new-project-2.jpg",
                        "/storage/projects/new-project-3.jpg"
                    ],
                    "amenities" => [
                        "Swimming Pool",
                        "Fitness Center",
                        "Rooftop Garden"
                    ],
                    "created_at" => now(),
                    "updated_at" => now(),
                ]
            ]
        ]);
    }

    /**
     * Update an existing project.
     */
    public function update(Request $request, $id): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Project updated successfully",
            "data" => [
                "project" => [
                    "id" => $id,
                    "name" => "Updated Project Name",
                    "location" => "Updated Location",
                    "price" => "From $550,000",
                    "status" => "Under Construction",
                    "completion_date" => "2025",
                    "units" => 90,
                    "developer" => "Updated Developer",
                    "description" => "Updated project description...",
                    "thumbnail" => "/storage/projects/updated-project.jpg",
                    "featured" => true,
                    "gallery" => [
                        "/storage/projects/new-project-1.jpg",
                        "/storage/projects/new-project-2.jpg",
                        "/storage/projects/new-project-3.jpg",
                        "/storage/projects/updated-project-1.jpg",
                        "/storage/projects/updated-project-2.jpg"
                    ],
                    "amenities" => [
                        "Swimming Pool",
                        "Fitness Center",
                        "Rooftop Garden",
                        "24/7 Security"
                    ],
                    "created_at" => now(),
                    "updated_at" => now(),
                ]
            ]
        ]);
    }

    /**
     * Delete a project.
     */
    public function destroy($id): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Project deleted successfully"
        ]);
    }

    /**
     * Toggle project featured status.
     */
    public function toggleFeatured($id): JsonResponse
    {
        return response()->json([
            "status" => "success",
            "message" => "Project featured status updated",
            "data" => [
                "featured" => true
            ]
        ]);
    }
}