<?php

use Illuminate\Http\Request;
use App\Models\Api\ApiThemeSettings;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Rbac\{
    RoleController as RbacRoleController,
    AssignmentController,
    PermissionController,
    PermissionAdminController,
};

use App\Http\Controllers\Api\V1\{
    RoleController,
};

use Laravel\Socialite\Facades\Socialite;
// use App\Http\Controllers\Api\content\ApiContentSection;
use App\Http\Controllers\ImpersonationController;

use App\Http\Controllers\Api\{
    MeAbilitiesController,
    CRMController,
    ReferralController,
    content\ApiContentSectionsController,
    ThemeSettingsController,
    AuthController,
    OtpController,
    RegionController,
    DistrictController,
    CityController,
    UploadController,
    PaymentController,
    AnalyticsDashboardController,
    OnboardingController,
    PublicUserController,
    StepProgressController,
    isthara\IstharaController,
    content\AboutApiController,
    apps\whatsapp\ChatController,
    apps\whatsapp\WhatsappAddonController,
    Affiliate\AffiliateController,
    App\ApiInstallationController,
    dashboard\DashboardController,
    property\UserFacadeController,
    apps\whatsapp\WhatsappController,
    apps\whatsapp\MetaOAuthController,
    apps\whatsapp\EmbeddingController,
    User\RealestateManagement\ApiCategoryController,
    ResetPasswordController,
    property\ApiPropertyRequestController,
    property\ApiPropertyRequestSettingsController,
    property\PropertyRequestStatusController,
    blog\BlogController,
    blog\PostController,
    blog\MediaController as BlogMediaController,
    blog\CategoriesController,
    project\ProjectController,
    project\ProjectPropertyController,
    property\PropertyController,
    content\FooterSettingController,
    content\ApiBannerSettingController,
    content\ApiMenuController,
    content\GeneralSettingController,
    content\CustomerDropdownSettingController,
    DomainSettingsController,
    ApiSideMenusController,
    ApiContractController,
    RentalContractController,
    AppPaymentController,
    AdminArticleController,
    AdminArticleCategoryController,
    PublicAdminArticlesController,
    PublicSupportCenterController,
};

use App\Http\Controllers\Api\V1\Logs\{
    CustomerLogController,
    PropertyLogController,
    ProjectLogController,
    CardLogController,
};
use App\Http\Controllers\Api\Customer\{
    UserApiCustomerStageController,
    UserApiCustomerPriorityController,
    UserApiCustomerTypeController,
    UserApiCustomerProcedureController,
    UserApiCustomerReminderController,
    UserApiCustomerAppointmentController,
    CustomerController,
};
use App\Http\Controllers\Api\CRM\{
    ReminderTypeController,
    ReminderController,
};

use App\Http\Controllers\Api\V1\WhatsApp\{
    CampaignController as WaCampaignController,
    NumberController as WhatsAppNumberController,
    ConversationController as WhatsAppConversationController,
    MessageController as WhatsAppMessageController,
    TemplateController as WhatsAppTemplateController,
    AutomationRuleController as WhatsAppAutomationRuleController,
    AiConfigController as WhatsAppAiConfigController,
    StatsController as WhatsAppStatsController,
    WebhookController as WhatsAppWebhookController,
};
use App\Http\Controllers\Api\V1\{
    ConversationController,
    MessageController,
    Em\EmployeeAuthController,
    LogController,
    // RoleController,
    EmployeeController,
    CustomerInquiryController,
    Crm\CrmCardController,
    Crm\CrmRequestController,
    Em\CustomerController as EmployeeCustomerController,
};

use App\Http\Controllers\Api\V1\Rms\{
    RentalController,
    ContractController,
    InstallmentController,
    MaintenanceController,
    ReminderController as RmsReminderController,
    RmsDashboardController,
    ExpenseController,
};
use App\Http\Controllers\Api\V1\TenantWebsite\{
    GetTenantController,
    SavePagesController,
    PageController,
    GlobalsController,
    ComponentCatalogController,
    MediaController,
    SettingsController,
    PublishController,
    FormController,
    PixelController as TenantWebsitePixelController,
    StaticPageController,
};
use App\Http\Controllers\Api\V1\Analytics\PageviewController;
use App\Http\Controllers\Api\V1\Analytics\Ga4AnalyticsController;
use App\Http\Controllers\Api\V1\Matching\MatchingController as V1MatchingController;
use App\Http\Controllers\Api\V1\Matching\CustomerRequestController as V1CustomerRequestController;

use App\Http\Controllers\Api\PixelController; // Added import for PixelController

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

// =============================================================================
// PUBLIC API ROUTES (no authentication required)
// =============================================================================

// Templates & public data
Route::get('public-user/{id}', [PublicUserController::class, 'show']);
Route::get('/properties/bulk-import/template', [PropertyController::class, 'downloadTemplate']);
Route::post('/customers/bulk-import/template', [CustomerController::class, 'downloadTemplate']);

// Auth: register, login, password reset
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [ResetPasswordController::class, 'forgotPassword']); // Send reset link
Route::post('/auth/verify-reset-code', [ResetPasswordController::class, 'verifyResetCode']); // Verify reset code

// OTP (registration phone verification via WhatsApp)
Route::post('/auth/send-otp', [OtpController::class, 'sendOtp']);

// OAuth (web middleware)
Route::middleware('web')->group(function () {
    Route::get('/auth/google/redirect', [AuthController::class, 'redirect'])->name('redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'callback'])->name('callback');
});

// Referrals (public validation)
Route::get('/referrals', [ReferralController::class, 'validateCode']); // /api/referrals?code=ABCD1234
Route::get('/referrals/{code}', [ReferralController::class, 'show']);  // /api/referrals/ABCD1234

// Analytics: public tracking endpoint
Route::post('/v1/analytics/page-view', [PageviewController::class, 'track'])
    ->middleware('throttle:api_tracking'); // 100 requests per minute for tracking (production only)

// Public admin articles (admin_articles + admin_articles_categories) — unique paths, no auth
Route::get('/public/admin-article-categories', [PublicAdminArticlesController::class, 'categories']);
Route::get('/public/admin-article-categories/{slug}/articles', [PublicAdminArticlesController::class, 'categoryArticles']);
Route::get('/public/admin-articles', [PublicAdminArticlesController::class, 'articles']);
Route::get('/public/admin-articles/{slug}', [PublicAdminArticlesController::class, 'show']);

// Public support center (support_center_categories + support_center_articles) — no auth
Route::get('/public/support-center/categories', [PublicSupportCenterController::class, 'categories']);
Route::get('/public/support-center/categories/{slug}/articles', [PublicSupportCenterController::class, 'categoryArticles']);
Route::get('/public/support-center/articles', [PublicSupportCenterController::class, 'articles']);
Route::get('/public/support-center/articles/{slug}', [PublicSupportCenterController::class, 'show']);

// =============================================================================
// PROTECTED API ROUTES (authentication required)
// =============================================================================

// --- Auth / user ---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUserProfile']);
    Route::get('/user/getUserInfo', [AuthController::class, 'getUserProfile']); // Alias for frontend compatibility
    Route::post('/user-read-message', [AuthController::class, 'read_message']);
});

// Phone verification (pre-registration): public endpoint
Route::post('/auth/verify-otp', [OtpController::class, 'verifyOtp']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// --- Affiliate ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/affiliate/register', [AffiliateController::class, 'register']);
    Route::get('/affiliate', [AffiliateController::class, 'index']);
});

// --- Impersonation ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/impersonate/{user}',            [ImpersonationController::class, 'start']);
    Route::post('/impersonate/{user}/revoke',     [ImpersonationController::class, 'stop']);
});

// --- Payments / onboarding ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/make-payment', [PaymentController::class, 'checkout']);
    Route::post('/make-payment-app', [PaymentController::class, 'checkoutApp']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/onboarding', [OnboardingController::class, 'store']);
});



// --- Dashboard (require active package) ---
Route::middleware(['auth:sanctum', 'require.active.package'])->group(function () {
    Route::get('/dashboard', [AnalyticsDashboardController::class, 'dashboard']);
    Route::get('/dashboard/summary', [AnalyticsDashboardController::class, 'summary']);
    Route::post('/dashboard/visitors', [AnalyticsDashboardController::class, 'visitors']);
    Route::get('/dashboard/devices', [AnalyticsDashboardController::class, 'devices']);
    Route::get('/dashboard/traffic-sources', [AnalyticsDashboardController::class, 'trafficSources']);
    Route::get('/dashboard/most-visited-pages', [AnalyticsDashboardController::class, 'mostVisitedPages']);
    Route::get('/dashboard/setup-progress', [AnalyticsDashboardController::class, 'setupProgress']);
    Route::get('/dashboard/recent-activity', [AnalyticsDashboardController::class, 'getRecentActivity']);

    // Analytics Views Endpoints
    Route::get('/dashboard/debug-ga-views', [AnalyticsDashboardController::class, 'debugGAViews']); // Production: tenant-specific views only
    Route::get('/dashboard/ga-full-diagnostics', [AnalyticsDashboardController::class, 'gaFullDiagnostics']); // Development: full diagnostic data
    Route::get('/dashboard/diagnostic-ga-test', [AnalyticsDashboardController::class, 'diagnosticGATest']); // Comprehensive GA4 diagnostic tests
    Route::get('/analytics/search', [AnalyticsDashboardController::class, 'searchAnalytics']); // Flexible search with backend filtering
    Route::get('/analytics/page-locations', [AnalyticsDashboardController::class, 'getPageLocations']); // Get full URLs (page_location)
    Route::get('/analytics/today', [AnalyticsDashboardController::class, 'getToday']); // Get today's data (near realtime, perfect tenant filtering)
    Route::get('/analytics/realtime', [AnalyticsDashboardController::class, 'getRealtime']); // Get realtime data (last 30 minutes, limited filtering)
    Route::get('/analytics/live-test', [AnalyticsDashboardController::class, 'liveTest']); // Live GA4 tenant filtering verification endpoint for debugging
    Route::get('/analytics/tenants', [AnalyticsDashboardController::class, 'getTenantsList']); // Get list of all tenants with GA4 data
});

// --- Analytics (v1 pageview & GA4) ---
Route::prefix('v1/analytics')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [PageviewController::class, 'dashboard'])
        ->middleware('throttle:api_standard_60'); // 60 requests per minute (production only)
    Route::get('/top-pages', [PageviewController::class, 'topPages'])
        ->middleware('throttle:api_standard_60');
    Route::get('/top-posts', [PageviewController::class, 'topPosts'])
        ->middleware('throttle:api_standard_60');
    Route::get('/views-summary', [PageviewController::class, 'summary'])
        ->middleware('throttle:api_standard_60');
});

// GA4 Analytics Routes (v1) - Read from database (no live GA calls)
Route::prefix('v1/analytics/ga4')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', [Ga4AnalyticsController::class, 'dashboard'])
        ->middleware('throttle:api_standard_60'); // 60 requests per minute (production only)
    Route::get('/top-pages', [Ga4AnalyticsController::class, 'topPages'])
        ->middleware('throttle:api_standard_60');
    Route::get('/properties-visits', [Ga4AnalyticsController::class, 'propertiesVisits'])
        ->middleware('throttle:api_standard_60');
});

// --- Blog (legacy) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/blogs', [BlogController::class, 'store']); // Create a blog post
    Route::post('/blogs/{id}', [BlogController::class, 'update']); // Update a blog post
    Route::delete('/blogs/{id}', [BlogController::class, 'destroy']); // Delete a blog post
    Route::post('/blogs/upload-image', [BlogController::class, 'uploadImage']); // Upload blog image
    Route::get('/blogs', [BlogController::class, 'index']); // Get all blog posts
    Route::get('/blogs/{id}', [BlogController::class, 'show']); // Get a single blog post
    Route::get('/blog-categories', [BlogController::class, 'categories']); // Get blog categories
});

// --- Blog posts (user's own posts) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/posts', [PostController::class, 'index']); // User's posts (supports ?status=draft|published)
    Route::get('/posts/{slug}', [PostController::class, 'show']); // User's single post by slug
    Route::get('/categories', [CategoriesController::class, 'index']); // All categories
    Route::get('/categories/{slug}', [CategoriesController::class, 'show']); // Single category by slug
    Route::get('/categories/{slug}/posts', [CategoriesController::class, 'posts']); // Posts for a specific category by slug
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{slug}', [PostController::class, 'update']); // Changed from {id} to {slug}
    Route::delete('/posts/{slug}', [PostController::class, 'destroy']); // Changed from {id} to {slug}
    Route::post('/media', [BlogMediaController::class, 'store']);
    Route::post('/categories', [CategoriesController::class, 'store']);
    Route::put('/categories/{slug}', [CategoriesController::class, 'update']); // Changed from {id} to {slug}
    Route::delete('/categories/{slug}', [CategoriesController::class, 'destroy']); // Changed from {id} to {slug}
});

// --- Contracts ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/contracts', [ApiContractController::class, 'index']); // Get all contracts
    Route::get('/contracts/{id}', [ApiContractController::class, 'show']); // Get a single contract
    Route::post('/contracts', [ApiContractController::class, 'store']); // Create a contract
    Route::put('/contracts/{id}', [ApiContractController::class, 'update']); // Update a contract
    Route::delete('/contracts/{id}', [ApiContractController::class, 'destroy']); // Delete a contract
    Route::get('/contracts/statistics', [ApiContractController::class, 'statistics']); // Get contract statistics
    Route::get('/contracts/customer/{customerId}', [ApiContractController::class, 'getByCustomer']); // Get contracts by customer
    Route::get('/contracts/rental/{rentalId}', [ApiContractController::class, 'getByRental']); // Get contracts by rental
});

// --- Rental contracts (RMS) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/rental-contracts', [RentalContractController::class, 'index']); // Get all rental contracts
    Route::get('/rental-contracts/statistics', [RentalContractController::class, 'statistics']); // Get rental contract statistics
    Route::get('/rental-contracts/daily-follow-up', [RentalContractController::class, 'dailyFollowUp']); // Daily follow-up for rental contracts
    Route::get('/rental-contracts/all-contracts', [RentalContractController::class, 'allContracts']); // All contracts with color status
    Route::get('/rental-contracts/filter', [RentalContractController::class, 'filterContracts']); // Advanced contract filtering
    Route::get('/rental-contracts/rental/{rentalId}', [RentalContractController::class, 'getByRental']); // Get contracts by rental
    Route::post('/rental-contracts', [RentalContractController::class, 'store']); // Create a rental contract
    Route::get('/rental-contracts/{id}', [RentalContractController::class, 'show']); // Get a single rental contract
    Route::put('/rental-contracts/{id}', [RentalContractController::class, 'update']); // Update a rental contract
    Route::delete('/rental-contracts/{id}', [RentalContractController::class, 'destroy']); // Delete a rental contract
    Route::post('/rental-contracts/{id}/terminate', [RentalContractController::class, 'terminate']); // Terminate a rental contract
    Route::patch('/rental-contracts/{id}/status', [RentalContractController::class, 'changeStatus']); // Change rental contract status
});

// --- Projects ---
Route::middleware(['auth:sanctum', 'audit.ctx'])->group(function () {
    Route::get   ('/projects',            [ProjectController::class, 'index'])->middleware('can:projects.view');
    Route::get   ('/projects/{project}/properties', [ProjectPropertyController::class, 'index'])->middleware('can:projects.view');
    Route::post  ('/projects/{project}/properties', [ProjectPropertyController::class, 'store'])->middleware('can:properties.create');
    Route::post  ('/projects/{project}/properties/attach', [ProjectPropertyController::class, 'attach'])->middleware('can:properties.update');
    Route::patch ('/projects/{project}/properties/{property}', [ProjectPropertyController::class, 'update'])->middleware('can:properties.update');
    Route::delete('/projects/{project}/properties/{property}', [ProjectPropertyController::class, 'destroy'])->middleware('can:properties.update');
    Route::get   ('/projects/{id}/property-counters', [ProjectController::class, 'propertyCounters'])->middleware('can:projects.view');
    Route::get   ('/projects/{id}',       [ProjectController::class, 'show'])->middleware('can:projects.view');
    Route::post  ('/projects',            [ProjectController::class, 'store'])->middleware('can:projects.create');
    Route::post  ('/projects/{id}',       [ProjectController::class, 'update'])->middleware('can:projects.update');
    Route::delete('/projects/{id}',       [ProjectController::class, 'destroy'])->middleware('can:projects.delete');
    Route::patch ('/projects/{id}/toggle-featured', [ProjectController::class, 'toggleFeatured'])->middleware('can:projects.update');
    Route::get   ('/user/projects',       [ProjectController::class, 'userProjects'])->middleware('can:projects.view');
});



// --- Properties ---
Route::middleware(['auth:sanctum', 'audit.ctx'])->group(function () {
    Route::post  ('/properties/reorder-featured',        [PropertyController::class, 'properties_reorder_featured'])->middleware('can:properties.reorder');
    Route::post  ('/properties/reorder',                 [PropertyController::class, 'properties_reorder'])->middleware('can:properties.reorder');
        // properties/categories
    Route::get   ('/properties/categories',              [PropertyController::class, 'properties_categories']);

    Route::get   ('/properties',                         [PropertyController::class, 'index'])->middleware('can:properties.view');
    Route::get   ('/properties/filter-options',         [PropertyController::class, 'filterOptions'])->middleware('can:properties.view');
    Route::get   ('/properties/cards',                  [PropertyController::class, 'cards'])->middleware('can:properties.view');
    Route::get   ('/properties/available-units',         [PropertyController::class, 'availableUnits'])->middleware('can:properties.view');
    Route::get   ('/properties/export',                  [PropertyController::class, 'export'])->middleware('can:properties.view');
    Route::get   ('/properties/export-for-import',     [PropertyController::class, 'exportForImport'])->middleware('can:properties.view');

    // Draft/Incomplete Properties Management - MUST be before /properties/{id} to avoid route conflict
    Route::get   ('/properties/drafts',                   [PropertyController::class, 'listDrafts'])->middleware('can:properties.view');
    Route::get   ('/properties/drafts/{id}',              [PropertyController::class, 'showDraft'])->middleware('can:properties.view');
    Route::patch ('/properties/drafts/{id}',              [PropertyController::class, 'updateDraft'])->middleware('can:properties.update');
    Route::post  ('/properties/drafts/{id}/complete',     [PropertyController::class, 'completeDraft'])->middleware('can:properties.create');
    Route::post  ('/properties/drafts/bulk-complete',     [PropertyController::class, 'bulkCompleteDrafts'])->middleware('can:properties.create');

    Route::post  ('/properties/bulk',                     [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'bulkCreate'])->middleware('can:properties.create');
    Route::post  ('/properties/import/excel',           [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'importExcel'])->middleware('can:properties.create');
    Route::get   ('/properties/import/{batchId}/preview', [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'importPreview'])->middleware('can:properties.view');
    Route::post  ('/properties/import/{batchId}/apply',   [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'importApply'])->middleware('can:properties.create');
    Route::get   ('/properties/import/{batchId}/report',  [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'importReport'])->middleware('can:properties.view');

    Route::get   ('/properties/{id}',                    [PropertyController::class, 'show'])->middleware('can:properties.view');
    Route::patch ('/properties/{id}/status',             [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'changeStatus'])->middleware('can:properties.change_status');
    Route::get   ('/properties/{id}/audit-logs',         [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'auditLogs'])->middleware('can:properties.view_audit_log');
    Route::get   ('/properties/{id}/internal-notes',     [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'internalNotes'])->middleware('can:properties.view');
    Route::post  ('/properties/{id}/internal-notes',     [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'storeInternalNote'])->middleware('can:properties.update');
    Route::get   ('/properties/{id}/archive',            [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'archive'])->middleware('can:properties.view');
    Route::post  ('/properties/{id}/archive',            [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'storeArchiveItem'])->middleware('can:properties.update');
    Route::get   ('/properties/{id}/crm-counters',       [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'crmCounters'])->middleware('can:properties.view');
    Route::get   ('/properties/{id}/crm-relations',      [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'crmRelations'])->middleware('can:properties.view');
    Route::post  ('/properties/{id}/crm-relations',      [\App\Http\Controllers\Api\property\PropertyManagementController::class, 'storeCrmRelation'])->middleware('can:properties.update');
    Route::post  ('/properties/bulk-import',             [PropertyController::class, 'bulkImport'])->middleware('can:properties.create');
    // Route::get   ('/properties/bulk-import/template',    [PropertyController::class, 'downloadTemplate']); // Moved to public routes
    Route::post  ('/properties',                         [PropertyController::class, 'store'])->middleware('can:properties.create');
    Route::post  ('/properties/upload-deed-image',       [PropertyController::class, 'uploadDeedImage'])->middleware('can:properties.create');
    Route::post  ('/properties/{id}',                    [PropertyController::class, 'update'])->middleware('can:properties.update');
    Route::delete('/properties/{id}',                    [PropertyController::class, 'destroy'])->middleware('can:properties.delete');
    Route::patch ('/properties/{id}/toggle-featured',    [PropertyController::class, 'toggleFeatured'])->middleware('can:properties.update');
    Route::post  ('/properties/{id}/toggle-status',      [PropertyController::class, 'toggleStatus'])->middleware('can:properties.update');
    Route::post  ('/properties/{propertyId}/duplicate',  [PropertyController::class, 'duplicate'])->middleware('can:properties.create');

    Route::get   ('/property/facades',                   [UserFacadeController::class, 'index'])->middleware('can:properties.view');
    // faqs
    Route::get   ('/property-faqs',                      [PropertyController::class, 'faqs']);

    // Building management routes
    Route::get   ('/buildings',                         [App\Http\Controllers\Api\BuildingController::class, 'index'])->middleware('can:buildings.view');
    Route::get   ('/buildings/{id}/properties',         [App\Http\Controllers\Api\BuildingPropertyController::class, 'index'])->middleware('can:buildings.view');
    Route::get   ('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'show'])->middleware('can:buildings.view');
    Route::post  ('/buildings',                         [App\Http\Controllers\Api\BuildingController::class, 'store'])->middleware('can:buildings.create');
    Route::post  ('/buildings/upload-image',            [App\Http\Controllers\Api\BuildingController::class, 'uploadBuildingImage'])->middleware('can:buildings.create');
    Route::post  ('/buildings/upload-deed-image',       [App\Http\Controllers\Api\BuildingController::class, 'uploadDeedImage'])->middleware('can:buildings.create');
    Route::put   ('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'update'])->middleware('can:buildings.update');
    Route::delete('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'destroy'])->middleware('can:buildings.delete');

    Route::post('/advertising-imports/link', [App\Http\Controllers\Api\AdvertisingImportController::class, 'storeFromLink'])->middleware('can:properties.create');
});

// --- Content ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/sections', [ApiContentSectionsController::class, 'index']);
});

// --- Upload ---
Route::middleware('auth:sanctum')->group(function () {
    // Upload routes
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::post('/upload-multiple', [UploadController::class, 'uploadMultiple']);
    Route::post('/delete-file', [UploadController::class, 'delete']);
});

// --- Regions ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('regions', [RegionController::class, 'index']);
    Route::get('regions/{region}', [RegionController::class, 'show']);
});

// --- Footer / General / Banner / Customer dropdown / About / Menu / Theme / Settings ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/footer', [FooterSettingController::class, 'index']);
    Route::put('/content/footer', [FooterSettingController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/general', [GeneralSettingController::class, 'index']);
    Route::put('/content/general', [GeneralSettingController::class, 'update']);
    Route::post('/content/general/toggle-show-properties', [GeneralSettingController::class, 'ShowProperties']);

});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/banner', [ApiBannerSettingController::class, 'index']);
    Route::post('/content/banner', [ApiBannerSettingController::class, 'update']);
});

// customer dropdown routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/customer-dropdown', [CustomerDropdownSettingController::class, 'index']);
    Route::put('/content/customer-dropdown', [CustomerDropdownSettingController::class, 'update']);
    Route::post('/content/customer-dropdown/toggle-visibility', [CustomerDropdownSettingController::class, 'toggleVisibility']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/about', [AboutApiController::class, 'index']);
    Route::post('/content/about', [AboutApiController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/menu', [ApiMenuController::class, 'index']);
    Route::put('/content/menu', [ApiMenuController::class, 'update']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/static-pages', [StaticPageController::class, 'index']);
    Route::post('/content/static-pages', [StaticPageController::class, 'store']);
    Route::get('/content/static-pages/{pageId}', [StaticPageController::class, 'show'])
        ->where('pageId', 'privacy|terms|profile');
    Route::put('/content/static-pages/{pageId}', [StaticPageController::class, 'update'])
        ->where('pageId', 'privacy|terms|profile');
    Route::patch('/content/static-pages/{pageId}', [StaticPageController::class, 'update'])
        ->where('pageId', 'privacy|terms|profile');
    Route::delete('/content/static-pages/{pageId}', [StaticPageController::class, 'destroy'])
        ->where('pageId', 'privacy|terms|profile');
});

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/settings/theme', [ThemeSettingsController::class, 'index']);
    Route::post('/settings/theme/set-active', [ThemeSettingsController::class, 'setActiveTheme']);
    Route::post('/settings/theme/purchase', [ThemeSettingsController::class, 'purchase']);
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/settings/payment', [PaymentController::class, 'index']); //PaymentController
    // (small note: remove the stray spaces in your paths like '/settings/domain ')
    Route::get   ('/settings/domain',                 [DomainSettingsController::class, 'index'])->middleware('can:settings.update');
    Route::get   ('/settings/domain/{id}',            [DomainSettingsController::class, 'show'])->middleware('can:settings.update');
    Route::post  ('/settings/domain',                 [DomainSettingsController::class, 'store'])->middleware('can:settings.update');
    Route::post  ('/settings/domain/verify',          [DomainSettingsController::class, 'verify'])->middleware('can:settings.update');
    Route::patch ('/settings/domain/set-primary',     [DomainSettingsController::class, 'setPrimary'])->middleware('can:settings.update');
    Route::delete('/settings/domain/{id}',            [DomainSettingsController::class, 'destroy'])->middleware('can:settings.update');

    Route::patch ('/settings/domain/request-ssl',     [DomainSettingsController::class, 'requestSsl'])->middleware('can:settings.update');
    Route::patch ('/settings/domain/ssl-status',      [DomainSettingsController::class, 'updateSslStatus'])->middleware('can:settings.update');
});


//ApiSideMenusController
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/settings/side-menus', [ApiSideMenusController::class, 'index']);
});


// --- User categories / Cities / Districts / Apps (installations) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user/categories', [ApiCategoryController::class, 'index']);
    Route::put('user/categories', [ApiCategoryController::class, 'update']);
});

// PropertyCharacteristicController

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/cities', [CityController::class, 'index']);
    Route::get('/user/districts', [DistrictController::class, 'index']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/apps', [ApiInstallationController::class, 'index']);
    Route::post('/apps/install', [ApiInstallationController::class, 'install']);
    Route::post('/apps/uninstall/{appId}', [ApiInstallationController::class, 'uninstall']);
    Route::get('/apps/{appId}/purchase-url', [ApiInstallationController::class, 'getPurchaseUrl']);

    // whatsapp
    Route::get('/apps/whatsapp', [ApiInstallationController::class, 'whatsapp']);
    Route::post('/apps/whatsapp/install', [ApiInstallationController::class, 'installWhatsapp']);
    Route::post('/apps/whatsapp/uninstall', [ApiInstallationController::class, 'uninstallWhatsapp']);

    // App Payment Endpoints
    Route::get('/installations/{installationId}/payment/status', [AppPaymentController::class, 'getPaymentStatus']);
    Route::post('/apps/{appId}/payment/verify', [AppPaymentController::class, 'verifyPayment']);
    Route::get('/apps/payments', [AppPaymentController::class, 'getPaymentHistory']);

});

// =============================================================================
// PUBLIC API ROUTES (continued - webhooks, callbacks, public endpoints)
// =============================================================================

// App payment callback (webhooks)
Route::post('/apps/payment/callback/{gateway}', [AppPaymentController::class, 'handleCallback']);

// --- Customers ---
Route::middleware(['auth:sanctum', 'audit.ctx'])->group(function () {
    Route::prefix('customers')->group(function () {
        Route::get   ('/filters',  [CustomerController::class, 'filterOptions'])->middleware('can:customers.view');
        Route::get   ('/',         [CustomerController::class, 'index'])->middleware('can:customers.view');
        Route::get   ('/all',      [CustomerController::class, 'all'])->middleware('can:customers.view');
        Route::get   ('/search',   [CustomerController::class, 'search'])->middleware('can:customers.view');
        Route::get   ('/export',   [CustomerController::class, 'export'])->middleware('can:customers.view');
        Route::post  ('/bulk-import', [CustomerController::class, 'bulkImport'])->middleware('can:customers.create');
        Route::get   ('/{id}/with-inquiries', [CustomerController::class, 'showWithInquiries'])->middleware('can:customers.view');
        Route::get   ('/{id}',     [CustomerController::class, 'show'])->middleware('can:customers.view');
        Route::post  ('/',         [CustomerController::class, 'store'])->middleware('can:customers.create');
        Route::put   ('/{id}',     [CustomerController::class, 'update'])->middleware('can:customers.update');
        Route::delete('/{id}',     [CustomerController::class, 'destroy'])->middleware('can:customers.delete');
    });
});


// --- CRM ---
Route::middleware(['auth:sanctum', 'audit.ctx', 'log.employee.activity', 'can:crm.view'])->prefix('crm')->group(function () {
    // STAGES
    Route::apiResource('stages', UserApiCustomerStageController::class);
    // reorderStages
    Route::post('stages/reorder', [UserApiCustomerStageController::class, 'reorderStages']); // reorder stages
    // moveStage
    Route::post('stages/{id}/move', [UserApiCustomerStageController::class, 'moveStage']); // move stage up or down
    // PROCEDURE TYPES
    Route::apiResource('procedures', UserApiCustomerProcedureController::class);
    Route::post('procedures/reorder', [UserApiCustomerProcedureController::class, 'reorderProcedures']);
    Route::post('procedures/{id}/move', [UserApiCustomerProcedureController::class, 'moveProcedure']);
    // PRIORITIES
    Route::apiResource('priorities', UserApiCustomerPriorityController::class);
    Route::post('priorities/reorder', [UserApiCustomerPriorityController::class, 'reorderPriorities']);
    Route::post('priorities/{id}/move', [UserApiCustomerPriorityController::class, 'movePriority']);
    // Types
    Route::apiResource('types', UserApiCustomerTypeController::class);
    Route::post('types/reorder', [UserApiCustomerTypeController::class, 'reorderTypes']);
    Route::post('types/{id}/move', [UserApiCustomerTypeController::class, 'moveTypes']);


    // Appointments
    Route::apiResource('customer-appointments', UserApiCustomerAppointmentController::class);

    // Reminders
    Route::get('customer-reminders/filter-options', [UserApiCustomerReminderController::class, 'filterOptions']);
    Route::apiResource('customer-reminders', UserApiCustomerReminderController::class);

    // Reminder Types (New System)
    Route::apiResource('reminder-types', ReminderTypeController::class);

    // Reminders (New System)
    Route::apiResource('reminders', ReminderController::class);

    // CRM Dashboard
    Route::get('/', [CRMController::class, 'index']);
    // CRM customer filters (same payload as /api/customers/filters)
    Route::get('/customers/filters', [CustomerController::class, 'filterOptions']);
    Route::post('/customers/{id}/change-stage', [CRMController::class, 'changeCustomerStage']); // drag and drop customers to change stage
    // drag and drop customers to change priority
    Route::post('/customers/{id}/change-priority', [CRMController::class, 'changeCustomerPriority']);
    // drag and drop customers to change type
    Route::post('/customers/{id}/change-type', [CRMController::class, 'changeCustomerType']);
    // drag and drop customers procedure
    Route::post('/customers/{id}/change-procedure', [CRMController::class, 'changeCustomerProcedure']);

    // searchCustomers
    Route::get('/customers/search', [CRMController::class, 'searchCustomers']); // search customers

    // CRM Customer Import/Export
    Route::get('/customers/export', [CRMController::class, 'export'])->middleware('can:crm.view');
    Route::get('/customers/import/template', [CRMController::class, 'downloadTemplate'])->middleware('can:crm.view');
    Route::post('/customers/import', [CRMController::class, 'bulkImport'])->middleware('can:crm.create');

    // Property Request Auto-Customer Settings
    Route::get('/property-requests/settings', [\App\Http\Controllers\Api\CRM\PropertyRequestSettingsController::class, 'index']);
    Route::put('/property-requests/settings', [\App\Http\Controllers\Api\CRM\PropertyRequestSettingsController::class, 'update']);

});

// --- Steps progress ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/steps/progress', [StepProgressController::class, 'getSteps']);
    Route::post('/steps/complete', [StepProgressController::class, 'completeStep']);
});


// --- Embeddings / Chat / WhatsApp meta redirect ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/embeddings', [EmbeddingController::class, 'store']);
    Route::post('/chat', [ChatController::class, 'chat']);
    Route::get('/whatsapp/meta/redirect', [MetaOAuthController::class, 'redirect']);
});

// WhatsApp: public callbacks & webhooks
Route::get('/whatsapp/meta/callback', [MetaOAuthController::class, 'callback']);
Route::post('/whatsapp/evolution-webhook', [ChatController::class, 'handleEvolutionWebhook']);
Route::post('/whatsapp/webhook', [ChatController::class, 'handleWhatsappWebhook']);

// Isthara (public)
Route::post('/isthara', [IstharaController::class, 'store']);

// Location lookups (public)
Route::get('/cities', [CityController::class, 'index'])
    ->middleware('throttle:api_standard_60');
Route::get('/districts', [DistrictController::class, 'index'])
    ->middleware('throttle:api_standard_60');

// Property requests (public)
Route::post('/v1/property-requests/public', [ApiPropertyRequestController::class, 'store']);
// Property interest (public - no authentication required)
Route::post('/v1/property-requests/interest', [ApiPropertyRequestController::class, 'storeFromInterest']);

// Credits: public packages & payment callbacks
Route::prefix('v1/credits')->group(function () {
    Route::get('packages', [\App\Http\Controllers\Api\marketing\CreditController::class, 'getPackages']);

    // Payment callback routes (no auth required for webhooks). Accept GET and POST (e.g. ARB may POST).
    Route::match(['get', 'post'], 'payment/success/{transaction_id}/{gateway}', [\App\Http\Controllers\Api\marketing\CreditController::class, 'paymentSuccess'])
        ->name('api.credits.payment.success');
    Route::match(['get', 'post'], 'payment/cancel/{transaction_id}/{gateway}', [\App\Http\Controllers\Api\marketing\CreditController::class, 'paymentCancel'])
        ->name('api.credits.payment.cancel');
});

Route::prefix('v1/whatsapp-addons')->group(function () {
    // Accept both GET and POST because some gateways call back with POST
    Route::match(['get', 'post'], 'payment/success/{addon_id}/{gateway}', [\App\Http\Controllers\Api\apps\whatsapp\WhatsappAddonController::class, 'paymentSuccess'])
        ->name('api.whatsapp.addons.payment.success');
    Route::match(['get', 'post'], 'payment/cancel/{addon_id}/{gateway}', [\App\Http\Controllers\Api\apps\whatsapp\WhatsappAddonController::class, 'paymentCancel'])
        ->name('api.whatsapp.addons.payment.cancel');
});

Route::prefix('v1/employee-addons')->group(function () {
    // Accept both GET and POST because some gateways call back with POST
    Route::match(['get', 'post'], 'payment/success/{addon_id}/{gateway}', [\App\Http\Controllers\Api\apps\employee\EmployeeAddonController::class, 'paymentSuccess'])
        ->name('api.employee.addons.payment.success');
    Route::match(['get', 'post'], 'payment/cancel/{addon_id}/{gateway}', [\App\Http\Controllers\Api\apps\employee\EmployeeAddonController::class, 'paymentCancel'])
        ->name('api.employee.addons.payment.cancel');
});

// Theme payment callbacks (public)
Route::prefix('themes')->group(function () {
    // Accept both GET and POST because some gateways call back with POST
    Route::match(['get', 'post'], 'payment/success/{user_theme_id}/{gateway}', [ThemeSettingsController::class, 'paymentSuccess'])
        ->name('api.themes.payment.success');
    Route::match(['get', 'post'], 'payment/cancel/{user_theme_id}/{gateway}', [ThemeSettingsController::class, 'paymentCancel'])
        ->name('api.themes.payment.cancel');
});

// --- WhatsApp (requires active membership) ---
Route::middleware(['auth:sanctum', \App\Http\Middleware\RequireActiveMembership::class])->group(function () {
    Route::post('/whatsapp/link', [WhatsappController::class, 'store']);
    Route::get('/whatsapp', [WhatsappController::class, 'index']);
    Route::match(['put', 'patch'], '/whatsapp/{id}/employee', [WhatsappController::class, 'updateEmployee']);
    Route::delete('/whatsapp/{id}', [WhatsappController::class, 'destroy']);
    Route::post('/whatsapp/{id}/unlink', [WhatsappController::class, 'unlink']);
    Route::post('/whatsapp/{id}/link', [WhatsappController::class, 'link']);
    Route::get('/whatsapp/addons/plans', [WhatsappAddonController::class, 'plans']);
    Route::post('/whatsapp/addons', [WhatsappAddonController::class, 'store']);

    // Employee Addons
    Route::get('/employee/addons/plans', [\App\Http\Controllers\Api\apps\employee\EmployeeAddonController::class, 'plans']);
    Route::get('/employee/addons', [\App\Http\Controllers\Api\apps\employee\EmployeeAddonController::class, 'index']);
    Route::post('/employee/addons', [\App\Http\Controllers\Api\apps\employee\EmployeeAddonController::class, 'store']);
});


// --- V1: RMS / PMS / Property requests / Employee / CRM / Marketing / Credits ---
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::prefix('rms')->middleware(['can:rentals.view'])->group(function () {
        // Dashboard
        Route::get('dashboard', [RmsDashboardController::class, 'index']);
        Route::get('sales-stats', [RmsDashboardController::class, 'salesStats']);

        // Filtered Payments Endpoints
        Route::get('payments/collections', [RmsDashboardController::class, 'paymentsCollections']);
        Route::get('payments/due', [RmsDashboardController::class, 'paymentsDue']);

        // Rentals
        Route::get('rentals/filter-options', [RentalController::class, 'filterOptions']);
        Route::get('rentals', [RentalController::class, 'index']);
        Route::post('rentals', [RentalController::class, 'store']);
        Route::get('rentals/{id}', [RentalController::class, 'show']);
        Route::get('rentals/{id}/details', [RentalController::class, 'propertyDetails']);
        Route::get('rentals/{id}/details-with-payments', [RentalController::class, 'detailsWithPayments']);
        Route::get('rentals/{id}/current-collections', [RentalController::class, 'currentCollections']);
        // get all payment collections (without rental ID)
        Route::get('payment-collection', [RentalController::class, 'allPaymentCollections']);
        // get and post payment collection for specific rental
        Route::get('rentals/{id}/payment-collection', [RentalController::class, 'paymentCollection']);
        Route::post('rentals/{id}/collect-payment', [RentalController::class, 'collectPayment']);
        Route::get('rentals/{id}/payments', [RentalController::class, 'listPayments']);
        Route::post('rentals/{rental}/payments/{payment}/reverse', [RentalController::class, 'reversePayment']);

        // Upload receipt image
        Route::post('rentals/upload-receipt-image', [RentalController::class, 'uploadReceiptImage']);

        Route::patch('rentals/{id}', [RentalController::class, 'update']);
        Route::patch('rentals/{id}/status', [RentalController::class, 'updateStatus']);
        Route::delete('rentals/{id}', [RentalController::class, 'destroy']);
        Route::post('rentals/{id}/end-contract', [RentalController::class, 'endContract']);
        Route::post('rentals/{id}/renew', [RentalController::class, 'renewRental']);

        // Expenses
        Route::post('expenses/upload-image', [ExpenseController::class, 'uploadImage']);
        Route::get('rentals/{rentalId}/expenses', [ExpenseController::class, 'index']);
        Route::post('rentals/{rentalId}/expenses', [ExpenseController::class, 'store']);
        // Route::patch('rentals/{rentalId}/expenses/{expenseId}', [ExpenseController::class, 'update']);
        Route::delete('rentals/{rentalId}/expenses/{expenseId}', [ExpenseController::class, 'destroy']);

        // Payment Report
        Route::get('payment-report', [RentalController::class, 'paymentReport']);

        // Daily Follow-up
        Route::get('daily-follow-up', [RentalController::class, 'dailyFollowUp']);

        // All Contracts (list all contracts with detailed information)
        Route::get('contracts', [RentalController::class, 'allContracts']);

        // Contracts
        Route::get('rentals/{rentalId}/contracts', [ContractController::class, 'listByRental']);
        Route::post('rentals/{rentalId}/contracts', [ContractController::class, 'store']);
        Route::patch('contracts/{id}', [ContractController::class, 'update']);
        Route::post('contracts/{id}/terminate', [ContractController::class, 'terminate']);
        Route::patch('contracts/{id}/status', [ContractController::class, 'changeStatus']);

        // Installments
        Route::get('installments', [InstallmentController::class, 'index']);
        Route::patch('installments/{id}', [InstallmentController::class, 'update']);
        Route::post('rentals/{rentalId}/installments/regenerate', [InstallmentController::class, 'regenerate']);

        // Maintenance
        Route::get('maintenance', [MaintenanceController::class, 'index']);
        Route::post('maintenance', [MaintenanceController::class, 'store']);
        Route::get('maintenance/{id}', [MaintenanceController::class, 'show']);
        Route::patch('maintenance/{id}', [MaintenanceController::class, 'update']);
        Route::post('maintenance/{id}/status', [MaintenanceController::class, 'updateStatus']);
        Route::delete('maintenance/{id}', [MaintenanceController::class, 'destroy']);

        // Reminders
        Route::get('reminders', [RmsReminderController::class, 'index']);
        Route::post('reminders/{id}/dismiss', [RmsReminderController::class, 'dismiss']);
        Route::post('reminders/{id}/snooze', [RmsReminderController::class, 'snooze']);
    });

    // Purchase Management System
    Route::prefix('pms')->group(function () {
        // Dashboard and Statistics
        Route::get('dashboard', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'dashboard']);

        // Helper endpoints for dropdowns
        Route::get('properties', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'getProperties']);
        Route::get('projects', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'getProjects']);
        Route::get('staff', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'getStaff']);

        // Purchase Requests CRUD
        Route::get('purchase-requests', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'index']);
        Route::post('purchase-requests', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'store']);
        Route::get('purchase-requests/{id}', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'show']);
        Route::patch('purchase-requests/{id}', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'update']);
        Route::delete('purchase-requests/{id}', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'destroy']);

        // Stage Transition
        Route::post('purchase-requests/{id}/transition-stage', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'transitionStage']);
        Route::post('purchase-requests/{id}/simple-transition-stage', [\App\Http\Controllers\Api\PurchaseRequestController::class, 'simpleTransitionStage']);

        // Purchase Request Stages
        Route::get('purchase-requests/{purchaseRequestId}/stages', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'index']);
        Route::get('purchase-requests/{purchaseRequestId}/stages/statistics', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'statistics']);
        Route::get('purchase-requests/{purchaseRequestId}/stages/{stageId}', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'show']);

        // Stage Status Updates
        Route::patch('purchase-requests/{purchaseRequestId}/stages/{stageId}/status', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'updateStatus']);
        Route::patch('purchase-requests/{purchaseRequestId}/stages/{stageId}/notes', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'updateNotes']);
        Route::patch('purchase-requests/{purchaseRequestId}/stages/bulk-update', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'bulkUpdate']);

        // Stage Action Helpers
        Route::post('purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-completed', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'markCompleted']);
        Route::post('purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-in-progress', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'markInProgress']);
        Route::post('purchase-requests/{purchaseRequestId}/stages/{stageId}/mark-pending', [\App\Http\Controllers\Api\PurchaseRequestStageController::class, 'markPending']);
    });

    // ApiCustomerInquiry
    Route::get('/inquiry', [CustomerInquiryController::class, 'index']);

    // ApiPropertyRequestController
    Route::middleware(['can:property_requests.view'])->group(function () {
        Route::get('/property-requests/filters', [ApiPropertyRequestController::class, 'filterOptions'])->middleware('can:property_requests.view');
        Route::get('/property-requests/stats', [ApiPropertyRequestController::class, 'stats'])->middleware('can:property_requests.view');
        Route::get('/property-requests', [ApiPropertyRequestController::class, 'index'])->middleware('can:property_requests.view');
        Route::get('/property-requests/map', [ApiPropertyRequestController::class, 'map']);
        Route::post('/property-requests', [ApiPropertyRequestController::class, 'store'])->middleware('can:property_requests.create');
        // Property IDs on request (must be before {id} so that .../properties is matched)
        Route::post('/property-requests/{id}/properties', [ApiPropertyRequestController::class, 'attachProperties'])->middleware('can:property_requests.update');
        Route::delete('/property-requests/{id}/properties/{propertyId}', [ApiPropertyRequestController::class, 'detachProperty'])->middleware('can:property_requests.update');
        Route::get('/property-requests/{id}', [ApiPropertyRequestController::class, 'show'])->middleware('can:property_requests.view');
        // DELETE
        Route::delete('/property-requests/{id}', [ApiPropertyRequestController::class, 'destroy'])->middleware('can:property_requests.delete');
        // update
        Route::put('/property-requests/{id}', [ApiPropertyRequestController::class, 'update'])->middleware('can:property_requests.update');
        // update status
        Route::put('/property-requests/{id}/status', [ApiPropertyRequestController::class, 'updateStatus'])->middleware('can:property_requests.update');
        // update priority
        Route::put('/property-requests/{id}/priority', [ApiPropertyRequestController::class, 'updatePriority'])->middleware('can:property_requests.update');
        // assign employee to customer (must come before property request employee route to avoid route conflict)
        Route::put('/property-requests/customer/{customerID}/employee', [ApiPropertyRequestController::class, 'assignEmployeeToCustomer'])->middleware('can:property_requests.update');
        // update employee (property request)
        Route::put('/property-requests/{id}/employee', [ApiPropertyRequestController::class, 'updateEmployee'])->middleware('can:property_requests.update');

        // Property Request Statuses CRUD (global defaults + per-tenant custom)
        Route::get('/property-request-statuses', [PropertyRequestStatusController::class, 'index'])->middleware('can:property_requests.view');
        Route::post('/property-request-statuses', [PropertyRequestStatusController::class, 'store'])->middleware('can:property_requests.update');
        Route::put('/property-request-statuses/{id}', [PropertyRequestStatusController::class, 'update'])->middleware('can:property_requests.update');
        Route::delete('/property-request-statuses/{id}', [PropertyRequestStatusController::class, 'destroy'])->middleware('can:property_requests.update');
    });




    // ApiPropertyRequestSettingsController
    Route::prefix('property-request-settings')->group(function () {
        Route::get('/',        [ApiPropertyRequestSettingsController::class, 'index']);       // ?merged=true|false
        Route::get('/defaults',[ApiPropertyRequestSettingsController::class, 'defaults']); // ?merged=true|false

        Route::post('/bulk',   [ApiPropertyRequestSettingsController::class, 'bulkUpsert']);  // upsert مجموعة
        Route::put('/{field}', [ApiPropertyRequestSettingsController::class, 'updateOne']);   // تعديل مفتاح واحد
        Route::post('/reset',  [ApiPropertyRequestSettingsController::class, 'reset']);       // حذف إعدادات (العودة للديفولت)
    });

    // ===== Employee Auth (PUBLIC) =====
    // Route::prefix('em/auth')->group(function () {
    //     Route::post('login',    [EmployeeAuthController::class, 'login']);
    //     Route::post('register', [EmployeeAuthController::class, 'register']);
    // });
    // Employee API
    Route::middleware(['auth:sanctum','employee.only','log.employee.activity'])->group(function () {
        // Protected
        Route::get('auth/me',     [EmployeeAuthController::class, 'me']);
        Route::post('auth/logout',[EmployeeAuthController::class, 'logout']);

        Route::apiResource('customers', EmployeeCustomerController::class);
        //  ->middleware('employee.can:customer.read');

    });

    // Tenant owner
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/logs', [\App\Http\Controllers\Api\V1\LogController::class,'index'])
            ->middleware('can:logs.read'); // owners pass via Gate::before
    });

    Route::middleware(['log.employee.activity'])->prefix('crm')->group(function () {
        Route::get('cards', [CrmCardController::class, 'index']);
        Route::post('cards', [CrmCardController::class, 'store']);
        Route::get('cards/{id}', [CrmCardController::class, 'show']);
        Route::match(['put','patch'], 'cards/{id}', [CrmCardController::class, 'update']);
        Route::delete('cards/{id}', [CrmCardController::class, 'destroy']);

        // Requests
        Route::apiResource('requests', CrmRequestController::class);
        Route::post('requests/{id}/change-stage', [CrmRequestController::class, 'changeStage']);
        Route::post('requests/reorder', [CrmRequestController::class, 'reorder']);
        Route::get('requests/{id}/details', [CrmRequestController::class, 'details']);

        // User Stages (v1 scoped)
        Route::get('stages', [CrmRequestController::class, 'stages']);
    });

    // Marketing Channels Routes
    Route::prefix('marketing')->group(function () {
        Route::get('channels', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'index']);
        Route::post('channels', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'store']);
        Route::get('channels/types', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getChannelTypes']);
        // i want to return for each channel calc how much credits used and how much messages sent all channels return it as object
        Route::get('channels/usage', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getUsage']);
        Route::get('channels/{id}', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'show']);
        Route::put('channels/{id}', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'update']);
        Route::patch('channels/{id}/status', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'updateStatus']);
        Route::get('channels/{id}/statistics', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'statistics']);
        Route::get('channels/{id}/stats', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'stats']);
        Route::post('channels/{id}/sync-verified', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'syncVerified']);
        Route::post('channels/{id}/send-message', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'sendMessage']);
        Route::post('channels/send-whatsapp-to-customer', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'sendWhatsAppToCustomer']);
        Route::get('channels/messages', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getMessages']);
        Route::get('channels/messages/stats', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getMessageStats']);
        Route::delete('channels/{id}', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'destroy']);

        // Marketing Settings Routes
        Route::get('settings', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getAllMarketingSettings']);
        Route::get('channels/{id}/settings', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'getMarketingSettings']);
        Route::put('channels/{id}/settings', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'updateMarketingSettings']);
        Route::patch('channels/{id}/system-integrations', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'updateSystemIntegrationSettings']);

    });

    // Marketing Webhooks Routes (no auth required for webhooks)
    Route::prefix('marketing/webhooks')->group(function () {
        Route::post('whatsapp', [\App\Http\Controllers\Api\marketing\MarketingChannelController::class, 'whatsappWebhook']);
    });

    // Authenticated Credit Management Routes
    Route::prefix('credits')->middleware(['auth:sanctum'])->group(function () {
        Route::get('balance', [\App\Http\Controllers\Api\marketing\CreditController::class, 'getBalance']);
        Route::post('purchase', [\App\Http\Controllers\Api\marketing\CreditController::class, 'purchasePackage']);
        Route::get('transactions', [\App\Http\Controllers\Api\marketing\CreditController::class, 'getTransactions']);
        Route::get('analytics', [\App\Http\Controllers\Api\marketing\CreditController::class, 'getAnalytics']);
    });


});



// --- Me abilities / RBAC ---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/me/abilities', [MeAbilitiesController::class, 'index']);

    Route::get('/v1/rbac/perms/me', [PermissionController::class, 'me']);


    Route::middleware('can:settings.update')->group(function () {
        Route::get('/v1/rbac/roles',           [RoleController::class, 'index']);
        Route::post('/v1/rbac/roles',           [RoleController::class, 'store']);
        Route::put('/v1/rbac/roles/{role}',    [RoleController::class, 'update']);
        Route::delete('/v1/rbac/roles/{role}',    [RoleController::class, 'destroy']);

        Route::get('/v1/rbac/permissions',                         [PermissionAdminController::class, 'index']);
        Route::post('/v1/rbac/permissions',                        [PermissionAdminController::class, 'store']);
        Route::put('/v1/rbac/permissions/{permission}',            [PermissionAdminController::class, 'update']);
        Route::delete('/v1/rbac/permissions/{permission}',         [PermissionAdminController::class, 'destroy']);

        Route::get('/v1/rbac/employees-show-roles/{employee}/roles',          [AssignmentController::class, 'showRoles']);
        Route::get('/v1/rbac/show-employees-data/{employee}',          [PermissionController::class, 'showEmployeeData']);

        Route::post('/v1/rbac/employees-sync-perms/{employee}/perms',         [AssignmentController::class, 'syncPerms']);
        Route::post('/v1/rbac/employees-sync-roles/{employee}/roles',         [AssignmentController::class, 'syncRoles']);
    });


});


// --- V1: Reservations / Job applications / Logs ---
Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', 'audit.ctx'])->group(function () {
		Route::prefix('reservations')->group(function () {
			Route::get('/', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'index']);
			Route::get('/stats', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'stats']);
			Route::get('/export/csv', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'exportCsv']);
			Route::get('/{id}', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'show']);
			Route::post('/{id}/accept', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'accept'])->name('reservations.accept');
			Route::post('/{id}/reject', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'reject'])->name('reservations.reject');
			Route::post('/bulk-action', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'bulkAction']);
		});

		Route::prefix('job-applications')->group(function () {
			Route::get('/', [\App\Http\Controllers\Api\V1\JobApplicationController::class, 'index'])->middleware('can:job_applications.view');
			Route::get('/{id}', [\App\Http\Controllers\Api\V1\JobApplicationController::class, 'show'])->middleware('can:job_applications.view');
		});

        Route::get('/customers/{id}/logs',  [CustomerLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/projects/{id}/logs',   [ProjectLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/properties/{id}/logs', [PropertyLogController::class, 'index'])->middleware('can:properties.view');
        Route::get('/crm/cards/{id}/logs', [CardLogController::class, 'index'])->middleware('can:crm.cards.view');
        // crm/cards
    });

    // --- Employees ---
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('employees/available-roles', [EmployeeController::class, 'availableRoles']);
        Route::get('employees/available-permissions', [EmployeeController::class, 'availablePermissions']);
        Route::apiResource('employees', EmployeeController::class);
    });

    // --- Roles ---
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('permissions', [RoleController::class, 'permissions']);
        Route::post('permissions', [RoleController::class, 'storePermission']);
        Route::put('permissions/{id}', [RoleController::class, 'updatePermission']);
        Route::delete('permissions/{id}', [RoleController::class, 'destroyPermission']);
    });
});

// --- Pixels ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/pixels', [PixelController::class, 'index']);
    Route::post('/pixels', [PixelController::class, 'store']);
    Route::get('/pixels/{id}', [PixelController::class, 'show']);
    Route::put('/pixels/{id}', [PixelController::class, 'update']);
    Route::delete('/pixels/{id}', [PixelController::class, 'destroy']);
    Route::patch('/pixels/{id}/toggle-status', [PixelController::class, 'toggleStatus']);
});

// --- Video upload ---
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('video')->group(function () {
        Route::post('/upload', [App\Http\Controllers\Api\VideoUploadController::class, 'uploadVideo']);
        Route::post('/initiate-chunked', [App\Http\Controllers\Api\VideoUploadController::class, 'initiateChunkedUpload']);
        Route::post('/upload-chunk', [App\Http\Controllers\Api\VideoUploadController::class, 'uploadChunk']);
        Route::post('/complete-chunked', [App\Http\Controllers\Api\VideoUploadController::class, 'completeChunkedUpload']);
        Route::post('/abort-chunked', [App\Http\Controllers\Api\VideoUploadController::class, 'abortChunkedUpload']);
        Route::post('/signed-url', [App\Http\Controllers\Api\VideoUploadController::class, 'getSignedUploadUrl']);
        Route::delete('/delete', [App\Http\Controllers\Api\VideoUploadController::class, 'deleteVideo']);
    });
});

// Debug (public, non-production only)
if (!app()->environment('production')) {
    Route::get('/debug-oss', function () {
        return [
            'env_oss_endpoint' => env('OSS_ENDPOINT'),
            'env_oss_bucket' => env('OSS_BUCKET'),
            'env_oss_key' => env('OSS_ACCESS_KEY_ID'),
            'env_oss_secret' => env('OSS_ACCESS_KEY_SECRET'),
            'config_oss_endpoint' => config('filesystems.disks.oss.endpoint'),
            'config_oss_bucket' => config('filesystems.disks.oss.bucket'),
            'config_oss_key' => config('filesystems.disks.oss.key'),
            'config_oss_secret' => config('filesystems.disks.oss.secret'),
            'all_oss_config' => config('filesystems.disks.oss'),
        ];
    });
}

// WhatsApp webhook routes
//Route::post('/whatsapp/webhook', [App\Http\Controllers\Api\WhatsAppWebhookController::class, 'handleWebhook']);

// Tenant Website API (mixed: some routes public, some require auth:sanctum)
Route::prefix('v1/tenant-website')->middleware(['api','tenant.resolve','tenant.id.response'])->group(function () {
    Route::post('getTenant', [GetTenantController::class, 'store']);
    Route::post('save-pages', [SavePagesController::class, 'store'])->middleware('auth:sanctum');

    // Tenant Website Pixels (public)
    Route::get('{tenantId}/pixels', [TenantWebsitePixelController::class, 'index'])->middleware('throttle:api_standard_60');

    // Unified search endpoint (public) - must be before more specific routes
    Route::get('{tenantId}', [\App\Http\Controllers\Api\V1\TenantWebsite\SearchController::class, 'index']);

    Route::get('{tenantId}/pages', [PageController::class, 'index']);
    Route::get('{tenantId}/pages/{pageId}', [PageController::class, 'show']);
    Route::post('{tenantId}/pages', [PageController::class, 'store'])->middleware('auth:sanctum');
    Route::put('{tenantId}/pages/{pageId}', [PageController::class, 'update'])->middleware('auth:sanctum');
    Route::patch('{tenantId}/pages/{pageId}', [PageController::class, 'patch'])->middleware('auth:sanctum');
    Route::delete('{tenantId}/pages/{pageId}', [PageController::class, 'destroy'])->middleware('auth:sanctum');

    Route::put('{tenantId}/globals', [GlobalsController::class, 'update'])->middleware('auth:sanctum');
    Route::get('components/catalog', [ComponentCatalogController::class, 'index']);
    Route::post('{tenantId}/media', [MediaController::class, 'store'])->middleware('auth:sanctum');
    Route::put('{tenantId}/settings', [SettingsController::class, 'update'])->middleware('auth:sanctum');
    Route::post('{tenantId}/publish', [PublishController::class, 'store'])->middleware('auth:sanctum');
    Route::post('{tenantId}/forms/contact', [FormController::class, 'store']);

	// Tenant Website Reservations (public - rate limited)
	Route::post('{tenantId}/reservations', [\App\Http\Controllers\Api\V1\TenantWebsite\ReservationController::class, 'store'])->middleware('throttle:api_tenant_reservations');

	Route::post('{tenantId}/job-applications', [\App\Http\Controllers\Api\V1\TenantWebsite\JobApplicationController::class, 'store'])->middleware('throttle:api_tenant_job_applications');

    // Tenant Website Properties (public)
    Route::get('{tenantId}/properties', [\App\Http\Controllers\Api\V1\TenantWebsite\PropertyController::class, 'index']);
    Route::get('{tenantId}/properties/most-viewed', [\App\Http\Controllers\Api\V1\TenantWebsite\PropertyController::class, 'mostViewed']);
    Route::get('{tenantId}/properties/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\PropertyController::class, 'show']);

    // Tenant Website Projects (public)
    Route::get('{tenantId}/projects', [\App\Http\Controllers\Api\V1\TenantWebsite\ProjectController::class, 'index']);
    Route::get('{tenantId}/projects/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\ProjectController::class, 'show']);

    Route::get('{tenantId}/buildings', [\App\Http\Controllers\Api\V1\TenantWebsite\BuildingController::class, 'index']);
    Route::get('{tenantId}/buildings/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\BuildingController::class, 'show']);

    // Tenant Website Posts (public)
    Route::get('{tenantId}/posts', [\App\Http\Controllers\Api\V1\TenantWebsite\PostController::class, 'index']);
    Route::get('{tenantId}/posts/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\PostController::class, 'show']);

    // Tenant Website AI Export (public)
    Route::get('{tenantId}/ai-export', [\App\Http\Controllers\Api\V1\TenantWebsite\AiExportController::class, 'index']);
    Route::get('{tenantId}/ai-export.txt', [\App\Http\Controllers\Api\V1\TenantWebsite\AiExportController::class, 'downloadTxt']);

    // Unified search endpoint (public) - must be last to avoid conflicts with specific routes
    Route::get('{tenantId}', [\App\Http\Controllers\Api\V1\TenantWebsite\SearchController::class, 'index']);

    // (moved Matching endpoints out of tenant-website scope)
});

// Matching Endpoints (Dashboard APIs, require auth) - observer-only, retrieval endpoints
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('matching')->group(function () {
        // Customer Requests (unified web + whatsapp)
        Route::get('requests', [V1CustomerRequestController::class, 'index']);
        Route::get('requests/{type}/{id}', [V1CustomerRequestController::class, 'show']);
        Route::put('requests/{type}/{id}', [V1CustomerRequestController::class, 'update']);
        Route::patch('requests/{type}/{id}/read', [V1CustomerRequestController::class, 'markAsRead']);
        Route::patch('requests/{type}/{id}/unread', [V1CustomerRequestController::class, 'markAsUnread']);
        Route::patch('requests/{type}/{id}/archive', [V1CustomerRequestController::class, 'archive']);
        Route::patch('requests/{type}/{id}/unarchive', [V1CustomerRequestController::class, 'unarchive']);

        Route::get('customers', [V1MatchingController::class, 'customers']);
        Route::get('customers/{customer_key}/properties', [V1MatchingController::class, 'customerProperties']);
        Route::get('matches/{id}', [V1MatchingController::class, 'showMatch']);
    });
});

// Communication read path (Phase 2) + write path (Phase 3)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [ConversationController::class, 'index']);
    Route::get('conversations/{id}', [ConversationController::class, 'show']);
    Route::get('conversations/{id}/messages', [MessageController::class, 'index']);
    Route::post('messages/send', [MessageController::class, 'send']);

    Route::prefix('communication/ops')->group(function () {
        Route::get('health', [\App\Http\Controllers\Api\V1\Communication\Ops\HealthController::class, '__invoke']);
        Route::get('reconciliation-summary', [\App\Http\Controllers\Api\V1\Communication\Ops\ReconciliationSummaryController::class, '__invoke']);
        Route::get('delivery-attempts', [\App\Http\Controllers\Api\V1\Communication\Ops\DeliveryAttemptsController::class, '__invoke']);
        Route::get('webhook-events', [\App\Http\Controllers\Api\V1\Communication\Ops\WebhookEventsController::class, '__invoke']);
        Route::get('stuck-items', [\App\Http\Controllers\Api\V1\Communication\Ops\StuckItemsController::class, '__invoke']);
    });

    Route::prefix('sms')->group(function () {
        Route::get('campaigns', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'index']);
        Route::get('campaigns/{id}', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'show']);
        Route::post('campaigns', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'store']);
        Route::patch('campaigns/{id}', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'update']);
        Route::delete('campaigns/{id}', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'destroy']);
        Route::post('campaigns/{id}/send', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'send']);
        Route::post('campaigns/{id}/pause', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'pause']);
        Route::post('campaigns/{id}/resume', [\App\Http\Controllers\Api\V1\Sms\CampaignController::class, 'resume']);

        Route::get('templates', [\App\Http\Controllers\Api\V1\Sms\TemplateController::class, 'index']);
        Route::get('templates/{id}', [\App\Http\Controllers\Api\V1\Sms\TemplateController::class, 'show']);
        Route::post('templates', [\App\Http\Controllers\Api\V1\Sms\TemplateController::class, 'store']);
        Route::patch('templates/{id}', [\App\Http\Controllers\Api\V1\Sms\TemplateController::class, 'update']);
        Route::delete('templates/{id}', [\App\Http\Controllers\Api\V1\Sms\TemplateController::class, 'destroy']);

        Route::post('messages/send', [\App\Http\Controllers\Api\V1\Sms\MessageController::class, 'send']);
        Route::get('logs', [\App\Http\Controllers\Api\V1\Sms\LogController::class, 'index']);
        Route::get('stats', [\App\Http\Controllers\Api\V1\Sms\StatsController::class, 'index']);
    });

    Route::prefix('email')->group(function () {
        Route::get('campaigns', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'index']);
        Route::get('campaigns/{id}', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'show']);
        Route::post('campaigns', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'store']);
        Route::patch('campaigns/{id}', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'update']);
        Route::delete('campaigns/{id}', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'destroy']);
        Route::post('campaigns/{id}/send', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'send']);
        Route::post('campaigns/{id}/pause', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'pause']);
        Route::post('campaigns/{id}/resume', [\App\Http\Controllers\Api\V1\Email\CampaignController::class, 'resume']);

        Route::get('templates', [\App\Http\Controllers\Api\V1\Email\TemplateController::class, 'index']);
        Route::get('templates/{id}', [\App\Http\Controllers\Api\V1\Email\TemplateController::class, 'show']);
        Route::post('templates', [\App\Http\Controllers\Api\V1\Email\TemplateController::class, 'store']);
        Route::patch('templates/{id}', [\App\Http\Controllers\Api\V1\Email\TemplateController::class, 'update']);
        Route::delete('templates/{id}', [\App\Http\Controllers\Api\V1\Email\TemplateController::class, 'destroy']);

        Route::get('stats', [\App\Http\Controllers\Api\V1\Email\StatsController::class, 'index']);
        Route::get('logs', [\App\Http\Controllers\Api\V1\Email\LogController::class, 'index']);
        Route::post('messages/send', [\App\Http\Controllers\Api\V1\Email\MessageController::class, 'send']);
    });

    Route::prefix('whatsapp')->group(function () {
        Route::get('numbers', [WhatsAppNumberController::class, 'index']);
        Route::get('numbers/{id}', [WhatsAppNumberController::class, 'show']);
        Route::post('numbers', [WhatsAppNumberController::class, 'store']);
        Route::put('numbers/{id}', [WhatsAppNumberController::class, 'update']);

        Route::get('conversations', [WhatsAppConversationController::class, 'index']);
        Route::get('conversations/{id}', [WhatsAppConversationController::class, 'show']);
        Route::post('conversations', [WhatsAppConversationController::class, 'store']);
        Route::patch('conversations/{id}', [WhatsAppConversationController::class, 'update']);
        Route::post('conversations/{id}/read', [WhatsAppConversationController::class, 'read']);
        Route::post('conversations/{id}/star', [WhatsAppConversationController::class, 'star']);

        Route::get('conversations/{id}/messages', [WhatsAppMessageController::class, 'index']);
        Route::post('conversations/{id}/messages', [WhatsAppMessageController::class, 'send']);
        Route::post('conversations/{id}/messages/template', [WhatsAppMessageController::class, 'sendTemplate']);

        Route::get('templates', [WhatsAppTemplateController::class, 'index']);
        Route::get('templates/{id}', [WhatsAppTemplateController::class, 'show']);
        Route::post('templates', [WhatsAppTemplateController::class, 'store']);
        Route::put('templates/{id}', [WhatsAppTemplateController::class, 'update']);
        Route::delete('templates/{id}', [WhatsAppTemplateController::class, 'destroy']);

        Route::get('automation/rules', [WhatsAppAutomationRuleController::class, 'index']);
        Route::get('automation/rules/{id}', [WhatsAppAutomationRuleController::class, 'show']);
        Route::post('automation/rules', [WhatsAppAutomationRuleController::class, 'store']);
        Route::put('automation/rules/{id}', [WhatsAppAutomationRuleController::class, 'update']);
        Route::patch('automation/rules/{id}/toggle', [WhatsAppAutomationRuleController::class, 'toggle']);
        Route::delete('automation/rules/{id}', [WhatsAppAutomationRuleController::class, 'destroy']);
        Route::get('automation/stats', [WhatsAppAutomationRuleController::class, 'stats']);

        Route::get('ai/config/{numberId}', [WhatsAppAiConfigController::class, 'show']);
        Route::put('ai/config/{numberId}', [WhatsAppAiConfigController::class, 'update']);
        Route::patch('ai/config/{numberId}/toggle', [WhatsAppAiConfigController::class, 'toggle']);
        Route::get('ai/stats', [WhatsAppAiConfigController::class, 'stats']);

        Route::get('stats', [WhatsAppStatsController::class, 'index']);
        Route::get('campaigns', [WaCampaignController::class, 'index']);
        Route::get('campaigns/{id}', [WaCampaignController::class, 'show']);
        Route::post('campaigns', [WaCampaignController::class, 'store']);
        Route::patch('campaigns/{id}', [WaCampaignController::class, 'update']);
        Route::delete('campaigns/{id}', [WaCampaignController::class, 'destroy']);
        Route::post('campaigns/{id}/send', [WaCampaignController::class, 'send']);
        Route::post('campaigns/{id}/pause', [WaCampaignController::class, 'pause']);
        Route::post('campaigns/{id}/resume', [WaCampaignController::class, 'resume']);
    });
});

Route::post('v1/sms/webhooks/delivery', [\App\Http\Controllers\Api\V1\Sms\WebhookController::class, 'delivery']);
Route::post('v1/email/webhooks/delivery', [\App\Http\Controllers\Api\V1\Email\WebhookController::class, 'delivery']);

Route::get('v1/whatsapp/webhook/verify', [WhatsAppWebhookController::class, 'verify']);
Route::post('v1/whatsapp/webhook/incoming', [WhatsAppWebhookController::class, 'incoming']);
Route::post('v1/whatsapp/webhook/status', [WhatsAppWebhookController::class, 'status']);
Route::post('v1/whatsapp/webhook/verify', [WhatsAppWebhookController::class, 'verifyPost']);

// Direct public route for property categories (bypassing tenant.resolve middleware)
Route::get('v1/tenant-website/{tenantId}/properties/categories/direct', [PropertyController::class, 'properties_categories']);

// =============================================================================
// CUSTOMERS HUB API (v2) - Unified Customer Management System
// =============================================================================
Route::prefix('v2/customers-hub')->middleware(['auth:sanctum'])->group(function () {

    // 1. REQUESTS/ACTIONS CENTER
    Route::prefix('requests')->group(function () {
        // List with filtering (POST for complex payloads, GET for compatibility)
        Route::post('/list', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'list']);
        Route::get('/list', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'list']);

        // Filter options (cached)
        Route::get('/filter-options', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'filterOptions']);

        // Bulk operations (unified endpoint - must be before /{requestId})
        Route::post('/bulk', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'bulk']);
        Route::post('/bulk-complete', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'bulkComplete']);
        Route::post('/bulk-dismiss', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'bulkDismiss']);

        // Mark requests list as viewed (per viewer) for isUpdated flags
        Route::post('/mark-viewed', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'markListViewed']);

        // Property request appointments and reminders (must be before /{requestId})
        Route::post('/{requestId}/appointments', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'createAppointmentForPropertyRequest']);
        Route::post('/{requestId}/reminders', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'createReminderForPropertyRequest']);

        // Matching V2 endpoints (must be before /{requestId})
        Route::get('/{requestId}/matches', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'matches']);
        Route::post('/{requestId}/complete-data', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'completeData']);
        Route::patch('/{requestId}/read', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'markRead']);
        Route::patch('/{requestId}/unread', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'markUnread']);
        Route::patch('/{requestId}/ignore', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'ignore']);
        Route::post('/{requestId}/rematch', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'rematch']);

        // Single action detail
        Route::get('/{requestId}', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'show']);
        Route::get('/{requestId}/stats', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'actionStats']);

        // Action operations
        Route::post('/{requestId}/complete', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'complete']);
        Route::post('/{requestId}/dismiss', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'dismiss']);
        Route::post('/{requestId}/snooze', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'snooze']);
        Route::patch('/{requestId}', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'update']);
        Route::post('/{requestId}/notes', [\App\Http\Controllers\Api\V2\CustomersHub\RequestsController::class, 'addNote']);
    });

    // 2. CUSTOMERS LIST
    Route::prefix('list')->group(function () {
        // Main list endpoint (supports list, stats actions)
        Route::post('/', [\App\Http\Controllers\Api\V2\CustomersHub\ListController::class, 'list']);

        // Statistics endpoint
        Route::get('/stats', [\App\Http\Controllers\Api\V2\CustomersHub\ListController::class, 'stats']);

        // Filter options
        Route::get('/filter-options', [\App\Http\Controllers\Api\V2\CustomersHub\ListController::class, 'filterOptions']);

        // Bulk operations
        Route::post('/bulk', [\App\Http\Controllers\Api\V2\CustomersHub\ListController::class, 'bulk']);
    });

    // 3. PIPELINE (KANBAN BOARD)
    Route::prefix('pipeline')->group(function () {
        // Main pipeline endpoint (board data + analytics)
        Route::post('/', [\App\Http\Controllers\Api\V2\CustomersHub\PipelineController::class, 'index']);

        // Move operations
        Route::post('/move', [\App\Http\Controllers\Api\V2\CustomersHub\PipelineController::class, 'move']);
        Route::post('/bulk-move', [\App\Http\Controllers\Api\V2\CustomersHub\PipelineController::class, 'bulkMove']);
    });

    // 4. ANALYTICS DASHBOARD
    Route::post('/analytics', [\App\Http\Controllers\Api\V2\CustomersHub\AnalyticsController::class, 'index']);
    Route::post('/analytics/trends', [\App\Http\Controllers\Api\V2\CustomersHub\AnalyticsController::class, 'trends']);
    Route::post('/analytics/sources', [\App\Http\Controllers\Api\V2\CustomersHub\AnalyticsController::class, 'sources']);
    Route::post('/analytics/performance', [\App\Http\Controllers\Api\V2\CustomersHub\AnalyticsController::class, 'performance']);

    // 5. CUSTOMER DETAIL
    Route::prefix('customers')->group(function () {
        // Get customer details (customer + tasks + properties + preferences)
        Route::get('/{customerId}', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'show']);

        // Update customer
        Route::put('/{customerId}', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'update']);

        // Tasks management
        Route::post('/{customerId}/tasks', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'addTask']);
        Route::put('/{customerId}/tasks/{taskId}', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'updateTask']);
        Route::delete('/{customerId}/tasks/{taskId}', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'deleteTask']);

        // Preferences/Requirements
        Route::put('/{customerId}/preferences', [\App\Http\Controllers\Api\V2\CustomersHub\DetailController::class, 'updatePreferences']);

        // Assigned properties (pivot api_customer_assigned_property)
        Route::post('/{customerId}/properties', [\App\Http\Controllers\Api\V2\CustomersHub\CustomerPropertiesController::class, 'addProperty']);
        Route::get('/{customerId}/properties', [\App\Http\Controllers\Api\V2\CustomersHub\CustomerPropertiesController::class, 'listProperties']);
        Route::delete('/{customerId}/properties/{propertyId}', [\App\Http\Controllers\Api\V2\CustomersHub\CustomerPropertiesController::class, 'removeProperty']);
    });

    // 6. ASSIGNMENT
    Route::prefix('assignment')->group(function () {
        // Get employees with workload stats
        Route::get('/employees', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'employees']);

        // Get unassigned count
        Route::get('/unassigned-count', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'unassignedCount']);

        // Auto assign customers
        Route::post('/auto-assign', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'autoAssign']);

        // Manual assign customers
        Route::post('/assign', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'assign']);

        // Save assignment rules
        Route::post('/rules', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'saveRules']);

        // Get assignment rules
        Route::get('/rules', [\App\Http\Controllers\Api\V2\CustomersHub\AssignmentController::class, 'getRules']);
    });

    // 7. STAGES (dynamic stages for pipeline)
    Route::prefix('stages')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\CustomersHub\StagesController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V2\CustomersHub\StagesController::class, 'store']);
        Route::put('/{stage_id}', [\App\Http\Controllers\Api\V2\CustomersHub\StagesController::class, 'update']);
        Route::delete('/{stage_id}', [\App\Http\Controllers\Api\V2\CustomersHub\StagesController::class, 'destroy']);
    });

    // 8. IGNORE LIST
    Route::prefix('ignored-customers')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V2\CustomersHub\IgnoredCustomersController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\V2\CustomersHub\IgnoredCustomersController::class, 'store']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V2\CustomersHub\IgnoredCustomersController::class, 'destroy']);
    });
});

// Owner Rental Management System Routes (v1)
// User Dashboard - Managing Owner Rentals (requires user authentication)
Route::prefix('v1/user/owner-rentals')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'store']);
    Route::get('/{id}', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'show']);
    Route::put('/{id}', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'destroy']);
    Route::post('/{id}/properties', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'assignProperties']);
    Route::delete('/{id}/properties/{propertyId}', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'removeProperty']);
    Route::get('/{id}/properties', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'getAssignedProperties']);
});

// User Properties for Owner Rental Assignment (requires user authentication)
Route::prefix('v1/user')->middleware('auth:sanctum')->group(function () {
    Route::get('/properties', [\App\Http\Controllers\User\OwnerRentalManagementController::class, 'getMyProperties']);
});

// Owner Rental Authentication Routes (v1 - public)
Route::prefix('v1/owner-rental')->group(function () {
    Route::post('/login', [\App\Http\Controllers\OwnerRental\AuthController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\OwnerRental\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\OwnerRental\AuthController::class, 'resetPassword']);

    // Protected Owner Rental Routes (requires owner-rental authentication)
    Route::middleware([\App\Http\Middleware\OwnerRentalAuth::class])->group(function () {
        Route::post('/logout', [\App\Http\Controllers\OwnerRental\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\OwnerRental\AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'dashboard']);

        // Temporary route to check property associations
        Route::get('/check-property/{id}', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'checkProperty']);

        // Properties
        Route::get('/properties', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'properties']);
        Route::get('/properties/{id}', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'propertyDetails']);

        // Rentals
        Route::get('/rentals', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'rentals']);

        // Tenants
        Route::get('/tenants', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'tenants']);

        // Financial Reports
        Route::get('/financial-reports', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'financialReports']);

        // Maintenance Requests
        Route::get('/maintenance-requests', [\App\Http\Controllers\OwnerRental\DashboardController::class, 'maintenanceRequests']);
    });
});
