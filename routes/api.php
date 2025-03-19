<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\dashboard\DashboardController;
use App\Http\Controllers\Api\blog\BlogController;
use App\Http\Controllers\Api\project\ProjectController;
use App\Http\Controllers\Api\property\PropertyController;
use App\Http\Controllers\Api\content\ContentController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\content\FooterSettingController;
use App\Http\Controllers\Api\content\ApiBannerSettingController;
use App\Http\Controllers\Api\content\AboutApiController;
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

// Auth routes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Dashboard routes
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

// blog routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blogs', [BlogController::class, 'store']); // Create a blog post
    Route::post('/blogs/{id}', [BlogController::class, 'update']); // Update a blog post
    Route::delete('/blogs/{id}', [BlogController::class, 'destroy']); // Delete a blog post
    Route::post('/blogs/upload-image', [BlogController::class, 'uploadImage']); // Upload blog image
    Route::get('/blogs', [BlogController::class, 'index']); // Get all blog posts
    Route::get('/blogs/{id}', [BlogController::class, 'show']); // Get a single blog post
    Route::get('/blog-categories', [BlogController::class, 'categories']); // Get blog categories
});

// project routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/projects', [ProjectController::class, 'store']); // Create a project
    Route::post('/projects/{id}', [ProjectController::class, 'update']); // Update a project
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']); // Delete a project
    Route::patch('/projects/{id}/toggle-featured', [ProjectController::class, 'toggleFeatured']); // Toggle featured status
    Route::get('/projects', [ProjectController::class, 'index']); // Get all projects
    Route::get('/projects/{id}', [ProjectController::class, 'show']); // Get a single project
});

// property routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::post('/properties/{id}', [PropertyController::class, 'update']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
    Route::patch('/properties/{id}/toggle-featured', [PropertyController::class, 'toggleFeatured']);
    Route::post('/properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite']);
});

// Content routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/sections', [ContentController::class, 'index']);

});

// Upload routes
Route::middleware('auth:sanctum')->group(function () {
    // Upload routes
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::post('/upload-multiple', [UploadController::class, 'uploadMultiple']);
    Route::post('/delete-file', [UploadController::class, 'delete']);
});

// Region routes
Route::middleware('auth:sanctum')->group(function () {

    Route::get('regions', [RegionController::class, 'index']);
    Route::get('regions/{region}', [RegionController::class, 'show']);
});

// Footer Settings routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/footer', [FooterSettingController::class, 'index']);
    Route::put('/content/footer', [FooterSettingController::class, 'update']);
});


// banner routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/banner', [ApiBannerSettingController::class, 'index']);
    Route::post('/content/banner', [ApiBannerSettingController::class, 'update']);
});

// about routes
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/content/about', [AboutApiController::class, 'index']);
    Route::post('/content/about', [AboutApiController::class, 'update']);
});