<?php

namespace App\Http\Controllers\Api\blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'posts' => [
                    [
                        'id' => 1,
                        'title' => 'أحدث اتجاهات التصميم الداخلي لعام 2023',
                        'excerpt' => 'استكشف أحدث اتجاهات التصميم الداخلي...',
                        'featured_image' => '/storage/blogs/image1.jpg',
                        'category' => 'تصميم داخلي',
                        'status' => 'published',
                        'tags' => ['تصميم', 'ديكور', '2023'],
                        'published_at' => '2023-07-15T10:00:00.000000Z',
                        'views' => 1245,
                        'comments' => 23,
                        'featured' => true,
                        'author' => [
                            'id' => 1,
                            'name' => 'سارة أحمد'
                        ],
                        'created_at' => '2023-07-15T10:00:00.000000Z',
                        'updated_at' => '2023-07-15T10:00:00.000000Z'
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
                'post' => [
                    'id' => $id,
                    'title' => 'أحدث اتجاهات التصميم الداخلي لعام 2023',
                    'excerpt' => 'استكشف أحدث اتجاهات التصميم الداخلي...',
                    'content' => 'Lorem ipsum dolor sit amet...',
                    'featured_image' => '/storage/blogs/image1.jpg',
                    'category' => 'تصميم داخلي',
                    'status' => 'published',
                    'tags' => ['تصميم', 'ديكور', '2023'],
                    'seo_title' => 'أحدث اتجاهات التصميم الداخلي لعام 2023',
                    'seo_description' => 'استكشف أحدث اتجاهات التصميم الداخلي...',
                    'published_at' => '2023-07-15T10:00:00.000000Z',
                    'views' => 1245,
                    'comments' => 23,
                    'featured' => true,
                    'author' => [
                        'id' => 1,
                        'name' => 'سارة أحمد'
                    ],
                    'created_at' => '2023-07-15T10:00:00.000000Z',
                    'updated_at' => '2023-07-15T10:00:00.000000Z'
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Blog post created successfully',
            'data' => [
                'post' => $request->all()
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Blog post updated successfully',
            'data' => [
                'post' => array_merge(['id' => $id], $request->all())
            ]
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Blog post deleted successfully'
        ]);
    }

    public function categories()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => [
                    ['id' => 1, 'name' => 'تصميم داخلي', 'count' => 15],
                    ['id' => 2, 'name' => 'نصائح التصميم', 'count' => 8]
                ]
            ]
        ]);
    }

    public function uploadImage(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'url' => '/storage/blogs/uploaded-image.jpg'
            ]
        ]);
    }
}
