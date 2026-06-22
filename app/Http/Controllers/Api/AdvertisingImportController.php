<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Advertising\StoreAdvertisingImportRequest;
use App\Services\Advertising\AdvertisingImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdvertisingImportController extends Controller
{
    public function __construct(
        private readonly AdvertisingImportService $importService,
    ) {}

    public function storeFromLink(StoreAdvertisingImportRequest $request): JsonResponse
    {
        $user = Auth::user();
        $import = $this->importService->createFromUrl(
            $user->id,
            $request->input('url'),
            $request->input('platform'),
        );

        return response()->json([
            'status' => 'success',
            'data' => ['import_id' => $import->id],
        ], 201);
    }
}
