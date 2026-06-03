<?php

use App\Rc\Controllers\AuthController;
use App\Rc\Controllers\HomeController;
use App\Rc\Controllers\MetaController;
use App\Rc\Controllers\ResumeController;
use App\Rc\Controllers\ResumeEducationController;
use App\Rc\Controllers\ResumeIntentionController;
use App\Rc\Controllers\ResumeWorkController;
use App\Rc\Controllers\UploadController;
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
    Route::get('/meta/areas', [MetaController::class, 'areas']);
    Route::get('/meta/industries', [MetaController::class, 'industries']);
    Route::get('/meta/positions', [MetaController::class, 'positions']);

    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/resumes/{id}', [ResumeController::class, 'show'])->whereNumber('id');
    Route::get('/resumes/{id}/intentions', [ResumeIntentionController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'show'])->whereNumber('id')->whereNumber('intentionId');
    Route::get('/resumes/{id}/works', [ResumeWorkController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'show'])->whereNumber('id')->whereNumber('workId');
    Route::get('/resumes/{id}/educations', [ResumeEducationController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'show'])->whereNumber('id')->whereNumber('educationId');
    Route::post('/resumes', [ResumeController::class, 'store']);
    Route::put('/resumes/{id}', [ResumeController::class, 'update'])->whereNumber('id');
    Route::post('/resumes/{id}/intentions', [ResumeIntentionController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/works', [ResumeWorkController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/educations', [ResumeEducationController::class, 'store'])->whereNumber('id');
    Route::put('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'update'])->whereNumber('id')->whereNumber('intentionId');
    Route::put('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'update'])->whereNumber('id')->whereNumber('workId');
    Route::put('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'update'])->whereNumber('id')->whereNumber('educationId');
    Route::delete('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'destroy'])->whereNumber('id')->whereNumber('intentionId');
    Route::delete('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'destroy'])->whereNumber('id')->whereNumber('workId');
    Route::delete('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'destroy'])->whereNumber('id')->whereNumber('educationId');

    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/files', [UploadController::class, 'destroy']);
});
