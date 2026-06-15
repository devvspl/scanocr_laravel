<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentAiController;
use App\Http\Controllers\Panel\CompanyController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\DocumentTypeController;
use App\Http\Controllers\Panel\FinancialYearController;
use App\Http\Controllers\Panel\GeneratorController;
use App\Http\Controllers\Panel\ImportController;
use App\Http\Controllers\Panel\MasterController;
use App\Http\Controllers\Panel\NumberingController;
use App\Http\Controllers\Panel\PageBuilderController;
use App\Http\Controllers\Panel\PageFieldController;
use App\Http\Controllers\Panel\PageShareManageController;
use App\Http\Controllers\Panel\PermissionController;
use App\Http\Controllers\Panel\PermissionGroupController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\RoleController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\ExtMasterController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/help', [PublicController::class, 'help'])->name('help');

// Public API — Form Structure Share (token-based, no auth)
Route::get('/api/forms/{token}', [\App\Http\Controllers\Api\PageShareController::class, 'show'])->name('api.forms.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
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

    // ── Workflow ──────────────────────────────────────────────────────────────
    Route::prefix('workflow')->name('workflow.')->group(function () {

        // Temp Scanner
        Route::prefix('temp-scan')->name('temp-scan.')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Workflow\TempScannerController::class, 'index'])                     ->name('index');
            Route::post('/',             [\App\Http\Controllers\Workflow\TempScannerController::class, 'store'])                     ->name('store');
            Route::get('/data',          [\App\Http\Controllers\Workflow\TempScannerController::class, 'data'])                     ->name('data');
            Route::get('/tab-counts',    [\App\Http\Controllers\Workflow\TempScannerController::class, 'tabCounts'])               ->name('tab-counts');
            Route::get('/locations',     [\App\Http\Controllers\Workflow\TempScannerController::class, 'locationsSelect'])           ->name('locations');
            Route::get('/bill-approvers',[\App\Http\Controllers\Workflow\TempScannerController::class, 'getBillApproversForLocation'])->name('bill-approvers');
            Route::get('/doc-types',     [\App\Http\Controllers\Workflow\TempScannerController::class, 'docTypesSelect'])            ->name('doc-types');
            Route::get('/companies',     [\App\Http\Controllers\Workflow\TempScannerController::class, 'companiesSelect'])           ->name('companies');
            Route::get('/financial-years',[\App\Http\Controllers\Workflow\TempScannerController::class, 'financialYearsSelect'])     ->name('financial-years');
            Route::get('/export/excel',  [\App\Http\Controllers\Workflow\TempScannerController::class, 'exportExcel'])           ->name('export.excel');
            Route::get('/export/pdf',    [\App\Http\Controllers\Workflow\TempScannerController::class, 'exportPdf'])             ->name('export.pdf');
            Route::get('/export/logs',   [\App\Http\Controllers\Workflow\TempScannerController::class, 'exportLogs'])            ->name('export.logs');
            Route::get('/{scan}/support-list',       [\App\Http\Controllers\Workflow\TempScannerController::class, 'supportList'])       ->name('support-list');
            Route::post('/{scan}/supporting',        [\App\Http\Controllers\Workflow\TempScannerController::class, 'storeSupporting'])   ->name('supporting.store');
            Route::post('/{scan}/final-submit',      [\App\Http\Controllers\Workflow\TempScannerController::class, 'finalSubmit'])       ->name('final-submit');
            Route::post('/{scan}/resubmit',          [\App\Http\Controllers\Workflow\TempScannerController::class, 'resubmit'])          ->name('resubmit');
            Route::post('/{scan}/replace',           [\App\Http\Controllers\Workflow\TempScannerController::class, 'replaceFile'])       ->name('replace');
            Route::delete('/{scan}',                 [\App\Http\Controllers\Workflow\TempScannerController::class, 'destroy'])           ->name('destroy');
            Route::delete('/{scan}/support/{supportId}', [\App\Http\Controllers\Workflow\TempScannerController::class, 'destroySupport'])->name('support.destroy');
        });

        // ── Super Scanner (summary dashboard for Super Scanner role) ──────────────
        Route::prefix('super-scanner')->name('super-scanner.')->group(function () {
            Route::get('/',               [\App\Http\Controllers\Workflow\SuperScannerController::class, 'index'])       ->name('index');
            Route::get('/data',           [\App\Http\Controllers\Workflow\SuperScannerController::class, 'data'])        ->name('data');
            Route::get('/totals',         [\App\Http\Controllers\Workflow\SuperScannerController::class, 'totals'])      ->name('totals');
            Route::get('/detail',         [\App\Http\Controllers\Workflow\SuperScannerController::class, 'detail'])      ->name('detail');
            Route::get('/export/excel',   [\App\Http\Controllers\Workflow\SuperScannerController::class, 'exportExcel']) ->name('export.excel');
            Route::get('/export/pdf',     [\App\Http\Controllers\Workflow\SuperScannerController::class, 'exportPdf'])   ->name('export.pdf');
            
            // Company-wise scanning management
            Route::get('/company/{company}',                   [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyView'])              ->name('company');
            Route::get('/company/{company}/scans-data',        [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyScansData'])         ->name('company.scans-data');
            Route::get('/company/{company}/pending-naming',    [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyPendingNamingData']) ->name('company.pending-naming');
            Route::get('/company/{company}/pending-verify',    [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyPendingVerifyData']) ->name('company.pending-verify');
            Route::get('/company/{company}/tab-counts',        [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyTabCounts'])         ->name('company.tab-counts');
            Route::post('/company/{company}/scan',             [\App\Http\Controllers\Workflow\SuperScannerController::class, 'companyScan'])              ->name('company.scan');
            Route::post('/company/{company}/verify-document',  [\App\Http\Controllers\Workflow\SuperScannerController::class, 'verifyDocument'])           ->name('company.verify-document');
            
            // Select2 endpoints for company scanning
            Route::get('/select/locations',       [\App\Http\Controllers\Workflow\SuperScannerController::class, 'locationsSelect'])      ->name('select.locations');
            Route::get('/select/bill-approvers',  [\App\Http\Controllers\Workflow\SuperScannerController::class, 'billApproversSelect']) ->name('select.bill-approvers');
            Route::get('/select/vendors',         [\App\Http\Controllers\Workflow\SuperScannerController::class, 'vendorsSelect'])        ->name('select.vendors');
            Route::get('/select/users',           [\App\Http\Controllers\Workflow\SuperScannerController::class, 'usersSelect'])          ->name('select.users');
        });

        // Direct Scanner
        Route::prefix('direct-scan')->name('direct-scan.')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Workflow\DirectScannerController::class, 'index'])                     ->name('index');
            Route::post('/',             [\App\Http\Controllers\Workflow\DirectScannerController::class, 'store'])                     ->name('store');
            Route::get('/data',          [\App\Http\Controllers\Workflow\DirectScannerController::class, 'data'])                     ->name('data');
            Route::get('/tab-counts',    [\App\Http\Controllers\Workflow\DirectScannerController::class, 'tabCounts'])               ->name('tab-counts');
            Route::get('/locations',     [\App\Http\Controllers\Workflow\DirectScannerController::class, 'locationsSelect'])           ->name('locations');
            Route::get('/bill-approvers',[\App\Http\Controllers\Workflow\DirectScannerController::class, 'getBillApproversForLocation'])->name('bill-approvers');
            Route::get('/doc-types',     [\App\Http\Controllers\Workflow\DirectScannerController::class, 'docTypesSelect'])            ->name('doc-types');
            Route::get('/companies',     [\App\Http\Controllers\Workflow\DirectScannerController::class, 'companiesSelect'])           ->name('companies');
            Route::get('/financial-years',[\App\Http\Controllers\Workflow\DirectScannerController::class, 'financialYearsSelect'])     ->name('financial-years');
            Route::get('/vendors',       [\App\Http\Controllers\Workflow\DirectScannerController::class, 'vendorsSelect'])             ->name('vendors');
            Route::get('/export/excel',  [\App\Http\Controllers\Workflow\DirectScannerController::class, 'exportExcel'])           ->name('export.excel');
            Route::get('/export/pdf',    [\App\Http\Controllers\Workflow\DirectScannerController::class, 'exportPdf'])             ->name('export.pdf');
            Route::get('/export/logs',   [\App\Http\Controllers\Workflow\DirectScannerController::class, 'exportLogs'])            ->name('export.logs');
            Route::get('/{scan}/support-list',       [\App\Http\Controllers\Workflow\DirectScannerController::class, 'supportList'])       ->name('support-list');
            Route::post('/{scan}/supporting',        [\App\Http\Controllers\Workflow\DirectScannerController::class, 'storeSupporting'])   ->name('supporting.store');
            Route::post('/{scan}/final-submit',      [\App\Http\Controllers\Workflow\DirectScannerController::class, 'finalSubmit'])       ->name('final-submit');
            Route::post('/{scan}/resubmit',          [\App\Http\Controllers\Workflow\DirectScannerController::class, 'resubmit'])          ->name('resubmit');
            Route::post('/{scan}/replace',           [\App\Http\Controllers\Workflow\DirectScannerController::class, 'replaceFile'])       ->name('replace');
            Route::delete('/{scan}',                 [\App\Http\Controllers\Workflow\DirectScannerController::class, 'destroy'])           ->name('destroy');
            Route::delete('/{scan}/support/{supportId}', [\App\Http\Controllers\Workflow\DirectScannerController::class, 'destroySupport'])->name('support.destroy');
        });

    });
});


