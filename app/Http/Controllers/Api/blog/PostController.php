<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Blog\StorePostRequest;
use App\Http\Requests\Api\Blog\UpdatePostRequest;
use App\Http\Resources\Api\Blog\PostListResource;
use App\Http\Resources\Api\Blog\PostResource;
use App\Models\Api\Media;
use App\Models\Api\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = Post::where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate($request->input('per_page', 15));

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

    public function show(string $slug): JsonResponse
    {
        $post = Post::where('status', 'published')
            ->where('slug', $slug)
            ->with(['categories', 'media', 'user'])
            ->firstOrFail();

        return (new PostResource($post))->response();
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->only(['title', 'slug', 'content', 'excerpt', 'status']);
        $data['user_id'] = $request->user()->id;
        $data['status'] = $data['status'] ?? 'draft';

        $post = Post::create($data);

        $post->categories()->sync($request->input('category_ids', []));

        $this->attachMedia($post, $request->input('media_ids', []), $request->user()->id);

        $post->load(['categories', 'media', 'user']);

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            abort(403, 'You do not own this post.');
        }

        $fill = $request->only(['title', 'slug', 'content', 'excerpt', 'status']);
        $post->update(array_filter($fill, fn ($v) => $v !== null));

        if ($request->has('category_ids')) {
            $post->categories()->sync($request->input('category_ids', []));
        }

        if ($request->has('media_ids')) {
            Media::where('mediable_type', Post::class)
                ->where('mediable_id', $post->id)
                ->update(['mediable_id' => null, 'mediable_type' => null]);
            $this->attachMedia($post, $request->input('media_ids', []), $request->user()->id);
        }

        $post->load(['categories', 'media', 'user']);

        return (new PostResource($post))->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            abort(403, 'You do not own this post.');
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ], 200);
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
