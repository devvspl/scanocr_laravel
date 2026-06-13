<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Generated\TCA002Controller;
use App\Http\Controllers\Generated\InvoiceController;
use App\Http\Controllers\Generated\ScannerController;

/*
|--------------------------------------------------------------------------
| Generated Routes
|--------------------------------------------------------------------------
|
| This file contains auto-generated routes created by the Page Builder.
| Routes are automatically added when you generate CRUD pages.
|
*/

Route::middleware(['auth'])->prefix('generated')->name('generated.')->group(function () {
    // Generated routes will be added here automatically

    Route::get('scanners/export', [ScannerController::class, 'export'])->name('scanners.export');
    Route::get('scanners/export/{exportLog}/download', [ScannerController::class, 'exportDownload'])->name('scanners.export.download');
    Route::resource('scanners', ScannerController::class);

    Route::get('invoices/export', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('invoices/export/{exportLog}/download', [InvoiceController::class, 'exportDownload'])->name('invoices.export.download');
    Route::resource('invoices', InvoiceController::class);

    Route::get('tc-a-002s/export', [TCA002Controller::class, 'export'])->name('tc-a-002s.export');
    Route::get('tc-a-002s/export/{exportLog}/download', [TCA002Controller::class, 'exportDownload'])->name('tc-a-002s.export.download');
    Route::resource('tc-a-002s', TCA002Controller::class);

});
