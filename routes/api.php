<?php

use App\Http\Middleware\SetTenantForPermissions; // the middleware we added earlier

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
    Affiliate\AffiliateController,
    App\ApiInstallationController,
    dashboard\DashboardController,
    property\UserFacadeController,
    apps\whatsapp\WhatsappController,
    apps\whatsapp\EmbeddingController,
    User\RealestateManagement\ApiCategoryController,
    ResetPasswordController,
    property\ApiPropertyRequestController,
    property\ApiPropertyRequestSettingsController,
    blog\BlogController,
    project\ProjectController,
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

use App\Http\Controllers\Api\V1\{
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
    ReminderController,
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
};
use App\Http\Controllers\Api\V1\Matching\MatchingController as V1MatchingController;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('public-user/{id}', [PublicUserController::class, 'show']);
Route::get('/properties/bulk-import/template', [PropertyController::class, 'downloadTemplate']);
Route::get('/customers/bulk-import/template', [CustomerController::class, 'downloadTemplate'])->middleware('auth:sanctum');

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
Route::middleware(['auth:sanctum', SetTenantForPermissions::class])->group(function () {
    Route::get('/user', [AuthController::class, 'getUserProfile']);
    Route::get('/user/getUserInfo', [AuthController::class, 'getUserProfile']); // Alias for frontend compatibility
    Route::post('/user-read-message', [AuthController::class, 'read_message']);
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

// Public referral validation endpoints
Route::get('/referrals', [ReferralController::class, 'validateCode']); // /api/referrals?code=ABCD1234
Route::get('/referrals/{code}', [ReferralController::class, 'show']);  // /api/referrals/ABCD1234



// Dashboard routes
Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'require.active.package'])->group(function () {
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

// contract routes
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

// rental contract routes (RMS contracts only)
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
// project routes
Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'audit.ctx'])->group(function () {
    Route::get   ('/projects',            [ProjectController::class, 'index'])->middleware('can:projects.view');
    Route::get   ('/projects/{id}',       [ProjectController::class, 'show'])->middleware('can:projects.view');
    Route::post  ('/projects',            [ProjectController::class, 'store'])->middleware('can:projects.create');
    Route::post  ('/projects/{id}',       [ProjectController::class, 'update'])->middleware('can:projects.update');
    Route::delete('/projects/{id}',       [ProjectController::class, 'destroy'])->middleware('can:projects.delete');
    Route::patch ('/projects/{id}/toggle-featured', [ProjectController::class, 'toggleFeatured'])->middleware('can:projects.update');
    Route::get   ('/user/projects',       [ProjectController::class, 'userProjects'])->middleware('can:projects.view');
});



// property routes

Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'audit.ctx'])->group(function () {
    Route::post  ('/properties/reorder-featured',        [PropertyController::class, 'properties_reorder_featured'])->middleware('can:properties.reorder');
    Route::post  ('/properties/reorder',                 [PropertyController::class, 'properties_reorder'])->middleware('can:properties.reorder');
        // properties/categories
    Route::get   ('/properties/categories',              [PropertyController::class, 'properties_categories']);

    Route::get   ('/properties',                         [PropertyController::class, 'index'])->middleware('can:properties.view');
    Route::get   ('/properties/available-units',         [PropertyController::class, 'availableUnits'])->middleware('can:properties.view');
    Route::get   ('/properties/{id}',                    [PropertyController::class, 'show'])->middleware('can:properties.view');
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
    Route::get   ('/property-faqs',                               [PropertyController::class, 'faqs']);

    // Building management routes
    Route::get   ('/buildings',                         [App\Http\Controllers\Api\BuildingController::class, 'index']);
    Route::get   ('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'show']);
    Route::post  ('/buildings',                         [App\Http\Controllers\Api\BuildingController::class, 'store']);
    Route::post  ('/buildings/upload-image',            [App\Http\Controllers\Api\BuildingController::class, 'uploadBuildingImage']);
    Route::post  ('/buildings/upload-deed-image',       [App\Http\Controllers\Api\BuildingController::class, 'uploadDeedImage']);
    Route::put   ('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'update']);
    Route::delete('/buildings/{id}',                    [App\Http\Controllers\Api\BuildingController::class, 'destroy']);
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

// customer dropdown routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/content/customer-dropdown', [CustomerDropdownSettingController::class, 'index']);
    Route::put('/content/customer-dropdown', [CustomerDropdownSettingController::class, 'update']);
    Route::post('/content/customer-dropdown/toggle-visibility', [CustomerDropdownSettingController::class, 'toggleVisibility']);
});
// header routes

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

Route::middleware(['auth:sanctum', SetTenantForPermissions::class])->group(function () {

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
Route::middleware(['auth:sanctum', SetTenantForPermissions::class])->group(function () {
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
Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'audit.ctx'])->group(function () {
    Route::prefix('customers')->group(function () {
        Route::get   ('/filters',  [CustomerController::class, 'filterOptions'])->middleware('can:customers.view');
        Route::get   ('/',         [CustomerController::class, 'index'])->middleware('can:customers.view');
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


// Api crm Customer
Route::middleware('auth:sanctum')->prefix('crm')->group(function () {
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
    Route::apiResource('customer-reminders', UserApiCustomerReminderController::class);

    // CRM Dashboard
    Route::get('/', [CRMController::class, 'index']);
    Route::post('/customers/{id}/change-stage', [CRMController::class, 'changeCustomerStage']); // drag and drop customers to change stage
    // drag and drop customers to change priority
    Route::post('/customers/{id}/change-priority', [CRMController::class, 'changeCustomerPriority']);
    // drag and drop customers to change type
    Route::post('/customers/{id}/change-type', [CRMController::class, 'changeCustomerType']);
    // drag and drop customers procedure
    Route::post('/customers/{id}/change-procedure', [CRMController::class, 'changeCustomerProcedure']);

    // searchCustomers
    Route::get('/customers/search', [CRMController::class, 'searchCustomers']); // search customers

    // Property Request Auto-Customer Settings
    Route::get('/property-requests/settings', [\App\Http\Controllers\Api\CRM\PropertyRequestSettingsController::class, 'index']);
    Route::put('/property-requests/settings', [\App\Http\Controllers\Api\CRM\PropertyRequestSettingsController::class, 'update']);

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
Route::post('/whatsapp/webhook', [ChatController::class, 'handleWhatsappWebhook']);

// isthara
Route::post('/isthara', [IstharaController::class, 'store']);

// Public Property Request (for visitors - no auth required)
Route::post('/v1/property-requests/public', [ApiPropertyRequestController::class, 'store']);

// Public Credit Management Routes (no auth required)
Route::prefix('v1/credits')->group(function () {
    Route::get('packages', [\App\Http\Controllers\Api\markting\CreditController::class, 'getPackages']);

    // Payment callback routes (no auth required for webhooks)
    Route::get('payment/success/{transaction_id}/{gateway}', [\App\Http\Controllers\Api\markting\CreditController::class, 'paymentSuccess'])
        ->name('api.credits.payment.success');
    Route::get('payment/cancel/{transaction_id}/{gateway}', [\App\Http\Controllers\Api\markting\CreditController::class, 'paymentCancel'])
        ->name('api.credits.payment.cancel');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/whatsapp/link', [WhatsappController::class, 'store']);
    Route::get('/whatsapp', [WhatsappController::class, 'index']);
});


Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Sales Management

    Route::prefix('rms')->group(function () {
        // Dashboard
        Route::get('dashboard', [RmsDashboardController::class, 'index']);

        // Filtered Payments Endpoints
        Route::get('payments/collections', [RmsDashboardController::class, 'paymentsCollections']);
        Route::get('payments/due', [RmsDashboardController::class, 'paymentsDue']);

        // Rentals
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
        Route::get('reminders', [ReminderController::class, 'index']);
        Route::post('reminders/{id}/dismiss', [ReminderController::class, 'dismiss']);
        Route::post('reminders/{id}/snooze', [ReminderController::class, 'snooze']);
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
    Route::get('/property-requests', [ApiPropertyRequestController::class, 'index']);
    Route::post('/property-requests', [ApiPropertyRequestController::class, 'store']);
    // DELETE
    Route::delete('/property-requests/{id}', [ApiPropertyRequestController::class, 'destroy']);
    // update
    Route::put('/property-requests/{id}', [ApiPropertyRequestController::class, 'update']);


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
    Route::middleware(['auth:sanctum','employee.only'])->group(function () {
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

    Route::prefix('crm')->group(function () {
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
        Route::get('channels', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'index']);
        Route::post('channels', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'store']);
        Route::get('channels/types', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'getChannelTypes']);
        // i want to return for each channel calc how much credits used and how much messages sent all channels return it as object
        Route::get('channels/usage', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'getUsage']);
        Route::get('channels/{id}', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'show']);
        Route::put('channels/{id}', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'update']);
        Route::patch('channels/{id}/status', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'updateStatus']);
        Route::get('channels/{id}/statistics', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'statistics']);
        Route::get('channels/{id}/stats', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'stats']);
        Route::post('channels/{id}/sync-verified', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'syncVerified']);
        Route::post('channels/{id}/send-message', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'sendMessage']);
        Route::post('channels/send-whatsapp-to-customer', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'sendWhatsAppToCustomer']);
        Route::delete('channels/{id}', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'destroy']);

        // Marketing Settings Routes
        Route::get('settings', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'getAllMarketingSettings']);
        Route::get('channels/{id}/settings', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'getMarketingSettings']);
        Route::put('channels/{id}/settings', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'updateMarketingSettings']);
        Route::patch('channels/{id}/system-integrations', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'updateSystemIntegrationSettings']);

    });

    // Marketing Webhooks Routes (no auth required for webhooks)
    Route::prefix('marketing/webhooks')->group(function () {
        Route::post('whatsapp', [\App\Http\Controllers\Api\markting\MarketingChannelController::class, 'whatsappWebhook']);
    });

    // Authenticated Credit Management Routes
    Route::prefix('credits')->middleware(['auth:sanctum'])->group(function () {
        Route::get('balance', [\App\Http\Controllers\Api\markting\CreditController::class, 'getBalance']);
        Route::post('purchase', [\App\Http\Controllers\Api\markting\CreditController::class, 'purchasePackage']);
        Route::get('transactions', [\App\Http\Controllers\Api\markting\CreditController::class, 'getTransactions']);
        Route::get('analytics', [\App\Http\Controllers\Api\markting\CreditController::class, 'getAnalytics']);
    });


});



Route::middleware(['auth:sanctum', SetTenantForPermissions::class])->group(function () {
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


Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'audit.ctx'])->group(function () {
		// Reservations (Dashboard - v1)
		Route::prefix('reservations')->group(function () {
			Route::get('/', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'index']);
			Route::get('/stats', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'stats']);
			Route::get('/export/csv', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'exportCsv']);
			Route::get('/{id}', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'show']);
			Route::post('/{id}/accept', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'accept'])->name('reservations.accept');
			Route::post('/{id}/reject', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'reject'])->name('reservations.reject');
			Route::post('/bulk-action', [\App\Http\Controllers\Api\V1\ReservationsController::class, 'bulkAction']);
		});

        Route::get('/customers/{id}/logs',  [CustomerLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/projects/{id}/logs',   [ProjectLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/properties/{id}/logs', [PropertyLogController::class, 'index'])->middleware('can:properties.view');
        Route::get('/crm/cards/{id}/logs', [CardLogController::class, 'index'])->middleware('can:crm.cards.view');
        // crm/cards
    });

    // Employee Management Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        // Define specific routes BEFORE apiResource to avoid conflicts
        Route::get('employees/available-roles', [EmployeeController::class, 'availableRoles']);
        Route::get('employees/available-permissions', [EmployeeController::class, 'availablePermissions']);
        Route::apiResource('employees', EmployeeController::class);
    });

    // Role Management Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('roles', RoleController::class);
        Route::get('permissions', [RoleController::class, 'permissions']);
        Route::post('permissions', [RoleController::class, 'storePermission']);
        Route::put('permissions/{id}', [RoleController::class, 'updatePermission']);
        Route::delete('permissions/{id}', [RoleController::class, 'destroyPermission']);
    });
});

// Pixel routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/pixels', [PixelController::class, 'index']);
    Route::post('/pixels', [PixelController::class, 'store']);
    Route::get('/pixels/{id}', [PixelController::class, 'show']);
    Route::put('/pixels/{id}', [PixelController::class, 'update']);
    Route::delete('/pixels/{id}', [PixelController::class, 'destroy']);
    Route::patch('/pixels/{id}/toggle-status', [PixelController::class, 'toggleStatus']);
});

// Add these routes for video upload functionality
Route::middleware('auth:sanctum')->group(function () {
    // Video upload routes
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

// Add this debug route
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

// Tenant Website API
Route::prefix('v1/tenant-website')->middleware(['api','tenant.resolve','tenant.id.response'])->group(function () {
    Route::post('getTenant', [GetTenantController::class, 'store']);
    Route::post('save-pages', [SavePagesController::class, 'store'])->middleware('auth:sanctum');

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
	Route::post('{tenantId}/reservations', [\App\Http\Controllers\Api\V1\TenantWebsite\ReservationController::class, 'store'])->middleware('throttle:5,1');

    // Tenant Website Properties (public)
    Route::get('{tenantId}/properties', [\App\Http\Controllers\Api\V1\TenantWebsite\PropertyController::class, 'index']);
    Route::get('{tenantId}/properties/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\PropertyController::class, 'show']);

    // Tenant Website Projects (public)
    Route::get('{tenantId}/projects', [\App\Http\Controllers\Api\V1\TenantWebsite\ProjectController::class, 'index']);
    Route::get('{tenantId}/projects/{slug}', [\App\Http\Controllers\Api\V1\TenantWebsite\ProjectController::class, 'show']);

    // Tenant Website AI Export (public)
    Route::get('{tenantId}/ai-export', [\App\Http\Controllers\Api\V1\TenantWebsite\AiExportController::class, 'index']);
    Route::get('{tenantId}/ai-export.txt', [\App\Http\Controllers\Api\V1\TenantWebsite\AiExportController::class, 'downloadTxt']);

    // (moved Matching endpoints out of tenant-website scope)
});

// Matching Endpoints (Dashboard APIs, require auth) - observer-only, retrieval endpoints
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('matching')->group(function () {
        Route::get('customers', [V1MatchingController::class, 'customers']);
        Route::get('customers/{customer_key}/properties', [V1MatchingController::class, 'customerProperties']);
        Route::get('matches/{id}', [V1MatchingController::class, 'showMatch']);
    });
});

// Direct public route for property categories (bypassing tenant.resolve middleware)
Route::get('v1/tenant-website/{tenantId}/properties/categories/direct', [PropertyController::class, 'properties_categories']);

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

