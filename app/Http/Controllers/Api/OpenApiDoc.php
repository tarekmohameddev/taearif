<?php

namespace App\Http\Controllers\Api;

/**
 * Base OpenAPI documentation for the Main API (routes/api.php).
 * This file is scanned by L5-Swagger. Admin API (routes/admin-api.php) is documented separately.
 *
 * @OA\Info(
 *     title="Main API",
 *     version="1.0.0",
 *     description="Tenant/user API defined in routes/api.php. Authentication: Laravel Sanctum (Bearer token). Admin Dashboard API is at docs/admin-dashboard-api-v1/openapi.json"
 * )
 * @OA\Server(
 *     url="/api",
 *     description="Main API base path"
 * )
 * @OA\Tag(name="Auth", description="Login, register, user profile")
 * @OA\Tag(name="Properties", description="Properties CRUD and filters")
 * @OA\Tag(name="Customers", description="Customers and CRM")
 * @OA\Tag(name="Projects", description="Projects CRUD")
 * @OA\Tag(name="CRM", description="CRM stages, priorities, cards, requests")
 * @OA\Tag(name="Analytics", description="Dashboard and analytics")
 * @OA\Tag(name="Content", description="Content sections, footer, menu, about")
 * @OA\Tag(name="Settings", description="Theme, payment, domain, side menus")
 * @OA\Tag(name="RMS", description="Rental Management System")
 * @OA\Tag(name="PMS", description="Purchase Management System")
 * @OA\Tag(name="Whatsapp", description="WhatsApp numbers, conversations, templates")
 * @OA\Tag(name="Sms", description="SMS campaigns and templates")
 * @OA\Tag(name="Marketing", description="Marketing channels and credits")
 * @OA\Tag(name="Tenant Website", description="Tenant website pages, properties, publish")
 * @OA\Tag(name="Customers Hub", description="V2 Customers Hub requests, pipeline, analytics")
 */
class OpenApiDoc
{
    // No logic; this class exists only for OpenAPI annotations.
}
