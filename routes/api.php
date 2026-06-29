<?php

use App\Http\Controllers\Api\BillApprovalApiController;
use Illuminate\Support\Facades\Route;

// ── Public (no auth) ──────────────────────────────────────────────────────
Route::post('/login', [BillApprovalApiController::class, 'login']);

// ── Protected (JWT auth) ──────────────────────────────────────────────────
Route::middleware('api.jwt')->group(function () {

    // Profile & FCM
    Route::get('/profile', [BillApprovalApiController::class, 'profile']);
    Route::post('/fcm-token', [BillApprovalApiController::class, 'updateFcmToken']);

    // Filter Data (Select options)
    Route::get('/filters/companies', [BillApprovalApiController::class, 'filterCompanies']);
    Route::get('/filters/locations', [BillApprovalApiController::class, 'filterLocations']);
    Route::get('/filters/financial-years', [BillApprovalApiController::class, 'filterFinancialYears']);
    Route::get('/filters/users', [BillApprovalApiController::class, 'filterUsers']);

    // Bill Approval
    Route::get('/bills/tab-counts', [BillApprovalApiController::class, 'tabCounts']);
    Route::get('/bills', [BillApprovalApiController::class, 'list']);
    Route::get('/bills/{scanId}', [BillApprovalApiController::class, 'detail']);
    Route::post('/bills/{scanId}/approve', [BillApprovalApiController::class, 'approve']);
    Route::post('/bills/{scanId}/reject', [BillApprovalApiController::class, 'reject']);
});
