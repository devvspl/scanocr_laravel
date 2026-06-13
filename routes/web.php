<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentAiController;
use App\Http\Controllers\Panel\AccountController;
use App\Http\Controllers\Workflow\WorkflowActionController;
use App\Http\Controllers\Workflow\WorkflowController;
use App\Http\Controllers\Workflow\WorkflowLogController;
use App\Http\Controllers\Workflow\WorkflowRoutingController;
use App\Http\Controllers\Workflow\WorkflowStageController;
use App\Http\Controllers\Panel\AccountGroupController;
use App\Http\Controllers\Panel\CompanyController;
use App\Http\Controllers\Panel\CustomerController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\DeliveryNoteController;
use App\Http\Controllers\Panel\DocumentTypeController;
use App\Http\Controllers\Panel\FinancialYearController;
use App\Http\Controllers\Panel\GeneratorController;
use App\Http\Controllers\Panel\HsnCodeController;
use App\Http\Controllers\Panel\ImportController;
use App\Http\Controllers\Panel\ItemGroupController;
use App\Http\Controllers\Panel\MasterController;
use App\Http\Controllers\Panel\NatureController;
use App\Http\Controllers\Panel\NumberingController;
use App\Http\Controllers\Panel\PageBuilderController;
use App\Http\Controllers\Panel\PageFieldController;
use App\Http\Controllers\Panel\PermissionController;
use App\Http\Controllers\Panel\PermissionGroupController;
use App\Http\Controllers\Panel\ProductController;
use App\Http\Controllers\Panel\ProfileController;
use App\Http\Controllers\Panel\ProformaInvoiceController;
use App\Http\Controllers\Panel\RoleController;
use App\Http\Controllers\Panel\SaleInvoiceController;
use App\Http\Controllers\Panel\SettingsController;
use App\Http\Controllers\Panel\TaxRateController;
use App\Http\Controllers\Panel\UnitOfMeasureController;
use App\Http\Controllers\Panel\UserController;
use App\Http\Controllers\Panel\VendorController;
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

// Email-based approval action (token-secured, no auth required)
Route::get('/approval/action/{token}/{action}', function (string $token, string $action) {
    if (!in_array($action, ['approve', 'reject'])) {
        abort(404);
    }
    $result = \App\Http\Controllers\Panel\SaleInvoiceController::handleEmailAction($token, $action);
    return view('emails.approval-result', ['result' => $result, 'action' => $action]);
})->name('approval.email-action');

// Digital Signature — public signing page (token-based, no auth)
Route::get('/approval/sign/{token}', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'showSigningPage'])->name('approval.sign');
Route::post('/approval/sign/{token}', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'processSignature'])->name('approval.sign.submit');

// Signature Upload — public page (token-based, no auth)
Route::get('/signature/upload/{token}', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'showUploadPage'])->name('signature.upload');
Route::post('/signature/upload/{token}', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'processUpload'])->name('signature.upload.submit');

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

    // Page Builder CRUD
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
    Route::get('/master/page-builder/{page}/shares', [\App\Http\Controllers\Panel\PageShareManageController::class, 'index'])->name('master.page-builder.shares');
    Route::post('/master/page-builder/{page}/shares', [\App\Http\Controllers\Panel\PageShareManageController::class, 'store'])->name('master.page-builder.shares.store');
    Route::post('/master/page-builder/{page}/shares/{share}/toggle', [\App\Http\Controllers\Panel\PageShareManageController::class, 'toggle'])->name('master.page-builder.shares.toggle');
    Route::delete('/master/page-builder/{page}/shares/{share}', [\App\Http\Controllers\Panel\PageShareManageController::class, 'destroy'])->name('master.page-builder.shares.destroy');

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

    // Import Data Routes
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
    // Account Groups
    Route::get('/master/account-groups', [AccountGroupController::class, 'index'])->name('master.account-groups');
    Route::post('/master/account-groups', [AccountGroupController::class, 'store'])->name('master.account-groups.store');
    Route::put('/master/account-groups/{accountGroup}', [AccountGroupController::class, 'update'])->name('master.account-groups.update');
    Route::delete('/master/account-groups/{accountGroup}', [AccountGroupController::class, 'destroy'])->name('master.account-groups.destroy');

    // Natures
    Route::get('/master/natures', [NatureController::class, 'index'])->name('master.natures.index');
    Route::post('/master/natures', [NatureController::class, 'store'])->name('master.natures.store');
    Route::put('/master/natures/{nature}', [NatureController::class, 'update'])->name('master.natures.update');
    Route::delete('/master/natures/{nature}', [NatureController::class, 'destroy'])->name('master.natures.destroy');

    // Chart of Accounts
    Route::get('/master/accounts', [AccountController::class, 'index'])->name('master.accounts');
    Route::get('/master/accounts/data', [AccountController::class, 'data'])->name('master.accounts.data');
    Route::post('/master/accounts', [AccountController::class, 'store'])->name('master.accounts.store');
    Route::get('/master/accounts/{account}', [AccountController::class, 'show'])->name('master.accounts.show');
    Route::put('/master/accounts/{account}', [AccountController::class, 'update'])->name('master.accounts.update');
    Route::delete('/master/accounts/{account}', [AccountController::class, 'destroy'])->name('master.accounts.destroy');

    // Customers
    Route::get('/master/customers', [CustomerController::class, 'index'])->name('master.customers');
    Route::get('/master/customers/data', [CustomerController::class, 'data'])->name('master.customers.data');
    Route::post('/master/customers', [CustomerController::class, 'store'])->name('master.customers.store');
    Route::get('/master/customers/{customer}', [CustomerController::class, 'show'])->name('master.customers.show');
    Route::put('/master/customers/{customer}', [CustomerController::class, 'update'])->name('master.customers.update');
    Route::delete('/master/customers/{customer}', [CustomerController::class, 'destroy'])->name('master.customers.destroy');

    // Vendors
    Route::get('/master/vendors', [VendorController::class, 'index'])->name('master.vendors');
    Route::get('/master/vendors/data', [VendorController::class, 'data'])->name('master.vendors.data');
    Route::post('/master/vendors', [VendorController::class, 'store'])->name('master.vendors.store');
    Route::get('/master/vendors/{vendor}', [VendorController::class, 'show'])->name('master.vendors.show');
    Route::put('/master/vendors/{vendor}', [VendorController::class, 'update'])->name('master.vendors.update');
    Route::delete('/master/vendors/{vendor}', [VendorController::class, 'destroy'])->name('master.vendors.destroy');

    // Products
    Route::get('/master/products', [ProductController::class, 'index'])->name('master.products');
    Route::get('/master/products/data', [ProductController::class, 'data'])->name('master.products.data');
    Route::post('/master/products', [ProductController::class, 'store'])->name('master.products.store');
    Route::get('/master/products/{product}', [ProductController::class, 'show'])->name('master.products.show');
    Route::put('/master/products/{product}', [ProductController::class, 'update'])->name('master.products.update');
    Route::delete('/master/products/{product}', [ProductController::class, 'destroy'])->name('master.products.destroy');

    // Item Groups
    Route::get('/master/item-groups', [ItemGroupController::class, 'index'])->name('master.item-groups');
    Route::get('/master/item-groups/data', [ItemGroupController::class, 'data'])->name('master.item-groups.data');
    Route::post('/master/item-groups', [ItemGroupController::class, 'store'])->name('master.item-groups.store');
    Route::get('/master/item-groups/{itemGroup}', [ItemGroupController::class, 'show'])->name('master.item-groups.show');
    Route::put('/master/item-groups/{itemGroup}', [ItemGroupController::class, 'update'])->name('master.item-groups.update');
    Route::delete('/master/item-groups/{itemGroup}', [ItemGroupController::class, 'destroy'])->name('master.item-groups.destroy');

    // Units of Measure
    Route::get('/master/units', [UnitOfMeasureController::class, 'index'])->name('master.units');
    Route::get('/master/units/data', [UnitOfMeasureController::class, 'data'])->name('master.units.data');
    Route::post('/master/units', [UnitOfMeasureController::class, 'store'])->name('master.units.store');
    Route::get('/master/units/{unit}', [UnitOfMeasureController::class, 'show'])->name('master.units.show');
    Route::put('/master/units/{unit}', [UnitOfMeasureController::class, 'update'])->name('master.units.update');
    Route::delete('/master/units/{unit}', [UnitOfMeasureController::class, 'destroy'])->name('master.units.destroy');

    // Tax Rates
    Route::get('/master/taxes', [TaxRateController::class, 'index'])->name('master.taxes');
    Route::get('/master/taxes/data', [TaxRateController::class, 'data'])->name('master.taxes.data');
    Route::post('/master/taxes', [TaxRateController::class, 'store'])->name('master.taxes.store');
    Route::get('/master/taxes/{taxRate}', [TaxRateController::class, 'show'])->name('master.taxes.show');
    Route::put('/master/taxes/{taxRate}', [TaxRateController::class, 'update'])->name('master.taxes.update');
    Route::delete('/master/taxes/{taxRate}', [TaxRateController::class, 'destroy'])->name('master.taxes.destroy');

    // HSN / SAC Codes
    Route::get('/master/hsn', [HsnCodeController::class, 'index'])->name('master.hsn');
    Route::get('/master/hsn/data', [HsnCodeController::class, 'data'])->name('master.hsn.data');
    Route::post('/master/hsn', [HsnCodeController::class, 'store'])->name('master.hsn.store');
    Route::get('/master/hsn/{hsnCode}', [HsnCodeController::class, 'show'])->name('master.hsn.show');
    Route::put('/master/hsn/{hsnCode}', [HsnCodeController::class, 'update'])->name('master.hsn.update');
    Route::delete('/master/hsn/{hsnCode}', [HsnCodeController::class, 'destroy'])->name('master.hsn.destroy');

    // ── Workflow Designer ─────────────────────────────────────────────────────
    Route::get('/master/workflow',                    [WorkflowController::class, 'index'])->name('master.workflow.index');
    Route::get('/master/workflow/create',             [WorkflowController::class, 'create'])->name('master.workflow.create');
    Route::post('/master/workflow',                   [WorkflowController::class, 'store'])->name('master.workflow.store');
    Route::get('/master/workflow/{id}/designer',      [WorkflowController::class, 'designer'])->name('master.workflow.designer');
    Route::post('/master/workflow/{id}/duplicate',    [WorkflowController::class, 'duplicate'])->name('master.workflow.duplicate');
    Route::post('/master/workflow/{id}/activate',     [WorkflowController::class, 'activate'])->name('master.workflow.activate');
    Route::post('/master/workflow/{id}/publish',      [WorkflowController::class, 'publish'])->name('master.workflow.publish');
    Route::delete('/master/workflow/{id}',            [WorkflowController::class, 'destroy'])->name('master.workflow.destroy');

    // Workflow — Stage routes
    Route::post('/master/workflow/{workflowId}/stage',                  [WorkflowStageController::class, 'store'])->name('master.workflow.stage.store');
    Route::put('/master/workflow/{workflowId}/stage/{stageId}',        [WorkflowStageController::class, 'update'])->name('master.workflow.stage.update');
    Route::delete('/master/workflow/{workflowId}/stage/{stageId}',     [WorkflowStageController::class, 'destroy'])->name('master.workflow.stage.destroy');
    Route::post('/master/workflow/{workflowId}/stage/reorder',          [WorkflowStageController::class, 'reorder'])->name('master.workflow.stage.reorder');
    Route::post('/master/workflow/{workflowId}/stage/{stageId}/roles',  [WorkflowStageController::class, 'roles'])->name('master.workflow.stage.roles');
    Route::post('/master/workflow/{workflowId}/stage/{stageId}/actions', [WorkflowStageController::class, 'syncActions'])->name('master.workflow.stage.actions');
    Route::put('/master/workflow/{workflowId}/stage-action/{mapId}', [WorkflowStageController::class, 'updateActionConfig'])->name('master.workflow.stage-action.update');
    Route::get('/master/workflow/{workflowId}/stage/{stageId}/widgets', [WorkflowStageController::class, 'getWidgets'])->name('master.workflow.stage.widgets');
    Route::post('/master/workflow/{workflowId}/stage/{stageId}/widgets', [WorkflowStageController::class, 'saveWidgets'])->name('master.workflow.stage.widgets.save');

    // Workflow — Action routes
    Route::post('/master/workflow/{workflowId}/action/{actionId}/toggle', [WorkflowActionController::class, 'toggle'])->name('master.workflow.action.toggle');
    Route::put('/master/workflow/{workflowId}/action/{actionId}',        [WorkflowActionController::class, 'update'])->name('master.workflow.action.update');

    // Workflow — Routing rules
    Route::get('/master/workflow/{workflowId}/routing',           [WorkflowRoutingController::class, 'index'])->name('master.workflow.routing.index');
    Route::post('/master/workflow/{workflowId}/routing',          [WorkflowRoutingController::class, 'store'])->name('master.workflow.routing.store');
    Route::put('/master/workflow/{workflowId}/routing/{ruleId}',  [WorkflowRoutingController::class, 'update'])->name('master.workflow.routing.update');
    Route::delete('/master/workflow/{workflowId}/routing/{ruleId}', [WorkflowRoutingController::class, 'destroy'])->name('master.workflow.routing.destroy');

    // Workflow — Logs
    Route::get('/master/workflow/{workflowId}/log', [WorkflowLogController::class, 'index'])->name('master.workflow.log');

    // Workflow — Page fields preview (JSON)
    Route::get('/master/workflow/page-fields/{pageId}', [WorkflowController::class, 'pageFields'])->name('master.workflow.page-fields');

    // Workflow — Run (published workflow process page)
    Route::get('/process/workflow/{id}', [WorkflowController::class, 'run'])->name('workflow.run');
    Route::post('/process/workflow/{id}/action', [\App\Http\Controllers\Workflow\WorkflowEntryController::class, 'executeAction'])->name('workflow.entry.action');
    Route::get('/process/workflow/{id}/entries', [\App\Http\Controllers\Workflow\WorkflowEntryController::class, 'list'])->name('workflow.entry.list');

    Route::get('/master/{tab}', [MasterController::class, 'tab'])->name('master.tab');

    // Sales Invoices
    Route::get('/sales/invoices', [SaleInvoiceController::class, 'index'])->name('sales.invoices');
    Route::get('/sales/invoices/data', [SaleInvoiceController::class, 'data'])->name('sales.invoices.data');
    Route::get('/sales/invoices/create', [SaleInvoiceController::class, 'create'])->name('sales.invoices.create');
    Route::post('/sales/invoices', [SaleInvoiceController::class, 'store'])->name('sales.invoices.store');
    Route::get('/sales/invoices/next-number', [SaleInvoiceController::class, 'nextNumber'])->name('sales.invoices.next-number');
    Route::get('/sales/invoices/search-customers', [SaleInvoiceController::class, 'searchCustomers'])->name('sales.invoices.search-customers');
    Route::get('/sales/invoices/search-products', [SaleInvoiceController::class, 'searchProducts'])->name('sales.invoices.search-products');
    Route::get('/sales/invoices/product/{product}', [SaleInvoiceController::class, 'productDetails'])->name('sales.invoices.product');
    Route::get('/sales/invoices/{invoice}', [SaleInvoiceController::class, 'show'])->name('sales.invoices.show');
    Route::get('/sales/invoices/{invoice}/pdf', [SaleInvoiceController::class, 'pdf'])->name('sales.invoices.pdf');
    Route::get('/sales/invoices/{invoice}/edit', [SaleInvoiceController::class, 'edit'])->name('sales.invoices.edit');
    Route::put('/sales/invoices/{invoice}', [SaleInvoiceController::class, 'update'])->name('sales.invoices.update');
    Route::delete('/sales/invoices/{invoice}', [SaleInvoiceController::class, 'destroy'])->name('sales.invoices.destroy');
    Route::post('/sales/invoices/{invoice}/submit', [SaleInvoiceController::class, 'submit'])->name('sales.invoices.submit');
    Route::post('/sales/invoices/{invoice}/approve', [SaleInvoiceController::class, 'approve'])->name('sales.invoices.approve');
    Route::post('/sales/invoices/{invoice}/reject', [SaleInvoiceController::class, 'reject'])->name('sales.invoices.reject');
    Route::post('/sales/invoices/{invoice}/cancel', [SaleInvoiceController::class, 'cancel'])->name('sales.invoices.cancel');
    Route::get('/sales/invoices/{invoice}/approval-logs', [SaleInvoiceController::class, 'approvalLogs'])->name('sales.invoices.approval-logs');
    Route::post('/sales/invoices/{invoice}/level-approve', [SaleInvoiceController::class, 'levelApprove'])->name('sales.invoices.level-approve');
    Route::post('/sales/invoices/{invoice}/level-reject', [SaleInvoiceController::class, 'levelReject'])->name('sales.invoices.level-reject');

    // Proforma Invoices
    Route::get('/sales/proforma', [ProformaInvoiceController::class, 'index'])->name('sales.proforma');
    Route::get('/sales/proforma/data', [ProformaInvoiceController::class, 'data'])->name('sales.proforma.data');
    Route::get('/sales/proforma/create', [ProformaInvoiceController::class, 'create'])->name('sales.proforma.create');
    Route::post('/sales/proforma', [ProformaInvoiceController::class, 'store'])->name('sales.proforma.store');
    Route::get('/sales/proforma/next-number', [ProformaInvoiceController::class, 'nextNumber'])->name('sales.proforma.next-number');
    Route::get('/sales/proforma/search-customers', [ProformaInvoiceController::class, 'searchCustomers'])->name('sales.proforma.search-customers');
    Route::get('/sales/proforma/search-products', [ProformaInvoiceController::class, 'searchProducts'])->name('sales.proforma.search-products');
    Route::get('/sales/proforma/product/{product}', [ProformaInvoiceController::class, 'productDetails'])->name('sales.proforma.product');
    Route::get('/sales/proforma/{proforma}', [ProformaInvoiceController::class, 'show'])->name('sales.proforma.show');
    Route::get('/sales/proforma/{proforma}/pdf', [ProformaInvoiceController::class, 'pdf'])->name('sales.proforma.pdf');
    Route::get('/sales/proforma/{proforma}/edit', [ProformaInvoiceController::class, 'edit'])->name('sales.proforma.edit');
    Route::put('/sales/proforma/{proforma}', [ProformaInvoiceController::class, 'update'])->name('sales.proforma.update');
    Route::delete('/sales/proforma/{proforma}', [ProformaInvoiceController::class, 'destroy'])->name('sales.proforma.destroy');
    Route::post('/sales/proforma/{proforma}/submit', [ProformaInvoiceController::class, 'submit'])->name('sales.proforma.submit');
    Route::post('/sales/proforma/{proforma}/approve', [ProformaInvoiceController::class, 'approve'])->name('sales.proforma.approve');
    Route::post('/sales/proforma/{proforma}/reject', [ProformaInvoiceController::class, 'reject'])->name('sales.proforma.reject');
    Route::post('/sales/proforma/{proforma}/cancel', [ProformaInvoiceController::class, 'cancel'])->name('sales.proforma.cancel');
    Route::get('/sales/proforma/{proforma}/approval-logs', [ProformaInvoiceController::class, 'approvalLogs'])->name('sales.proforma.approval-logs');
    Route::post('/sales/proforma/{proforma}/level-approve', [ProformaInvoiceController::class, 'levelApprove'])->name('sales.proforma.level-approve');
    Route::post('/sales/proforma/{proforma}/level-reject', [ProformaInvoiceController::class, 'levelReject'])->name('sales.proforma.level-reject');
    Route::post('/sales/proforma/{proforma}/convert', [ProformaInvoiceController::class, 'convertToInvoice'])->name('sales.proforma.convert');

    // Delivery Notes
    Route::get('/sales/delivery', [DeliveryNoteController::class, 'index'])->name('sales.delivery');
    Route::get('/sales/delivery/data', [DeliveryNoteController::class, 'data'])->name('sales.delivery.data');
    Route::get('/sales/delivery/create', [DeliveryNoteController::class, 'create'])->name('sales.delivery.create');
    Route::post('/sales/delivery', [DeliveryNoteController::class, 'store'])->name('sales.delivery.store');
    Route::get('/sales/delivery/next-number', [DeliveryNoteController::class, 'nextNumber'])->name('sales.delivery.next-number');
    Route::get('/sales/delivery/search-customers', [DeliveryNoteController::class, 'searchCustomers'])->name('sales.delivery.search-customers');
    Route::get('/sales/delivery/search-transporters', [DeliveryNoteController::class, 'searchTransporters'])->name('sales.delivery.search-transporters');
    Route::get('/sales/delivery/search-products', [DeliveryNoteController::class, 'searchProducts'])->name('sales.delivery.search-products');
    Route::get('/sales/delivery/search-invoices', [DeliveryNoteController::class, 'searchInvoices'])->name('sales.delivery.search-invoices');
    Route::get('/sales/delivery/{delivery}', [DeliveryNoteController::class, 'show'])->name('sales.delivery.show');
    Route::get('/sales/delivery/{delivery}/pdf', [DeliveryNoteController::class, 'pdf'])->name('sales.delivery.pdf');
    Route::get('/sales/delivery/{delivery}/edit', [DeliveryNoteController::class, 'edit'])->name('sales.delivery.edit');
    Route::put('/sales/delivery/{delivery}', [DeliveryNoteController::class, 'update'])->name('sales.delivery.update');
    Route::delete('/sales/delivery/{delivery}', [DeliveryNoteController::class, 'destroy'])->name('sales.delivery.destroy');
    Route::post('/sales/delivery/{delivery}/submit', [DeliveryNoteController::class, 'submit'])->name('sales.delivery.submit');
    Route::post('/sales/delivery/{delivery}/approve', [DeliveryNoteController::class, 'approve'])->name('sales.delivery.approve');
    Route::post('/sales/delivery/{delivery}/reject', [DeliveryNoteController::class, 'reject'])->name('sales.delivery.reject');
    Route::post('/sales/delivery/{delivery}/cancel', [DeliveryNoteController::class, 'cancel'])->name('sales.delivery.cancel');
    Route::post('/sales/delivery/{delivery}/mark-delivered', [DeliveryNoteController::class, 'markDelivered'])->name('sales.delivery.mark-delivered');
    Route::get('/sales/delivery/{delivery}/approval-logs', [DeliveryNoteController::class, 'approvalLogs'])->name('sales.delivery.approval-logs');
    Route::post('/sales/delivery/{delivery}/level-approve', [DeliveryNoteController::class, 'levelApprove'])->name('sales.delivery.level-approve');
    Route::post('/sales/delivery/{delivery}/level-reject', [DeliveryNoteController::class, 'levelReject'])->name('sales.delivery.level-reject');

    // Credit Notes
    Route::get('/sales/credit-notes', [\App\Http\Controllers\Panel\CreditNoteController::class, 'index'])->name('sales.credit-notes');
    Route::get('/sales/credit-notes/data', [\App\Http\Controllers\Panel\CreditNoteController::class, 'data'])->name('sales.credit-notes.data');
    Route::get('/sales/credit-notes/create', [\App\Http\Controllers\Panel\CreditNoteController::class, 'create'])->name('sales.credit-notes.create');
    Route::post('/sales/credit-notes', [\App\Http\Controllers\Panel\CreditNoteController::class, 'store'])->name('sales.credit-notes.store');
    Route::get('/sales/credit-notes/next-number', [\App\Http\Controllers\Panel\CreditNoteController::class, 'nextNumber'])->name('sales.credit-notes.next-number');
    Route::get('/sales/credit-notes/search-customers', [\App\Http\Controllers\Panel\CreditNoteController::class, 'searchCustomers'])->name('sales.credit-notes.search-customers');
    Route::get('/sales/credit-notes/search-products', [\App\Http\Controllers\Panel\CreditNoteController::class, 'searchProducts'])->name('sales.credit-notes.search-products');
    Route::get('/sales/credit-notes/product/{product}', [\App\Http\Controllers\Panel\CreditNoteController::class, 'productDetails'])->name('sales.credit-notes.product');
    Route::get('/sales/credit-notes/{creditNote}', [\App\Http\Controllers\Panel\CreditNoteController::class, 'show'])->name('sales.credit-notes.show');
    Route::get('/sales/credit-notes/{creditNote}/pdf', [\App\Http\Controllers\Panel\CreditNoteController::class, 'pdf'])->name('sales.credit-notes.pdf');
    Route::get('/sales/credit-notes/{creditNote}/edit', [\App\Http\Controllers\Panel\CreditNoteController::class, 'edit'])->name('sales.credit-notes.edit');
    Route::put('/sales/credit-notes/{creditNote}', [\App\Http\Controllers\Panel\CreditNoteController::class, 'update'])->name('sales.credit-notes.update');
    Route::delete('/sales/credit-notes/{creditNote}', [\App\Http\Controllers\Panel\CreditNoteController::class, 'destroy'])->name('sales.credit-notes.destroy');
    Route::post('/sales/credit-notes/{creditNote}/submit', [\App\Http\Controllers\Panel\CreditNoteController::class, 'submit'])->name('sales.credit-notes.submit');
    Route::post('/sales/credit-notes/{creditNote}/approve', [\App\Http\Controllers\Panel\CreditNoteController::class, 'approve'])->name('sales.credit-notes.approve');
    Route::post('/sales/credit-notes/{creditNote}/reject', [\App\Http\Controllers\Panel\CreditNoteController::class, 'reject'])->name('sales.credit-notes.reject');
    Route::post('/sales/credit-notes/{creditNote}/cancel', [\App\Http\Controllers\Panel\CreditNoteController::class, 'cancel'])->name('sales.credit-notes.cancel');
    Route::get('/sales/credit-notes/{creditNote}/approval-logs', [\App\Http\Controllers\Panel\CreditNoteController::class, 'approvalLogs'])->name('sales.credit-notes.approval-logs');
    Route::post('/sales/credit-notes/{creditNote}/level-approve', [\App\Http\Controllers\Panel\CreditNoteController::class, 'levelApprove'])->name('sales.credit-notes.level-approve');
    Route::post('/sales/credit-notes/{creditNote}/level-reject', [\App\Http\Controllers\Panel\CreditNoteController::class, 'levelReject'])->name('sales.credit-notes.level-reject');

    // Ledger / Accounting
    Route::get('/ledgers/accounts', [\App\Http\Controllers\Panel\LedgerController::class, 'accountsList'])->name('ledgers.accounts');
    Route::get('/ledgers/accounts/{account}', [\App\Http\Controllers\Panel\LedgerController::class, 'accountLedger'])->name('ledgers.account');
    Route::get('/ledgers/accounts/{account}/pdf', [\App\Http\Controllers\Panel\LedgerController::class, 'exportAccountLedgerPdf'])->name('ledgers.account.pdf');
    Route::get('/ledgers/parties', [\App\Http\Controllers\Panel\LedgerController::class, 'partiesList'])->name('ledgers.parties');
    Route::get('/ledgers/parties/{party}', [\App\Http\Controllers\Panel\LedgerController::class, 'partyLedger'])->name('ledgers.party');

    // Receipts
    Route::get('/sales/receipts', [\App\Http\Controllers\Panel\ReceiptController::class, 'index'])->name('sales.receipts');

    // Sales Reports
    Route::get('/sales/register', [\App\Http\Controllers\Panel\SalesReportController::class, 'register'])->name('sales.register');
    Route::get('/sales/register/data', [\App\Http\Controllers\Panel\SalesReportController::class, 'registerData'])->name('sales.register.data');
    Route::get('/sales/register/export', [\App\Http\Controllers\Panel\SalesReportController::class, 'exportRegister'])->name('sales.register.export');
    Route::get('/sales/outstanding', [\App\Http\Controllers\Panel\SalesReportController::class, 'outstanding'])->name('sales.outstanding');
    Route::get('/sales/outstanding/data', [\App\Http\Controllers\Panel\SalesReportController::class, 'outstandingData'])->name('sales.outstanding.data');
    Route::get('/sales/outstanding/export', [\App\Http\Controllers\Panel\SalesReportController::class, 'exportOutstanding'])->name('sales.outstanding.export');
    Route::get('/sales/receipts/data', [\App\Http\Controllers\Panel\ReceiptController::class, 'data'])->name('sales.receipts.data');
    Route::get('/sales/receipts/create', [\App\Http\Controllers\Panel\ReceiptController::class, 'create'])->name('sales.receipts.create');
    Route::post('/sales/receipts', [\App\Http\Controllers\Panel\ReceiptController::class, 'store'])->name('sales.receipts.store');
    Route::get('/sales/receipts/next-number', [\App\Http\Controllers\Panel\ReceiptController::class, 'nextNumber'])->name('sales.receipts.next-number');
    Route::get('/sales/receipts/search-customers', [\App\Http\Controllers\Panel\ReceiptController::class, 'searchCustomers'])->name('sales.receipts.search-customers');
    Route::get('/sales/receipts/{receipt}', [\App\Http\Controllers\Panel\ReceiptController::class, 'show'])->name('sales.receipts.show');
    Route::get('/sales/receipts/{receipt}/pdf', [\App\Http\Controllers\Panel\ReceiptController::class, 'pdf'])->name('sales.receipts.pdf');
    Route::get('/sales/receipts/{receipt}/edit', [\App\Http\Controllers\Panel\ReceiptController::class, 'edit'])->name('sales.receipts.edit');
    Route::put('/sales/receipts/{receipt}', [\App\Http\Controllers\Panel\ReceiptController::class, 'update'])->name('sales.receipts.update');
    Route::delete('/sales/receipts/{receipt}', [\App\Http\Controllers\Panel\ReceiptController::class, 'destroy'])->name('sales.receipts.destroy');
    Route::post('/sales/receipts/{receipt}/submit', [\App\Http\Controllers\Panel\ReceiptController::class, 'submit'])->name('sales.receipts.submit');
    Route::post('/sales/receipts/{receipt}/approve', [\App\Http\Controllers\Panel\ReceiptController::class, 'approve'])->name('sales.receipts.approve');
    Route::post('/sales/receipts/{receipt}/reject', [\App\Http\Controllers\Panel\ReceiptController::class, 'reject'])->name('sales.receipts.reject');
    Route::post('/sales/receipts/{receipt}/cancel', [\App\Http\Controllers\Panel\ReceiptController::class, 'cancel'])->name('sales.receipts.cancel');

    // Document AI Predictor
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

    // Odometer Reading Extractor
    Route::prefix('odometer')->name('odometer.')->group(function () {
        Route::get('/', [DocumentAiController::class, 'odometerPlayground'])->name('playground');
        Route::post('extract', [DocumentAiController::class, 'extractOdometer'])->name('extract');
        Route::post('confirm', [DocumentAiController::class, 'confirmOdometerReading'])->name('confirm');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile/info', [ProfileController::class, 'updateInfo'])->name('profile.update.info');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.update.password');

    // Digital Signature (authenticated)
    Route::get('/profile/signature', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'getUserSignature'])->name('profile.signature.get');
    Route::post('/profile/signature', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'uploadUserSignature'])->name('profile.signature.upload');
    Route::delete('/profile/signature', [\App\Http\Controllers\Panel\DigitalSignatureController::class, 'deleteUserSignature'])->name('profile.signature.delete');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Company Info
    Route::get('/settings/company', [CompanyController::class, 'index'])->name('settings.company');
    Route::post('/settings/company', [CompanyController::class, 'store'])->name('settings.company.store');
    Route::get('/settings/company/{company}', [CompanyController::class, 'show'])->name('settings.company.show');
    Route::put('/settings/company/{company}', [CompanyController::class, 'update'])->name('settings.company.update');
    Route::delete('/settings/company/{company}', [CompanyController::class, 'destroy'])->name('settings.company.destroy');
    Route::post('/settings/company/{company}/default', [CompanyController::class, 'setDefault'])->name('settings.company.default');

    // Financial Year
    Route::get('/settings/financial-year', [FinancialYearController::class, 'index'])->name('settings.financial-year');
    Route::post('/settings/financial-year', [FinancialYearController::class, 'store'])->name('settings.financial-year.store');
    Route::get('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'show'])->name('settings.financial-year.show');
    Route::put('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'update'])->name('settings.financial-year.update');
    Route::delete('/settings/financial-year/{financialYear}', [FinancialYearController::class, 'destroy'])->name('settings.financial-year.destroy');
    Route::post('/settings/financial-year/{financialYear}/current', [FinancialYearController::class, 'setCurrent'])->name('settings.financial-year.current');

    // Numbering
    Route::get('/settings/numbering', [NumberingController::class, 'index'])->name('settings.numbering');
    Route::post('/settings/numbering/approval', [NumberingController::class, 'saveApproval'])->name('settings.numbering.approval.save');
    Route::get('/settings/numbering/approval/get', [NumberingController::class, 'getApproval'])->name('settings.numbering.approval.get');
    Route::post('/settings/numbering/upload-signature', [NumberingController::class, 'uploadSignatureForUser'])->name('settings.numbering.upload-signature');
    Route::post('/settings/numbering/send-signature-link', [NumberingController::class, 'sendSignatureLink'])->name('settings.numbering.send-signature-link');
    Route::get('/settings/numbering/{numberingSetting}', [NumberingController::class, 'show'])->name('settings.numbering.show');
    Route::put('/settings/numbering/{numberingSetting}', [NumberingController::class, 'update'])->name('settings.numbering.update');

    // Document
    Route::get('/settings/document-types', [DocumentTypeController::class, 'index'])->name('settings.document-types');
    Route::get('/settings/document-types/data', [DocumentTypeController::class, 'data'])->name('settings.document-types.data');
    Route::post('/settings/document-types', [DocumentTypeController::class, 'store'])->name('settings.document-types.store');
    Route::get('/settings/document-types/{documentType}', [DocumentTypeController::class, 'show'])->name('settings.document-types.show');
    Route::put('/settings/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('settings.document-types.update');
    Route::delete('/settings/document-types/{documentType}', [DocumentTypeController::class, 'destroy'])->name('settings.document-types.destroy');

    // Users Management
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

    // Roles Management
    Route::get('/settings/roles', [RoleController::class, 'index'])->name('settings.roles');
    Route::get('/settings/roles/data', [RoleController::class, 'data'])->name('settings.roles.data');
    Route::post('/settings/roles', [RoleController::class, 'store'])->name('settings.roles.store');
    Route::get('/settings/roles/{role}', [RoleController::class, 'show'])->name('settings.roles.show');
    Route::put('/settings/roles/{role}', [RoleController::class, 'update'])->name('settings.roles.update');
    Route::delete('/settings/roles/{role}', [RoleController::class, 'destroy'])->name('settings.roles.destroy');
    Route::get('/settings/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('settings.roles.permissions');
    Route::put('/settings/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('settings.roles.permissions.update');

    // Permissions Management
    Route::get('/settings/permissions', [PermissionController::class, 'index'])->name('settings.permissions');
    Route::get('/settings/permissions/data', [PermissionController::class, 'data'])->name('settings.permissions.data');
    Route::post('/settings/permissions', [PermissionController::class, 'store'])->name('settings.permissions.store');

    // Permission Groups
    Route::get('/settings/permission-groups', [PermissionGroupController::class, 'index'])->name('settings.permission-groups.index');
    Route::post('/settings/permission-groups', [PermissionGroupController::class, 'store'])->name('settings.permission-groups.store');
    Route::delete('/settings/permission-groups/{permissionGroup}', [PermissionGroupController::class, 'destroy'])->name('settings.permission-groups.destroy');

    Route::get('/settings/permissions/{permission}', [PermissionController::class, 'show'])->name('settings.permissions.show');
    Route::put('/settings/permissions/{permission}', [PermissionController::class, 'update'])->name('settings.permissions.update');
    Route::delete('/settings/permissions/{permission}', [PermissionController::class, 'destroy'])->name('settings.permissions.destroy');
});
