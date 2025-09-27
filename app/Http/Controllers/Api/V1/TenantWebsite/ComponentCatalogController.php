<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;

class ComponentCatalogController extends Controller
{
    public function index()
    {
        $catalog = [
            ['id' => 'hero', 'type' => 'hero', 'name' => 'Hero', 'placements' => ['homepage'], 'schema' => ['data' => []]],
            ['id' => 'header', 'type' => 'header', 'name' => 'Header', 'placements' => ['any'], 'schema' => ['data' => []]],
            ['id' => 'footer', 'type' => 'footer', 'name' => 'Footer', 'placements' => ['any'], 'schema' => ['data' => []]],
        ];
        return response()->json($catalog);
    }
}


