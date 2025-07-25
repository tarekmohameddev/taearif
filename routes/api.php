<?php
use Illuminate\Http\Request;
use App\Models\Api\ApiThemeSettings;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Api\CRMController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\blog\BlogController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\PublicUserController;
use App\Http\Controllers\Api\ApiSideMenusController;
// use App\Http\Controllers\Api\content\ApiContentSection;
use App\Http\Controllers\Api\StepProgressController;
use App\Http\Controllers\Api\ThemeSettingsController;
use App\Http\Controllers\Api\DomainSettingsController;
use App\Http\Controllers\Api\content\ApiMenuController;
use App\Http\Controllers\Api\isthara\IstharaController;
use App\Http\Controllers\Api\project\ProjectController;
use App\Http\Controllers\Api\content\AboutApiController;
use App\Http\Controllers\Api\Customer\CustomerController;
use App\Http\Controllers\Api\property\PropertyController;
use App\Http\Controllers\Api\AnalyticsDashboardController;
use App\Http\Controllers\Api\apps\whatsapp\ChatController;
use App\Http\Controllers\Api\Affiliate\AffiliateController;
use App\Http\Controllers\Api\App\ApiInstallationController;
use App\Http\Controllers\Api\dashboard\DashboardController;
use App\Http\Controllers\Api\property\UserFacadeController;
use App\Http\Controllers\Api\content\FooterSettingController;
use App\Http\Controllers\Api\apps\whatsapp\WhatsappController;
use App\Http\Controllers\Api\content\GeneralSettingController;
use App\Http\Controllers\Api\apps\whatsapp\EmbeddingController;
use App\Http\Controllers\Api\content\ApiBannerSettingController;
use App\Http\Controllers\Api\content\ApiContentSectionsController;
use App\Http\Controllers\Api\Customer\UserApiCustomerStageController;
use App\Http\Controllers\Api\Customer\UserApiCustomerReminderController;
use App\Http\Controllers\Api\Customer\UserApiCustomerAppointmentController;
use App\Http\Controllers\Api\User\RealestateManagement\ApiCategoryController;
use App\Http\Controllers\Api\ResetPasswordController;

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

Route::get('public-user/{id}', [PublicUserController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/affiliate/register', [AffiliateController::class, 'register']);
    Route::get('/affiliate', [AffiliateController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/impersonate/{user}',            [ImpersonationController::class, 'start']);
    Route::post('/impersonate/{user}/revoke',     [ImpersonationController::class, 'stop']);
    // Route::post('/impersonate/revoke-one',        [ImpersonationController::class, 'revokeOne']);
});


// Route::middleware('web')->prefix('auth/google')->group(function () {
//     Route::get('url',      [GoogleAuthController::class, 'getGoogleAuthUrl']);
//     Route::get('callback', [GoogleAuthController::class, 'callback']);
// });

Route::middleware('web')->group(function () {
    Route::get('/auth/google/redirect', [AuthController::class, 'redirect'])->name('redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'callback'])->name('callback');
});


// Auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'getUserProfile']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/make-payment', [PaymentController::class, 'checkout']);
    Route::post('/make-payment-app', [PaymentController::class, 'checkoutApp']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/onboarding', [OnboardingController::class, 'store']);
});
// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Password reset routes
Route::post('/auth/forgot-password', [ResetPasswordController::class, 'forgotPassword']); // Send reset link
Route::post('/auth/verify-reset-code', [ResetPasswordController::class, 'verifyResetCode']); // Verify reset code



// Dashboard routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [AnalyticsDashboardController::class, 'dashboard']);
    Route::get('/dashboard/summary', [AnalyticsDashboardController::class, 'summary']);
    Route::post('/dashboard/visitors', [AnalyticsDashboardController::class, 'visitors']);
    Route::get('/dashboard/devices', [AnalyticsDashboardController::class, 'devices']);
    Route::get('/dashboard/traffic-sources', [AnalyticsDashboardController::class, 'trafficSources']);
    Route::get('/dashboard/most-visited-pages', [AnalyticsDashboardController::class, 'mostVisitedPages']);
    Route::get('/dashboard/setup-progress', [AnalyticsDashboardController::class, 'setupProgress']);
    Route::get('/dashboard/recent-activity', [AnalyticsDashboardController::class, 'getRecentActivity']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    // Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
    // Route::get('/dashboard/visitors', [DashboardController::class, 'visitors']);
    // Route::get('/dashboard/devices', [DashboardController::class, 'devices']);
    // Route::get('/dashboard/traffic-sources', [DashboardController::class, 'trafficSources']);
    // Route::get('/dashboard/most-visited-pages', [DashboardController::class, 'mostVisitedPages']);
    // Route::get('/dashboard/setup-progress', [DashboardController::class, 'setupProgress']);
    // Route::get('/dashboard/recent-activity', [DashboardController::class, 'getRecentActivity']);
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

    // Route::get('/projects/categories', [ProjectController::class, 'categories']); // Get project categories
    Route::get('/user/projects', [ProjectController::class, 'userProjects']); // Get all projects for the authenticated user
});

// property routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);

    Route::post('/properties/reorder-featured', [PropertyController::class, 'properties_reorder_featured']);
    Route::post('/properties/reorder', [PropertyController::class, 'properties_reorder']);


    Route::get('/properties/categories', [PropertyController::class, 'properties_categories']);
    Route::get('/property-faqs', [PropertyController::class, 'faqs']); // Get FAQs for a property
    Route::get('/properties/{id}', [PropertyController::class, 'show']); // Get a single property
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::post('/properties/{id}', [PropertyController::class, 'update']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
    Route::patch('/properties/{id}/toggle-featured', [PropertyController::class, 'toggleFeatured']);
    Route::post('/properties/{id}/toggle-status', [PropertyController::class, 'toggleStatus']); // Toggle property status
    Route::post('/properties/{id}/toggle-favorite', [PropertyController::class, 'toggleFavorite']);

    Route::post('/properties/{propertyId}/duplicate', [PropertyController::class, 'duplicate']); // Duplicate a property
    // faqs

    Route::get('/property/facades', [UserFacadeController::class, 'index']);

});

// Content routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/sections', [ApiContentSectionsController::class, 'index']);

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

// General Settings routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/general', [GeneralSettingController::class, 'index']);
    Route::put('/content/general', [GeneralSettingController::class, 'update']);
    Route::post('/content/general/toggle-show-properties', [GeneralSettingController::class, 'ShowProperties']);

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/menu', [ApiMenuController::class, 'index']);
    Route::put('/content/menu', [ApiMenuController::class, 'update']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/theme', [ThemeSettingsController::class, 'index']);
    Route::post('/settings/theme/set-active', [ThemeSettingsController::class, 'setActiveTheme']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/domain ', [DomainSettingsController::class, 'index']);
    Route::get('/settings/domain/{id}', [DomainSettingsController::class, 'show']);
    Route::post('/settings/domain ', [DomainSettingsController::class, 'store']);
    Route::post('/settings/domain/verify ', [DomainSettingsController::class, 'verify']);
    Route::patch('/settings/domain/set-primary', [DomainSettingsController::class, 'setPrimary']);
    Route::delete('/settings/domain/{id}', [DomainSettingsController::class, 'destroy']);

    Route::patch('/settings/domain/request-ssl', [DomainSettingsController::class, 'requestSsl']);
    Route::patch('/settings/domain/ssl-status', [DomainSettingsController::class, 'updateSslStatus']);

    Route::get('/settings/payment ', [PaymentController::class, 'index']);
});

//ApiSideMenusController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/side-menus', [ApiSideMenusController::class, 'index']);

});

// ApiCategoryController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user/categories', [ApiCategoryController::class, 'index']);
    Route::put('user/categories', [ApiCategoryController::class, 'update']);
});

// PropertyCharacteristicController

// city and district routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/cities', [CityController::class, 'index']);
    Route::get('/user/districts', [DistrictController::class, 'index']);
});

// ApiInstallationController
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/apps', [ApiInstallationController::class, 'index']);
    Route::post('/apps/install', [ApiInstallationController::class, 'install']);
    Route::post('/apps/uninstall/{appId}', [ApiInstallationController::class, 'uninstall']);

    // whatsapp
    Route::get('/apps/whatsapp', [ApiInstallationController::class, 'whatsapp']);
    Route::post('/apps/whatsapp/install', [ApiInstallationController::class, 'installWhatsapp']);
    Route::post('/apps/whatsapp/uninstall', [ApiInstallationController::class, 'uninstallWhatsapp']);

});

// api_customers
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

});

// ApiCustomerStage
Route::middleware('auth:sanctum')->prefix('crm')->group(function () {
    Route::apiResource('stages', UserApiCustomerStageController::class);
    // reorderStages
    Route::post('stages/reorder', [UserApiCustomerStageController::class, 'reorderStages']); // reorder stages
    // moveStage
    Route::post('stages/{id}/move', [UserApiCustomerStageController::class, 'moveStage']); // move stage up or down

    // Appointments
    Route::apiResource('customer-appointments', UserApiCustomerAppointmentController::class);

    // Reminders
    Route::apiResource('customer-reminders', UserApiCustomerReminderController::class);

    // CRM Dashboard
    Route::get('/', [CRMController::class, 'index']);
    Route::post('/customers/{id}/change-stage', [CRMController::class, 'changeCustomerStage']); // drag and drop customers to change stage

});




// steps
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/steps/progress', [StepProgressController::class, 'getSteps']);
    Route::post('/steps/complete', [StepProgressController::class, 'completeStep']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/embeddings', [EmbeddingController::class, 'store']);
    Route::post('/chat', [ChatController::class, 'chat']);
});
Route::post('/whatsapp/evolution-webhook', [ChatController::class, 'handleEvolutionWebhook']);

// isthara
Route::post('/isthara', [IstharaController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/whatsapp/link', [WhatsappController::class, 'store']);
    Route::get('/whatsapp', [WhatsappController::class, 'index']);
});

