<?php

namespace App\Http\Controllers\Api\blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\blog\StorePostRequest;
use App\Http\Requests\Api\blog\UpdatePostRequest;
use App\Http\Resources\Api\blog\PostListResource;
use App\Http\Resources\Api\blog\PostResource;
use App\Models\Api\Media;
use App\Models\Api\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $query = Post::where('user_id', $userId)
            ->with('thumbnail');

        // Filter by status if provided (draft, published, or all)
        $status = $request->input('status');
        if ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'published') {
            $query->where('status', 'published');
        }
        // If no status or 'all', show both draft and published

        // Order by published_at for published posts, created_at for drafts
        $query->orderByDesc($status === 'draft' ? 'created_at' : 'published_at')
            ->orderByDesc('created_at');

        $posts = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => PostListResource::collection($posts),
            'pagination' => [
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $userId = $request->user()->id;
        
        $post = Post::where('user_id', $userId)
            ->where('slug', $slug)
            ->with(['categories', 'media', 'user', 'thumbnail'])
            ->firstOrFail();

        return (new PostResource($post))->response();
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        if ($request->has('thumbnail_id')) {
            $this->validateThumbnailOwnership($request->input('thumbnail_id'), $request->user()->id);
        }

        $data = $request->only(['title', 'slug', 'content', 'excerpt', 'status', 'thumbnail_id']);
        $data['user_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        $post = Post::create($data);

        $post->categories()->sync($request->input('category_ids', []));

        $this->attachMedia($post, $request->input('media_ids', []), $request->user()->id);

        $post->load(['categories', 'media', 'user', 'thumbnail']);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, string $slug): JsonResponse
    {
        $userId = $request->user()->id;
        
        $post = Post::where('user_id', $userId)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($request->has('thumbnail_id')) {
            $this->validateThumbnailOwnership($request->input('thumbnail_id'), $userId);
        }

        $fill = $request->only(['title', 'slug', 'content', 'excerpt', 'status', 'thumbnail_id']);
        $post->update(array_filter($fill, fn ($v) => $v !== null));

        if ($request->has('category_ids')) {
            $post->categories()->sync($request->input('category_ids', []));
        }

        if ($request->has('media_ids')) {
            Media::where('mediable_type', Post::class)
                ->where('mediable_id', $post->id)
                ->update(['mediable_id' => null, 'mediable_type' => null]);
            $this->attachMedia($post, $request->input('media_ids', []), $userId);
        }

        $post->load(['categories', 'media', 'user', 'thumbnail']);

        return (new PostResource($post))->response();
    }

    public function destroy(Request $request, string $slug): JsonResponse
    {
        $userId = $request->user()->id;
        
        $post = Post::where('user_id', $userId)
            ->where('slug', $slug)
            ->firstOrFail();

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ], 200);
    }

    private function validateThumbnailOwnership(?int $thumbnailId, int $userId): void
    {
        if (!$thumbnailId) {
            return;
        }
        $media = Media::findOrFail($thumbnailId);
        if ($media->user_id !== $userId) {
            abort(403, 'You do not own this thumbnail.');
        }
    }

    private function attachMedia(Post $post, array $mediaIds, int $userId): void
    {
        if (empty($mediaIds)) {
            return;
        }
        $q = Media::where('user_id', $userId)->whereIn('id', $mediaIds);
        $q->where(function ($q) use ($post) {
            $q->whereNull('mediable_id')->orWhere('mediable_id', $post->id);
        });
        $allowed = $q->pluck('id')->all();
        Media::whereIn('id', $allowed)->update([
            'mediable_id' => $post->id,
            'mediable_type' => Post::class,
        ]);
    }
}
