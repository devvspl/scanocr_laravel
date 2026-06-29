<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\DocumentAiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\TokenLoginController;
use App\Http\Controllers\Panel\CompanyController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\DepartmentController;
use App\Http\Controllers\Panel\DocumentTypeController;
use App\Http\Controllers\Panel\FileController;
use App\Http\Controllers\Panel\FirmController;
use App\Http\Controllers\Panel\FinancialYearController;
use App\Http\Controllers\Panel\GeneratorController;
use App\Http\Controllers\Panel\HotelController;
use App\Http\Controllers\Panel\ImportController;
use App\Http\Controllers\Panel\ItemController;
use App\Http\Controllers\Panel\LedgerController;
use App\Http\Controllers\Panel\MasterController;
use App\Http\Controllers\Panel\NumberingController;
use App\Http\Controllers\Panel\PageBuilderController;
use App\Http\Controllers\Panel\PageFieldController;
use App\Http\Controllers\Panel\PageShareManageController;
use App\Http\Controllers\Panel\PermissionController;
use App\Http\Controllers\Panel\PermissionGroupController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\RoleController;
use App\Http\Controllers\Panel\ScanFileBillDateSyncController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\CoreApiSyncController;
use App\Http\Controllers\Panel\ExtMasterController;
use App\Http\Controllers\Panel\ReportController;
use App\Http\Controllers\Panel\GitDeployController;
use App\Http\Controllers\Panel\UnitController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\Panel\WorkLocationController;
use App\Http\Controllers\Workflow\BillApprovalController;
use App\Http\Controllers\Workflow\ClassificationController;
use App\Http\Controllers\Workflow\DirectScannerController;
use App\Http\Controllers\Workflow\PunchingController;
use App\Http\Controllers\Workflow\PunchingEntryController;
use App\Http\Controllers\Workflow\PunchApprovalController;
use App\Http\Controllers\Workflow\SuperScannerController;
use App\Http\Controllers\Workflow\TempScannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/help', [PublicController::class, 'help'])->name('help');
Route::get('/cron-process-queue', [PublicController::class, 'processQueue'])->name('cron.process-queue');

// ── JWT Token Login — accepts GET or POST, separate from normal auth flow ──
// GET /token-login?token=<jwt>
Route::match(['get', 'post'], '/token-login', [TokenLoginController::class, 'login'])->name('token-login');

Route::middleware('guest')->group(function () {
    // Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    // Route::post('/register', [AuthController::class, 'register']);
    Route::get('/register/verify', [AuthController::class, 'showVerifyRegisterForm'])->name('register.verify.form');
    Route::post('/register/verify', [AuthController::class, 'verifyRegister'])->name('register.verify.submit');
    Route::post('/register/otp/resend', [AuthController::class, 'resendRegisterOtp'])->name('register.otp.resend');

    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/login/verify', [AuthController::class, 'showVerifyLoginForm'])->name('login.verify.form');
    Route::post('/login/verify', [AuthController::class, 'verifyLogin'])->name('login.verify.submit');
    Route::post('/login/otp/resend', [AuthController::class, 'resendLoginOtp'])->name('login.otp.resend');

    Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('password.email');
    Route::get('/verify-otp', [AuthController::class, 'showVerifyForm'])->name('password.reset');
    Route::post('/verify-otp', [AuthController::class, 'resetPassword'])->name('password.update');
    Route::post('/verify-otp/resend', [AuthController::class, 'resendPasswordResetOtp'])->name('password.otp.resend');
});

Route::middleware(['auth', 'checkpermission'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/master', [MasterController::class, 'index'])->name('master');

    // ── Page Builder ──────────────────────────────────────────────────────────
    Route::get('/master/page-builder', [PageBuilderController::class, 'index'])->name('master.page-builder');
    Route::get('/master/page-builder/data', [PageBuilderController::class, 'data'])->name('master.page-builder.data');
    Route::get('/master/page-builder/add', [PageBuilderController::class, 'create'])->name('master.page-builder.create');
    Route::post('/master/page-builder', [PageBuilderController::class, 'store'])->name('master.page-builder.store');
    Route::post('/master/page-builder/bulk-destroy', [PageBuilderController::class, 'bulkDestroy'])->name('master.page-builder.bulk-destroy');
    Route::get('/master/page-builder/{page}/edit', [PageBuilderController::class, 'edit'])->name('master.page-builder.edit');
    Route::put('/master/page-builder/{page}', [PageBuilderController::class, 'update'])->name('master.page-builder.update');
    Route::delete('/master/page-builder/{page}', [PageBuilderController::class, 'destroy'])->name('master.page-builder.destroy');
    Route::post('/master/page-builder/{page}/generate', [GeneratorController::class, 'generate'])->name('master.page-builder.generate');

    // Page Shares (API link management)
    Route::get('/master/page-builder/{page}/shares', [PageShareManageController::class, 'index'])->name('master.page-builder.shares');
    Route::post('/master/page-builder/{page}/shares', [PageShareManageController::class, 'store'])->name('master.page-builder.shares.store');
    Route::post('/master/page-builder/{page}/shares/{share}/toggle', [PageShareManageController::class, 'toggle'])->name('master.page-builder.shares.toggle');
    Route::delete('/master/page-builder/{page}/shares/{share}', [PageShareManageController::class, 'destroy'])->name('master.page-builder.shares.destroy');

    // Page Fields
    Route::get('/master/page-builder/{page}/fields', [PageFieldController::class, 'index'])->name('master.page-builder.fields');
    Route::get('/master/page-builder/{page}/preview', [PageFieldController::class, 'preview'])->name('master.page-builder.preview');
    Route::post('/master/page-builder/{page}/fields', [PageFieldController::class, 'store'])->name('master.page-builder.fields.store');
    Route::put('/master/page-builder/{page}/fields/{field}', [PageFieldController::class, 'updateSettings'])->name('master.page-builder.fields.settings');
    Route::put('/master/page-builder/{page}/fields/{field}/repeater', [PageFieldController::class, 'updateRepeaterColumns'])->name('master.page-builder.fields.repeater');
    Route::post('/master/page-builder/{page}/fields/reorder', [PageFieldController::class, 'reorder'])->name('master.page-builder.fields.reorder');
    Route::get('/master/page-builder/get-columns', [PageFieldController::class, 'getColumns'])->name('master.page-builder.get-columns');
    Route::get('/master/page-builder/lookup', [PageFieldController::class, 'lookup'])->name('master.page-builder.lookup');
    Route::get('/master/page-builder/search-options', [PageFieldController::class, 'searchOptions'])->name('master.page-builder.search-options');
    Route::delete('/master/page-builder/{page}/fields/{field}', [PageFieldController::class, 'destroy'])->name('master.page-builder.fields.destroy');

    // ── Import Data ───────────────────────────────────────────────────────────
    Route::get('/master/import', [ImportController::class, 'index'])->name('master.import.index');
    Route::post('/master/import/upload', [ImportController::class, 'upload'])->name('master.import.upload');
    Route::get('/master/import/preview/{filename}', [ImportController::class, 'previewFile'])->name('master.import.preview');
    Route::get('/master/import/tables', [ImportController::class, 'getTables'])->name('master.import.tables');
    Route::get('/master/import/table-columns', [ImportController::class, 'getTableColumns'])->name('master.import.table-columns');
    Route::post('/master/import/start', [ImportController::class, 'start'])->name('master.import.start');
    Route::get('/master/import/status/{job}', [ImportController::class, 'status'])->name('master.import.status');
    Route::get('/master/import/jobs/{job}', [ImportController::class, 'show'])->name('master.import.show');
    Route::delete('/master/import/jobs/{job}', [ImportController::class, 'destroy'])->name('master.import.destroy');
    Route::get('/master/import/jobs/{job}/errors', [ImportController::class, 'downloadErrors'])->name('master.import.errors');
    Route::get('/master/import/templates', [ImportController::class, 'templates'])->name('master.import.templates');
    Route::delete('/master/import/templates/{template}', [ImportController::class, 'deleteTemplate'])->name('master.import.templates.delete');
    Route::get('/master/import/api-connections', [ImportController::class, 'apiConnections'])->name('master.import.api-connections');
    Route::post('/master/import/api-connections', [ImportController::class, 'storeApiConnection'])->name('master.import.api-connections.store');
    Route::post('/master/import/api-connections/{connection}/test', [ImportController::class, 'testApiConnection'])->name('master.import.api-connections.test');
    Route::delete('/master/import/api-connections/{connection}', [ImportController::class, 'destroyApiConnection'])->name('master.import.api-connections.destroy');
    Route::get('/master/{tab}', [MasterController::class, 'tab'])->name('master.tab');

    // ── Document AI Predictor ─────────────────────────────────────────────────
    Route::prefix('document-ai')->name('document-ai.')->group(function () {
        Route::get('playground', [DocumentAiController::class, 'playground'])->name('playground');
        Route::post('predict', [DocumentAiController::class, 'predictDocument'])->name('predict');
        Route::post('save-classification', [DocumentAiController::class, 'saveClassification'])->name('save-classification');
        Route::get('settings', [DocumentAiController::class, 'classificationSettings'])->name('settings');
        Route::post('training', [DocumentAiController::class, 'storeTrainingData'])->name('training.store');
        Route::post('training/{id}', [DocumentAiController::class, 'updateTrainingData'])->name('training.update');
        Route::delete('training/{id}', [DocumentAiController::class, 'deleteTrainingData'])->name('training.delete');
        Route::post('type/{id}/toggle', [DocumentAiController::class, 'toggleBasisStatus'])->name('type.toggle');
        Route::get('reasoning/{id}', [DocumentAiController::class, 'getPredictionReasoning'])->name('reasoning');
        Route::get('logs', [DocumentAiController::class, 'predictionLogs'])->name('logs');
        Route::get('logs/data', [DocumentAiController::class, 'predictionLogsData'])->name('logs.data');
        Route::get('analytics', [DocumentAiController::class, 'analytics'])->name('analytics');
        Route::get('analytics/data', [DocumentAiController::class, 'analyticsData'])->name('analytics.data');
        Route::get('dept-rules', [DocumentAiController::class, 'deptRules'])->name('dept-rules');
        Route::get('dept-rules/data', [DocumentAiController::class, 'deptRulesData'])->name('dept-rules.data');
        Route::post('dept-rules', [DocumentAiController::class, 'storeDeptRule'])->name('dept-rules.store');
        Route::post('dept-rules/{id}', [DocumentAiController::class, 'updateDeptRule'])->name('dept-rules.update');
        Route::delete('dept-rules/{id}', [DocumentAiController::class, 'deleteDeptRule'])->name('dept-rules.delete');
    });


    // ── Profile ───────────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update.info');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

    // ── Settings ──────────────────────────────────────────────────────────────
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Company
    Route::get('/settings/company', [CompanyController::class, 'index'])->name('settings.company');
    Route::post('/settings/company', [CompanyController::class, 'store'])->name('settings.company.store');
    Route::get('/settings/company/{company}', [CompanyController::class, 'show'])->name('settings.company.show');
    Route::put('/settings/company/{company}', [CompanyController::class, 'update'])->name('settings.company.update');
    Route::delete('/settings/company/{company}', [CompanyController::class, 'destroy'])->name('settings.company.destroy');
    Route::post('/settings/company/{company}/default', [CompanyController::class, 'setDefault'])->name('settings.company.default');
    Route::post('/settings/company/{company}/switch',  [CompanyController::class, 'switchSession'])->name('settings.company.switch');

    // Financial Year
    Route::get('/settings/financial-year', [FinancialYearController::class, 'index'])->name('settings.financial-year');
    Route::post('/settings/financial-year', [FinancialYearController::class, 'store'])->name('settings.financial-year.store');
    Route::get('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'show'])->name('settings.financial-year.show');
    Route::put('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'update'])->name('settings.financial-year.update');
    Route::delete('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'destroy'])->name('settings.financial-year.destroy');
    Route::post('/settings/financial-year/{financialYear}/current', [FinancialYearController::class, 'setCurrent'])->name('settings.financial-year.current');
    Route::post('/settings/financial-year/{financialYear}/switch',  [FinancialYearController::class, 'switchSession'])->name('settings.financial-year.switch');

    // Numbering
    Route::get('/settings/numbering', [NumberingController::class, 'index'])->name('settings.numbering');
    Route::get('/settings/numbering/{numberingSetting}', [NumberingController::class, 'show'])->name('settings.numbering.show');
    Route::put('/settings/numbering/{numberingSetting}', [NumberingController::class, 'update'])->name('settings.numbering.update');

    // Document Types
    Route::get('/settings/document-types', [DocumentTypeController::class, 'index'])->name('settings.document-types');
    Route::get('/settings/document-types/data', [DocumentTypeController::class, 'data'])->name('settings.document-types.data');
    Route::post('/settings/document-types', [DocumentTypeController::class, 'store'])->name('settings.document-types.store');
    Route::get('/settings/document-types/{documentType}', [DocumentTypeController::class, 'show'])->name('settings.document-types.show');
    Route::put('/settings/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('settings.document-types.update');
    Route::delete('/settings/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('settings.document-types.destroy');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Git Deploy (Super Admin only)
    Route::get('/git-deploy', [GitDeployController::class, 'index'])->name('git-deploy');
    Route::get('/git-deploy/status', [GitDeployController::class, 'status'])->name('git-deploy.status');
    Route::post('/git-deploy/pull', [GitDeployController::class, 'pull'])->name('git-deploy.pull');
    Route::post('/git-deploy/commit', [GitDeployController::class, 'commit'])->name('git-deploy.commit');
    Route::post('/git-deploy/push', [GitDeployController::class, 'push'])->name('git-deploy.push');
    Route::post('/git-deploy/reset', [GitDeployController::class, 'reset'])->name('git-deploy.reset');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/export-logs', [ReportController::class, 'exportLogs'])->name('reports.export-logs');
    Route::get('/reports/select/companies', [ReportController::class, 'companiesSelect'])->name('reports.select.companies');
    Route::get('/reports/select/locations', [ReportController::class, 'locationsSelect'])->name('reports.select.locations');
    Route::get('/reports/select/doc-types', [ReportController::class, 'docTypesSelect'])->name('reports.select.doc-types');
    Route::get('/reports/select/vendors', [ReportController::class, 'vendorsSelect'])->name('reports.select.vendors');
    Route::get('/reports/select/users', [ReportController::class, 'usersSelect'])->name('reports.select.users');
    Route::get('/reports/select/financial-years', [ReportController::class, 'financialYearsSelect'])->name('reports.select.financial-years');

    // Master Tables - Work Locations
    Route::get('/settings/work-locations', [WorkLocationController::class, 'index'])->name('settings.work-locations');
    Route::get('/settings/work-locations/data', [WorkLocationController::class, 'data'])->name('settings.work-locations.data');
    Route::post('/settings/work-locations', [WorkLocationController::class, 'store'])->name('settings.work-locations.store');
    Route::get('/settings/work-locations/{workLocation}', [WorkLocationController::class, 'show'])->name('settings.work-locations.show');
    Route::put('/settings/work-locations/{workLocation}', [WorkLocationController::class, 'update'])->name('settings.work-locations.update');
    Route::delete('/settings/work-locations/{workLocation}', [WorkLocationController::class, 'destroy'])->name('settings.work-locations.destroy');

    // Master Tables - Ledgers
    Route::get('/settings/ledgers', [LedgerController::class, 'index'])->name('settings.ledgers');
    Route::get('/settings/ledgers/data', [LedgerController::class, 'data'])->name('settings.ledgers.data');
    Route::post('/settings/ledgers', [LedgerController::class, 'store'])->name('settings.ledgers.store');
    Route::get('/settings/ledgers/{ledger}', [LedgerController::class, 'show'])->name('settings.ledgers.show');
    Route::put('/settings/ledgers/{ledger}', [LedgerController::class, 'update'])->name('settings.ledgers.update');
    Route::delete('/settings/ledgers/{ledger}', [LedgerController::class, 'destroy'])->name('settings.ledgers.destroy');

    // Master Tables - Firms
    Route::get('/settings/firms', [FirmController::class, 'index'])->name('settings.firms');
    Route::get('/settings/firms/data', [FirmController::class, 'data'])->name('settings.firms.data');
    Route::post('/settings/firms', [FirmController::class, 'store'])->name('settings.firms.store');
    Route::get('/settings/firms/{firm}', [FirmController::class, 'show'])->name('settings.firms.show');
    Route::put('/settings/firms/{firm}', [FirmController::class, 'update'])->name('settings.firms.update');
    Route::delete('/settings/firms/{firm}', [FirmController::class, 'destroy'])->name('settings.firms.destroy');

    // Master Tables - Departments
    Route::get('/settings/departments', [DepartmentController::class, 'index'])->name('settings.departments');
    Route::get('/settings/departments/data', [DepartmentController::class, 'data'])->name('settings.departments.data');
    Route::post('/settings/departments', [DepartmentController::class, 'store'])->name('settings.departments.store');
    Route::get('/settings/departments/{department}', [DepartmentController::class, 'show'])->name('settings.departments.show');
    Route::put('/settings/departments/{department}', [DepartmentController::class, 'update'])->name('settings.departments.update');
    Route::delete('/settings/departments/{department}', [DepartmentController::class, 'destroy'])->name('settings.departments.destroy');

    // Master Tables - Files
    Route::get('/settings/files', [FileController::class, 'index'])->name('settings.files');
    Route::get('/settings/files/data', [FileController::class, 'data'])->name('settings.files.data');
    Route::post('/settings/files', [FileController::class, 'store'])->name('settings.files.store');
    Route::get('/settings/files/{file}', [FileController::class, 'show'])->name('settings.files.show');
    Route::put('/settings/files/{file}', [FileController::class, 'update'])->name('settings.files.update');
    Route::delete('/settings/files/{file}', [FileController::class, 'destroy'])->name('settings.files.destroy');

    // Master Tables - Units
    Route::get('/settings/units', [UnitController::class, 'index'])->name('settings.units');
    Route::get('/settings/units/data', [UnitController::class, 'data'])->name('settings.units.data');
    Route::post('/settings/units', [UnitController::class, 'store'])->name('settings.units.store');
    Route::get('/settings/units/{unit}', [UnitController::class, 'show'])->name('settings.units.show');
    Route::put('/settings/units/{unit}', [UnitController::class, 'update'])->name('settings.units.update');
    Route::delete('/settings/units/{unit}', [UnitController::class, 'destroy'])->name('settings.units.destroy');

    // Master Tables - Hotels
    Route::get('/settings/hotels', [HotelController::class, 'index'])->name('settings.hotels');
    Route::get('/settings/hotels/data', [HotelController::class, 'data'])->name('settings.hotels.data');
    Route::post('/settings/hotels', [HotelController::class, 'store'])->name('settings.hotels.store');
    Route::get('/settings/hotels/{hotel}', [HotelController::class, 'show'])->name('settings.hotels.show');
    Route::put('/settings/hotels/{hotel}', [HotelController::class, 'update'])->name('settings.hotels.update');
    Route::delete('/settings/hotels/{hotel}', [HotelController::class, 'destroy'])->name('settings.hotels.destroy');

    // Master Tables - Items
    Route::get('/settings/items', [ItemController::class, 'index'])->name('settings.items');
    Route::get('/settings/items/data', [ItemController::class, 'data'])->name('settings.items.data');
    Route::post('/settings/items', [ItemController::class, 'store'])->name('settings.items.store');
    Route::get('/settings/items/{item}', [ItemController::class, 'show'])->name('settings.items.show');
    Route::put('/settings/items/{item}', [ItemController::class, 'update'])->name('settings.items.update');
    Route::delete('/settings/items/{item}', [ItemController::class, 'destroy'])->name('settings.items.destroy');

    // Ext Master — API Control
    Route::get('/settings/ext-api-control', [ExtMasterController::class, 'apiIndex'])->name('settings.ext-api-control');
    Route::get('/settings/ext-api-control/data', [ExtMasterController::class, 'apiData'])->name('settings.ext-api-control.data');
    Route::post('/settings/ext-api-control', [ExtMasterController::class, 'apiStore'])->name('settings.ext-api-control.store');
    Route::get('/settings/ext-api-control/{extMasterApiControl}', [ExtMasterController::class, 'apiShow'])->name('settings.ext-api-control.show');
    Route::put('/settings/ext-api-control/{extMasterApiControl}', [ExtMasterController::class, 'apiUpdate'])->name('settings.ext-api-control.update');
    Route::delete('/settings/ext-api-control/{extMasterApiControl}', [ExtMasterController::class, 'apiDestroy'])->name('settings.ext-api-control.destroy');

    // Ext Master — Field Mappings
    Route::get('/settings/ext-field-mappings', [ExtMasterController::class, 'fieldIndex'])->name('settings.ext-field-mappings');
    Route::get('/settings/ext-field-mappings/data', [ExtMasterController::class, 'fieldData'])->name('settings.ext-field-mappings.data');
    Route::post('/settings/ext-field-mappings', [ExtMasterController::class, 'fieldStore'])->name('settings.ext-field-mappings.store');
    Route::get('/settings/ext-field-mappings/{extFieldMapping}', [ExtMasterController::class, 'fieldShow'])->name('settings.ext-field-mappings.show');
    Route::put('/settings/ext-field-mappings/{extFieldMapping}', [ExtMasterController::class, 'fieldUpdate'])->name('settings.ext-field-mappings.update');
    Route::delete('/settings/ext-field-mappings/{extFieldMapping}', [ExtMasterController::class, 'fieldDestroy'])->name('settings.ext-field-mappings.destroy');

    // Users
    Route::get('/settings/users', [UserController::class, 'index'])->name('settings.users');
    Route::get('/settings/users/data', [UserController::class, 'data'])->name('settings.users.data');
    Route::post('/settings/users', [UserController::class, 'store'])->name('settings.users.store');
    Route::get('/settings/users/{user}', [UserController::class, 'show'])->name('settings.users.show');
    Route::put('/settings/users/{user}', [UserController::class, 'update'])->name('settings.users.update');
    Route::delete('/settings/users/{user}', [UserController::class, 'destroy'])->name('settings.users.destroy');
    Route::get('/settings/users/{user}/sub-users', [UserController::class, 'subUsers'])->name('settings.users.sub-users');
    Route::get('/settings/users/{user}/roles', [UserController::class, 'roles'])->name('settings.users.roles');
    Route::put('/settings/users/{user}/roles', [UserController::class, 'updateRoles'])->name('settings.users.roles.update');
    Route::get('/settings/users/{user}/permissions', [UserController::class, 'permissions'])->name('settings.users.permissions');
    Route::put('/settings/users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('settings.users.permissions.update');
    Route::get('/settings/users/{user}/document-access', [UserController::class, 'documentAccess'])->name('settings.users.document-access');
    Route::put('/settings/users/{user}/document-access', [UserController::class, 'updateDocumentAccess'])->name('settings.users.document-access.update');
    Route::get('/settings/users/{user}/company-access', [UserController::class, 'companyAccess'])->name('settings.users.company-access');
    Route::put('/settings/users/{user}/company-access', [UserController::class, 'updateCompanyAccess'])->name('settings.users.company-access.update');
    Route::get('/settings/users/{user}/location-access', [UserController::class, 'locationAccess'])->name('settings.users.location-access');
    Route::put('/settings/users/{user}/location-access', [UserController::class, 'updateLocationAccess'])->name('settings.users.location-access.update');

    // Roles
    Route::get('/settings/roles', [RoleController::class, 'index'])->name('settings.roles');
    Route::get('/settings/roles/data', [RoleController::class, 'data'])->name('settings.roles.data');
    Route::post('/settings/roles', [RoleController::class, 'store'])->name('settings.roles.store');
    Route::get('/settings/roles/{role}', [RoleController::class, 'show'])->name('settings.roles.show');
    Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [RoleController::class, 'destroy'])->name('settings.roles.destroy');
    Route::get('/settings/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('settings.roles.permissions');
    Route::put('/settings/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('settings.roles.permissions.update');

    // Permissions
    Route::get('/settings/permissions', [PermissionController::class, 'index'])->name('settings.permissions');
    Route::get('/settings/permissions/data', [PermissionController::class, 'data'])->name('settings.permissions.data');
    Route::post('/settings/permissions', [PermissionController::class, 'store'])->name('settings.permissions.store');
    Route::get('/settings/permissions/{permission}', [PermissionController::class, 'show'])->name('settings.permissions.show');
    Route::put('/settings/permissions/{permission}', [PermissionController::class, 'update'])->name('settings.permissions.update');
    Route::delete('/settings/permissions/{permission}', [PermissionController::class, 'destroy'])->name('settings.permissions.destroy');

    // Permission Groups
    Route::get('/settings/permission-groups', [PermissionGroupController::class, 'index'])->name('settings.permission-groups.index');
    Route::post('/settings/permission-groups', [PermissionGroupController::class, 'store'])->name('settings.permission-groups.store');
    Route::delete('/settings/permission-groups/{permissionGroup}', [PermissionGroupController::class, 'destroy'])->name('settings.permission-groups.destroy');

    // Bill Date Sync
    Route::get('/settings/bill-date-sync', [ScanFileBillDateSyncController::class, 'index'])->name('settings.bill-date-sync');
    Route::post('/settings/bill-date-sync/process', [ScanFileBillDateSyncController::class, 'process'])->name('settings.bill-date-sync.process');
    Route::get('/settings/bill-date-sync/export', [ScanFileBillDateSyncController::class, 'export'])->name('settings.bill-date-sync.export');

    // Core API Sync
    Route::get('/settings/core-api-sync', [CoreApiSyncController::class, 'index'])->name('settings.core-api-sync');
    Route::get('/settings/core-api-sync/data', [CoreApiSyncController::class, 'data'])->name('settings.core-api-sync.data');
    Route::post('/settings/core-api-sync/fetch', [CoreApiSyncController::class, 'fetchApiList'])->name('settings.core-api-sync.fetch');
    Route::post('/settings/core-api-sync/sync', [CoreApiSyncController::class, 'sync'])->name('settings.core-api-sync.sync');
    Route::get('/settings/core-api-sync/modal-data', [CoreApiSyncController::class, 'modalData'])->name('settings.core-api-sync.modal-data');
    Route::post('/settings/core-api-sync/empty', [CoreApiSyncController::class, 'emptyTable'])->name('settings.core-api-sync.empty');
    Route::post('/settings/core-api-sync/drop', [CoreApiSyncController::class, 'dropTable'])->name('settings.core-api-sync.drop');

    // ── Workflow ──────────────────────────────────────────────────────────────
    Route::prefix('workflow')->name('workflow.')->group(function () {

        // Temp Scanner
        Route::prefix('temp-scan')->name('temp-scan.')->group(function () {
            Route::get('/',              [TempScannerController::class, 'index'])                     ->name('index');
            Route::post('/',             [TempScannerController::class, 'store'])                     ->name('store');
            Route::get('/data',          [TempScannerController::class, 'data'])                     ->name('data');
            Route::get('/tab-counts',    [TempScannerController::class, 'tabCounts'])               ->name('tab-counts');
            Route::get('/locations',     [TempScannerController::class, 'locationsSelect'])           ->name('locations');
            Route::get('/bill-approvers',[TempScannerController::class, 'getBillApproversForLocation'])->name('bill-approvers');
            Route::get('/doc-types',     [TempScannerController::class, 'docTypesSelect'])            ->name('doc-types');
            Route::get('/export/excel',  [TempScannerController::class, 'exportExcel'])           ->name('export.excel');
            Route::get('/export/pdf',    [TempScannerController::class, 'exportPdf'])             ->name('export.pdf');
            Route::get('/export/logs',   [TempScannerController::class, 'exportLogs'])            ->name('export.logs');
            Route::get('/{scan}/support-list',       [TempScannerController::class, 'supportList'])       ->name('support-list');
            Route::post('/{scan}/supporting',        [TempScannerController::class, 'storeSupporting'])   ->name('supporting.store');
            Route::post('/{scan}/final-submit',      [TempScannerController::class, 'finalSubmit'])       ->name('final-submit');
            Route::post('/{scan}/resubmit',          [TempScannerController::class, 'resubmit'])          ->name('resubmit');
            Route::post('/{scan}/replace',           [TempScannerController::class, 'replaceFile'])       ->name('replace');
            Route::delete('/{scan}',                 [TempScannerController::class, 'destroy'])           ->name('destroy');
            Route::delete('/{scan}/support/{supportId}', [TempScannerController::class, 'destroySupport'])->name('support.destroy');
        });

        // ── Super Scanner (summary dashboard for Super Scanner role) ──────────────
        Route::prefix('super-scanner')->name('super-scanner.')->group(function () {
            Route::get('/',               [SuperScannerController::class, 'index'])       ->name('index');
            Route::get('/data',           [SuperScannerController::class, 'data'])        ->name('data');
            Route::get('/totals',         [SuperScannerController::class, 'totals'])      ->name('totals');
            Route::get('/detail',         [SuperScannerController::class, 'detail'])      ->name('detail');
            Route::get('/export/excel',   [SuperScannerController::class, 'exportExcel']) ->name('export.excel');
            Route::get('/export/pdf',     [SuperScannerController::class, 'exportPdf'])   ->name('export.pdf');
            
            // Company-wise scanning management
            Route::get('/company/{company}',                   [SuperScannerController::class, 'companyView'])              ->name('company');
            Route::get('/company/{company}/scans-data',        [SuperScannerController::class, 'companyScansData'])         ->name('company.scans-data');
            Route::get('/company/{company}/pending-naming',    [SuperScannerController::class, 'companyPendingNamingData']) ->name('company.pending-naming');
            Route::get('/company/{company}/pending-verify',    [SuperScannerController::class, 'companyPendingVerifyData']) ->name('company.pending-verify');
            Route::get('/company/{company}/tab-counts',        [SuperScannerController::class, 'companyTabCounts'])         ->name('company.tab-counts');
            Route::post('/company/{company}/scan',             [SuperScannerController::class, 'companyScan'])              ->name('company.scan');
            Route::post('/company/{company}/verify-document',  [SuperScannerController::class, 'verifyDocument'])           ->name('company.verify-document');
            Route::post('/company/{company}/name-scan',        [SuperScannerController::class, 'nameScan'])                ->name('company.name-scan');
            Route::post('/company/{company}/reject-naming',    [SuperScannerController::class, 'rejectNaming'])            ->name('company.reject-naming');
            Route::get('/company/{company}/scan/{scan:Scan_Id}/support-list', [SuperScannerController::class, 'companySupportList'])    ->name('company.support-list');
            Route::post('/company/{company}/scan/{scan:Scan_Id}/supporting',  [SuperScannerController::class, 'companyStoreSupporting'])->name('company.supporting.store');
            Route::post('/company/{company}/scan/{scan:Scan_Id}/final-submit', [SuperScannerController::class, 'companyFinalSubmit'])    ->name('company.final-submit');
            Route::delete('/company/{company}/scan/{scan:Scan_Id}/support/{supportId}', [SuperScannerController::class, 'companyDestroySupport'])->name('company.support.destroy');
            Route::delete('/company/{company}/scan/{scan:Scan_Id}',           [SuperScannerController::class, 'companyDestroyScan'])   ->name('company.scan.destroy');
            Route::get('/select/doc-types',       [SuperScannerController::class, 'docTypesSelect'])        ->name('select.doc-types');
            
            // Select2 endpoints for company scanning
            Route::get('/select/locations',       [SuperScannerController::class, 'locationsSelect'])      ->name('select.locations');
            Route::get('/select/bill-approvers',  [SuperScannerController::class, 'billApproversSelect']) ->name('select.bill-approvers');
            Route::get('/select/vendors',         [SuperScannerController::class, 'vendorsSelect'])        ->name('select.vendors');
            Route::get('/select/users',           [SuperScannerController::class, 'usersSelect'])          ->name('select.users');
        });

        // Direct Scanner
        Route::prefix('direct-scan')->name('direct-scan.')->group(function () {
            Route::get('/',              [DirectScannerController::class, 'index'])                     ->name('index');
            Route::post('/',             [DirectScannerController::class, 'store'])                     ->name('store');
            Route::get('/data',          [DirectScannerController::class, 'data'])                     ->name('data');
            Route::get('/tab-counts',    [DirectScannerController::class, 'tabCounts'])               ->name('tab-counts');
            Route::get('/locations',     [DirectScannerController::class, 'locationsSelect'])           ->name('locations');
            Route::get('/bill-approvers',[DirectScannerController::class, 'getBillApproversForLocation'])->name('bill-approvers');
            Route::get('/doc-types',     [DirectScannerController::class, 'docTypesSelect'])            ->name('doc-types');
            Route::get('/companies',     [DirectScannerController::class, 'companiesSelect'])           ->name('companies');
            Route::get('/financial-years',[DirectScannerController::class, 'financialYearsSelect'])     ->name('financial-years');
            Route::get('/vendors',       [DirectScannerController::class, 'vendorsSelect'])             ->name('vendors');
            Route::get('/export/excel',  [DirectScannerController::class, 'exportExcel'])           ->name('export.excel');
            Route::get('/export/pdf',    [DirectScannerController::class, 'exportPdf'])             ->name('export.pdf');
            Route::get('/export/logs',   [DirectScannerController::class, 'exportLogs'])            ->name('export.logs');
            Route::get('/{scan}/support-list',       [DirectScannerController::class, 'supportList'])       ->name('support-list');
            Route::post('/{scan}/supporting',        [DirectScannerController::class, 'storeSupporting'])   ->name('supporting.store');
            Route::post('/{scan}/final-submit',      [DirectScannerController::class, 'finalSubmit'])       ->name('final-submit');
            Route::post('/{scan}/resubmit',          [DirectScannerController::class, 'resubmit'])          ->name('resubmit');
            Route::post('/{scan}/replace',           [DirectScannerController::class, 'replaceFile'])       ->name('replace');
            Route::delete('/{scan}',                 [DirectScannerController::class, 'destroy'])           ->name('destroy');
            Route::delete('/{scan}/support/{supportId}', [DirectScannerController::class, 'destroySupport'])->name('support.destroy');
        });

        // Bill Approval
        Route::prefix('bill-approval')->name('bill-approval.')->group(function () {
            Route::get('/',                    [BillApprovalController::class, 'index'])                ->name('index');
            Route::get('/data',                [BillApprovalController::class, 'data'])                 ->name('data');
            Route::get('/tab-counts',          [BillApprovalController::class, 'tabCounts'])            ->name('tab-counts');
            Route::get('/locations',           [BillApprovalController::class, 'locationsSelect'])      ->name('locations');
            Route::get('/users',               [BillApprovalController::class, 'usersSelect'])          ->name('users');
            Route::get('/companies',           [BillApprovalController::class, 'companiesSelect'])      ->name('companies');
            Route::get('/financial-years',     [BillApprovalController::class, 'financialYearsSelect']) ->name('financial-years');
            Route::get('/{scan}/detail',       [BillApprovalController::class, 'scanDetail'])           ->name('detail');
            Route::post('/{scan}/approve',     [BillApprovalController::class, 'approve'])              ->name('approve');
            Route::post('/{scan}/reject',      [BillApprovalController::class, 'reject'])               ->name('reject');
            Route::get('/{scan}/support-list', [BillApprovalController::class, 'supportList'])          ->name('support-list');
            Route::get('/rejection-reasons',   [BillApprovalController::class, 'rejectionReasons'])     ->name('rejection-reasons');
            Route::post('/rejection-reasons',  [BillApprovalController::class, 'storeRejectionReason']) ->name('rejection-reasons.store');
            Route::get('/export/logs',         [BillApprovalController::class, 'exportLogs'])           ->name('export.logs');
            Route::get('/export/excel',        [BillApprovalController::class, 'exportExcel'])          ->name('export.excel');
            Route::get('/export/pdf',          [BillApprovalController::class, 'exportPdf'])            ->name('export.pdf');
        });

        // Classification
        Route::prefix('classification')->name('classification.')->group(function () {
            Route::get('/',                     [ClassificationController::class, 'index'])              ->name('index');
            Route::get('/data',                 [ClassificationController::class, 'data'])               ->name('data');
            Route::get('/tab-counts',           [ClassificationController::class, 'tabCounts'])          ->name('tab-counts');
            Route::post('/classify',            [ClassificationController::class, 'classify'])           ->name('classify');
            Route::get('/{scan}/detail',        [ClassificationController::class, 'scanDetail'])         ->name('detail');
            Route::get('/{scan}/support-list',  [ClassificationController::class, 'supportList'])        ->name('support-list');
            Route::get('/doc-types',            [ClassificationController::class, 'docTypesSelect'])     ->name('doc-types');
            Route::get('/companies',            [ClassificationController::class, 'companiesSelect'])    ->name('companies');
            Route::get('/financial-years',      [ClassificationController::class, 'financialYearsSelect'])->name('financial-years');
            Route::get('/locations',            [ClassificationController::class, 'locationsSelect'])    ->name('locations');
            Route::get('/users',                [ClassificationController::class, 'usersSelect'])        ->name('users');
            Route::get('/approvers',            [ClassificationController::class, 'approversSelect'])    ->name('approvers');
        });

        // Punching
        Route::prefix('punching')->name('punching.')->group(function () {
            Route::get('/',                    [PunchingController::class, 'index'])       ->name('index');
            Route::get('/data',                [PunchingController::class, 'data'])        ->name('data');
            Route::get('/tab-counts',          [PunchingController::class, 'tabCounts'])   ->name('tab-counts');
            Route::get('/scanners',            [PunchingController::class, 'scannersSelect']) ->name('scanners');
            Route::get('/approvers',           [PunchingController::class, 'approversSelect'])->name('approvers');
            Route::get('/doc-types',           [PunchingController::class, 'docTypesSelect']) ->name('doc-types');
            Route::get('/locations',           [PunchingController::class, 'locationsSelect'])->name('locations');

            // Punching Entry (full-page form) — select endpoints MUST come before {scan} routes
            Route::get('/entry/select/items',        [PunchingEntryController::class, 'itemsSelect'])      ->name('entry.select.items');
            Route::post('/entry/select/items/create',[PunchingEntryController::class, 'createItem'])       ->name('entry.select.items.create');
            Route::get('/entry/select/units',        [PunchingEntryController::class, 'unitsSelect'])      ->name('entry.select.units');
            Route::get('/entry/select/buyers',       [PunchingEntryController::class, 'buyersSelect'])     ->name('entry.select.buyers');
            Route::get('/entry/select/vendors',      [PunchingEntryController::class, 'vendorsSelect'])    ->name('entry.select.vendors');
            Route::get('/entry/select/departments',  [PunchingEntryController::class, 'departmentsSelect'])->name('entry.select.departments');
            Route::get('/entry/select/categories',   [PunchingEntryController::class, 'categoriesSelect'])->name('entry.select.categories');
            Route::get('/entry/select/ledgers',      [PunchingEntryController::class, 'ledgersSelect'])    ->name('entry.select.ledgers');
            Route::get('/entry/select/files',        [PunchingEntryController::class, 'filesSelect'])      ->name('entry.select.files');
            Route::get('/entry/select/locations',    [PunchingEntryController::class, 'locationsSelect'])  ->name('entry.select.locations');
            Route::get('/entry/select/employees',   [PunchingEntryController::class, 'employeesSelect']) ->name('entry.select.employees');
            Route::get('/entry/select/last-reading', [PunchingEntryController::class, 'lastReading'])     ->name('entry.select.lastReading');
            Route::get('/entry/select/hotels',       [PunchingEntryController::class, 'hotelsSelect'])    ->name('entry.select.hotels');
            Route::get('/entry/select/agents',       [PunchingEntryController::class, 'agentNamesSelect'])->name('entry.select.agents');
            Route::get('/entry/select/airlines',     [PunchingEntryController::class, 'airlinesSelect'])  ->name('entry.select.airlines');
            Route::get('/entry/{scan}',              [PunchingEntryController::class, 'show'])             ->name('entry');
            Route::get('/entry/{scan}/items',        [PunchingEntryController::class, 'getItems'])         ->name('entry.items');
            Route::get('/entry/{scan}/history',      [PunchingEntryController::class, 'history'])          ->name('entry.history');
            Route::post('/entry/{scan}/save',        [PunchingEntryController::class, 'save'])             ->name('entry.save');
            
            // View routes - must come after entry routes
            Route::get('/{scan}/view',         [PunchingController::class, 'view'])        ->name('view');
            Route::get('/{scan}/detail',       [PunchingController::class, 'scanDetail'])  ->name('detail');
            Route::get('/{scan}/support-list', [PunchingController::class, 'supportList'])->name('support-list');
            Route::post('/{scan}/mark-punched',[PunchingController::class, 'markPunched'])->name('mark-punched');
        });

        // Punch Approval
        Route::prefix('punch-approval')->name('punch-approval.')->group(function () {
            Route::get('/',                    [PunchApprovalController::class, 'index'])              ->name('index');
            Route::get('/data',                [PunchApprovalController::class, 'data'])               ->name('data');
            Route::get('/tab-counts',          [PunchApprovalController::class, 'tabCounts'])          ->name('tab-counts');
            Route::get('/companies',           [PunchApprovalController::class, 'companiesSelect'])    ->name('companies');
            Route::get('/financial-years',     [PunchApprovalController::class, 'financialYearsSelect'])->name('financial-years');
            Route::get('/locations',           [PunchApprovalController::class, 'locationsSelect'])    ->name('locations');
            Route::get('/doc-types',           [PunchApprovalController::class, 'docTypesSelect'])     ->name('doc-types');
            Route::get('/{scan}/detail',       [PunchApprovalController::class, 'scanDetail'])         ->name('detail');
            Route::get('/{scan}/support-list', [PunchApprovalController::class, 'supportList'])        ->name('support-list');
            Route::post('/{scan}/approve',     [PunchApprovalController::class, 'approve'])            ->name('approve');
            Route::post('/{scan}/reject',      [PunchApprovalController::class, 'reject'])             ->name('reject');
        });

    });
});


