<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\dashboard\DashboardController;
use App\Http\Controllers\Api\blog\BlogController;
use App\Http\Controllers\Api\project\ProjectController;
use App\Http\Controllers\Api\property\PropertyController;
use App\Http\Controllers\Api\content\ContentController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    Route::get('/dashboard/visitors', [DashboardController::class, 'visitors']);
    Route::get('/dashboard/devices', [DashboardController::class, 'devices']);
    Route::get('/dashboard/traffic-sources', [DashboardController::class, 'trafficSources']);
    Route::get('/dashboard/most-visited-pages', [DashboardController::class, 'mostVisitedPages']);
    Route::get('/dashboard/setup-progress', [DashboardController::class, 'setupProgress']);
    Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blogs', [BlogController::class, 'store']); // Create a blog post
    Route::post('/blogs/{id}', [BlogController::class, 'update']); // Update a blog post
    Route::delete('/blogs/{id}', [BlogController::class, 'destroy']); // Delete a blog post
    Route::post('/blogs/upload-image', [BlogController::class, 'uploadImage']); // Upload blog image
    Route::get('/blogs', [BlogController::class, 'index']); // Get all blog posts
    Route::get('/blogs/{id}', [BlogController::class, 'show']); // Get a single blog post
    Route::get('/blog-categories', [BlogController::class, 'categories']); // Get blog categories
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']); // Create a project
    Route::post('/projects/{id}', [ProjectController::class, 'update']); // Update a project
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); // Delete a project
    Route::patch('/projects/{id}/toggle-featured', [ProjectController::class, 'toggleFeatured']); // Toggle featured status
    Route::get('/projects', [ProjectController::class, 'index']); // Get all projects
    Route::get('/projects/{id}', [ProjectController::class, 'show']); // Get a single project
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::post('/properties/{id}', [PropertyController::class, 'update']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
    Route::patch('/properties/{id}/toggle-featured', [PropertyController::class, 'toggleFeatured']);
    Route::post('/properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/sections', [ContentController::class, 'index']);

});

