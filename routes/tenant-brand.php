<?php

use App\Http\Controllers\Api\V1\TenantWebsite\TenantBrandController;
use App\Http\Middleware\DisableDebugbarForTenantBrand;
use Illuminate\Support\Facades\Route;

Route::get('v1/tenant-website/{tenantId}/brand', [TenantBrandController::class, 'show'])
    ->where('tenantId', '[^/]+')
    ->middleware(DisableDebugbarForTenantBrand::class);
