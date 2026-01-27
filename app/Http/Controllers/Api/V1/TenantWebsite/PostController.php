<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Http\Resources\Api\blog\PostListResource;
use App\Http\Resources\Api\blog\PostResource;
use App\Models\Api\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request, string $tenantId): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);

        $query = Post::where('user_id', $tenant->id)
            ->where('status', 'published')
            ->with(['thumbnail', 'categories'])
            ->orderByDesc('published_at');

        $perPage = min((int) $request->query('per_page', 20), 50);
        $posts = $query->paginate($perPage);

        return response()->json([
            'posts' => PostListResource::collection($posts),
            'pagination' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, string $tenantId, string $slug): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);

        $post = Post::where('user_id', $tenant->id)
            ->where('status', 'published')
            ->where('slug', $slug)
            ->with(['categories', 'media', 'user', 'thumbnail'])
            ->firstOrFail();

        return (new PostResource($post))->response();
    }
}
