<?php

use App\B\Controllers\AuthController;
use App\B\Controllers\CompanyController;
use App\B\Controllers\DepartmentController;
use App\B\Controllers\PositionController;
use Illuminate\Support\Facades\Route;

// B端认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:b')->group(function (): void {
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    // ==============================================================================

    // 申请入驻/企业管理
    Route::resource('companies', CompanyController::class);
    // ==============================================================================

    // 部门管理
    Route::resource('departments', DepartmentController::class);
    // ==============================================================================

    // 岗位管理
    Route::resource('positions', PositionController::class);
    // ==============================================================================
});
// ==============================================================================
