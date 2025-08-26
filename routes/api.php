<?php

use App\Http\Middleware\SetTenantForPermissions; // the middleware we added earlier

use Illuminate\Http\Request;
use App\Models\Api\ApiThemeSettings;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Rbac\{
    RoleController,
    AssignmentController,
    PermissionController,
    PermissionAdminController,
};

use Laravel\Socialite\Facades\Socialite;
// use App\Http\Controllers\Api\content\ApiContentSection;
use App\Http\Controllers\ImpersonationController;

use App\Http\Controllers\Api\{
    MeAbilitiesController,
    CRMController,
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
    DomainSettingsController,
    ApiSideMenusController,
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
    Em\CustomerController as EmployeeCustomerController,
};

use App\Http\Controllers\Api\V1\Rms\{
    RentalController,
    ContractController,
    InstallmentController,
    MaintenanceController,
    ReminderController,
    RmsDashboardController,
};

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
    Route::get   ('/properties/{id}',                    [PropertyController::class, 'show'])->middleware('can:properties.view');
    Route::post  ('/properties',                         [PropertyController::class, 'store'])->middleware('can:properties.create');
    Route::post  ('/properties/{id}',                    [PropertyController::class, 'update'])->middleware('can:properties.update');
    Route::delete('/properties/{id}',                    [PropertyController::class, 'destroy'])->middleware('can:properties.delete');
    Route::patch ('/properties/{id}/toggle-featured',    [PropertyController::class, 'toggleFeatured'])->middleware('can:properties.update');
    Route::post  ('/properties/{id}/toggle-status',      [PropertyController::class, 'toggleStatus'])->middleware('can:properties.update');
    Route::post  ('/properties/{propertyId}/duplicate',  [PropertyController::class, 'duplicate'])->middleware('can:properties.create');
    Route::get   ('/property/facades',                   [UserFacadeController::class, 'index'])->middleware('can:properties.view');
    // faqs
    Route::get   ('/property-faqs',                               [PropertyController::class, 'faqs']);
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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/whatsapp/link', [WhatsappController::class, 'store']);
    Route::get('/whatsapp', [WhatsappController::class, 'index']);
});


Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    Route::prefix('rms')->group(function () {
        // Dashboard
        Route::get('dashboard', [RmsDashboardController::class, 'index']);

        // Rentals
        Route::get('rentals', [RentalController::class, 'index']);
        Route::post('rentals', [RentalController::class, 'store']);
        Route::get('rentals/{id}', [RentalController::class, 'show']);
        Route::patch('rentals/{id}', [RentalController::class, 'update']);
        Route::delete('rentals/{id}', [RentalController::class, 'destroy']);

        // Contracts
        Route::get('rentals/{rentalId}/contracts', [ContractController::class, 'listByRental']);
        Route::post('rentals/{rentalId}/contracts', [ContractController::class, 'store']);
        Route::patch('contracts/{id}', [ContractController::class, 'update']);
        Route::post('contracts/{id}/terminate', [ContractController::class, 'terminate']);

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

    // ApiCustomerInquiry
    Route::get('/inquiry', [CustomerInquiryController::class, 'index']);

    // ApiPropertyRequestController
    Route::get('/property-requests', [ApiPropertyRequestController::class, 'index']);
    Route::post('/property-requests', [ApiPropertyRequestController::class, 'store']);

    // ApiPropertyRequestSettingsController
    Route::prefix('property-request-settings')->group(function () {
        Route::get('/',        [ApiPropertyRequestSettingsController::class, 'index']);       // ?merged=true|false
        Route::get('/defaults',[ApiPropertyRequestSettingsController::class, 'defaults']); // ?merged=true|false

        Route::post('/bulk',   [ApiPropertyRequestSettingsController::class, 'bulkUpsert']);  // upsert مجموعة
        Route::put('/{field}', [ApiPropertyRequestSettingsController::class, 'updateOne']);   // تعديل مفتاح واحد
        Route::post('/reset',  [ApiPropertyRequestSettingsController::class, 'reset']);       // حذف إعدادات (العودة للديفولت)
    });

    // ===== Employee Auth (PUBLIC) =====
    Route::prefix('em/auth')->group(function () {
        Route::post('login',    [EmployeeAuthController::class, 'login']);
        Route::post('register', [EmployeeAuthController::class, 'register']);
    });
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
    });


});


Route::middleware(['auth:sanctum', SetTenantForPermissions::class])->group(function () {
    Route::get('/v1/me/abilities', [MeAbilitiesController::class, 'index']);

    Route::get('/v1/rbac/perms/me', [PermissionController::class, 'me']);
    Route::get('/v1/rbac/employees/{employee}/perms', [PermissionController::class, 'employee']);

    Route::middleware('can:settings.update')->group(function () {
        Route::get   ('/v1/rbac/roles',           [RoleController::class, 'index']);
        Route::post  ('/v1/rbac/roles',           [RoleController::class, 'store']);
        Route::put   ('/v1/rbac/roles/{role}',    [RoleController::class, 'update']);
        Route::delete('/v1/rbac/roles/{role}',    [RoleController::class, 'destroy']);

        Route::get   ('/v1/rbac/permissions',                         [PermissionAdminController::class, 'index']);
        Route::post  ('/v1/rbac/permissions',                         [PermissionAdminController::class, 'store']);
        Route::put   ('/v1/rbac/permissions/{permission}',            [PermissionAdminController::class, 'update']);
        Route::delete('/v1/rbac/permissions/{permission}',            [PermissionAdminController::class, 'destroy']);

        Route::get   ('/v1/rbac/employees/{employee}/roles',          [AssignmentController::class, 'showRoles']);
        Route::post  ('/v1/rbac/employees/{employee}/roles',          [AssignmentController::class, 'syncRoles']);
        Route::post  ('/v1/rbac/employees/{employee}/perms',          [AssignmentController::class, 'syncPerms']);
    });


});


Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', SetTenantForPermissions::class, 'audit.ctx'])->group(function () {
        Route::get('/customers/{id}/logs',  [CustomerLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/projects/{id}/logs',   [ProjectLogController::class, 'index'])->middleware('can:projects.view');
        Route::get('/properties/{id}/logs', [PropertyLogController::class, 'index'])->middleware('can:properties.view');
        Route::get('/crm/cards/{id}/logs', [CardLogController::class, 'index'])->middleware('can:crm.cards.view');
        // crm/cards
    });
});
