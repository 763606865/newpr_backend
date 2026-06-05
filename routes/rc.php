<?php

use App\Rc\Controllers\AuthController;
use App\Rc\Controllers\CompanyController;
use App\Rc\Controllers\Discovery\JobRecommendController;
use App\Rc\Controllers\Discovery\JobSearchController;
use App\Rc\Controllers\Discovery\ResumeRecommendController;
use App\Rc\Controllers\Discovery\ResumeSearchController;
use App\Rc\Controllers\HomeController;
use App\Rc\Controllers\JobController;
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

// Discovery - 职位推荐（可选登录）
Route::get('/talent/jobs/recommend', [JobRecommendController::class, 'index']);

Route::middleware('auth:rc')->group(function (): void {
    // 认证相关
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/organizations', [AuthController::class, 'organizations']);
    // ==============================================================================

    // 元数据
    Route::get('/meta', [MetaController::class, 'index']);
    Route::get('/meta/areas', [MetaController::class, 'areas']);
    Route::get('/meta/industries', [MetaController::class, 'industries']);
    Route::get('/meta/positions', [MetaController::class, 'positions']);
    // ==============================================================================

    // 求职者-简历
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
    Route::post('/resumes/{id}/attachment', [ResumeController::class, 'uploadAttachment'])->whereNumber('id');
    Route::post('/resumes/{id}/intentions', [ResumeIntentionController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/works', [ResumeWorkController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/educations', [ResumeEducationController::class, 'store'])->whereNumber('id');
    Route::put('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'update'])->whereNumber('id')->whereNumber('intentionId');
    Route::put('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'update'])->whereNumber('id')->whereNumber('workId');
    Route::put('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'update'])->whereNumber('id')->whereNumber('educationId');
    Route::delete('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'destroy'])->whereNumber('id')->whereNumber('intentionId');
    Route::delete('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'destroy'])->whereNumber('id')->whereNumber('workId');
    Route::delete('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'destroy'])->whereNumber('id')->whereNumber('educationId');
    // ==============================================================================

    // Discovery - 职位搜索
    Route::get('/talent/jobs', [JobSearchController::class, 'index']);
    // ==============================================================================

    // 招聘方-企业
    Route::get('/companies/lookup', [CompanyController::class, 'lookup']);
    Route::post('/companies/bind', [CompanyController::class, 'bind']);
    Route::post('/companies', [CompanyController::class, 'store']);
    // ==============================================================================

    // 招聘方-职位
    Route::get('/jobs', [JobController::class, 'index']);
    Route::post('/jobs', [JobController::class, 'store']);
    Route::get('/jobs/{id}', [JobController::class, 'show'])->whereNumber('id');
    Route::put('/jobs/{id}', [JobController::class, 'update'])->whereNumber('id');
    Route::delete('/jobs/{id}', [JobController::class, 'destroy'])->whereNumber('id');
    Route::post('/jobs/{id}/publish', [JobController::class, 'publish'])->whereNumber('id');
    Route::post('/jobs/{id}/pause', [JobController::class, 'pause'])->whereNumber('id');
    Route::post('/jobs/{id}/close', [JobController::class, 'close'])->whereNumber('id');
    // ==============================================================================

    // Discovery - 简历推荐 / 搜索
    Route::get('/talent/resumes/recommend', [ResumeRecommendController::class, 'index']);
    Route::get('/talent/resumes', [ResumeSearchController::class, 'index']);
    // ==============================================================================

    // 工具功能
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/files', [UploadController::class, 'destroy']);
    // ==============================================================================
});
