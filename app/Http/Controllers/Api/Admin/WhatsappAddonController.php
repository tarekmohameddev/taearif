<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappAddon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsappAddonController extends Controller
{
    /**
     * List all WhatsApp Add-on requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = WhatsappAddon::with(['whatsappUser.user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $addons = $query->orderBy('created_at', 'desc')->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $addons
        ]);
    }
}
