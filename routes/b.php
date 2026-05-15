<?php

use App\B\Controllers\AuthController;
use App\B\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

// B端认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:b')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    // ==============================================================================

    // 申请入驻
    Route::resource('companies', CompanyController::class);
    // ==============================================================================
});
// ==============================================================================
