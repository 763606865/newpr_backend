<?php

use App\Rc\Controllers\AuthController;
use App\Rc\Controllers\HomeController;
use App\Rc\Controllers\MetaController;
use App\Rc\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

// 认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/phone-login', [AuthController::class, 'phoneLogin']);
Route::post('/auth/email-login', [AuthController::class, 'emailLogin']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware('auth:rc')->group(function (): void {
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/meta', [MetaController::class, 'index']);
    Route::get('/meta/cities', [MetaController::class, 'cities']);
    Route::get('/meta/industries', [MetaController::class, 'industries']);
    Route::get('/meta/positions', [MetaController::class, 'positions']);

    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/resumes/{id}', [ResumeController::class, 'show'])->whereNumber('id');
    Route::post('/resumes', [ResumeController::class, 'store']);
    Route::put('/resumes/{id}', [ResumeController::class, 'update'])->whereNumber('id');
});
