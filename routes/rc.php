<?php

use App\Rc\Controllers\ApplicationController;
use App\Rc\Controllers\AuthController;
use App\Rc\Controllers\CompanyController;
use App\Rc\Controllers\Discovery\JobDetailController;
use App\Rc\Controllers\Discovery\JobRecommendController;
use App\Rc\Controllers\Discovery\JobSearchController;
use App\Rc\Controllers\Discovery\ResumeDetailController;
use App\Rc\Controllers\Discovery\ResumeRecommendController;
use App\Rc\Controllers\Discovery\ResumeSearchController;
use App\Rc\Controllers\HomeController;
use App\Rc\Controllers\JobController;
use App\Rc\Controllers\MetaController;
use App\Rc\Controllers\ResumeCertificateController;
use App\Rc\Controllers\ResumeController;
use App\Rc\Controllers\ResumeEducationController;
use App\Rc\Controllers\ResumeIntentionController;
use App\Rc\Controllers\ResumeLanguageController;
use App\Rc\Controllers\ResumePortfolioController;
use App\Rc\Controllers\ResumeProjectController;
use App\Rc\Controllers\ResumeSkillController;
use App\Rc\Controllers\ResumeTrainingController;
use App\Rc\Controllers\ResumeWorkController;
use App\Rc\Controllers\ToolController;
use App\Rc\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

// 认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/phone-login', [AuthController::class, 'phoneLogin']);
Route::post('/auth/email-login', [AuthController::class, 'emailLogin']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

// Discovery - 职位推荐 / 详情（可选登录）
Route::get('/talent/jobs/recommend', [JobRecommendController::class, 'index']);
Route::get('/talent/jobs/{id}', [JobDetailController::class, 'show'])->whereNumber('id');

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
    Route::get('/meta/majors', [MetaController::class, 'majors']);
    Route::get('/meta/companies', [MetaController::class, 'companies']);
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
    Route::get('/resumes/{id}/projects', [ResumeProjectController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/projects/{projectId}', [ResumeProjectController::class, 'show'])->whereNumber('id')->whereNumber('projectId');
    Route::get('/resumes/{id}/trainings', [ResumeTrainingController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/trainings/{trainingId}', [ResumeTrainingController::class, 'show'])->whereNumber('id')->whereNumber('trainingId');
    Route::get('/resumes/{id}/languages', [ResumeLanguageController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/languages/{languageId}', [ResumeLanguageController::class, 'show'])->whereNumber('id')->whereNumber('languageId');
    Route::get('/resumes/{id}/skills', [ResumeSkillController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/skills/{skillId}', [ResumeSkillController::class, 'show'])->whereNumber('id')->whereNumber('skillId');
    Route::get('/resumes/{id}/certificates', [ResumeCertificateController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/certificates/{certificateId}', [ResumeCertificateController::class, 'show'])->whereNumber('id')->whereNumber('certificateId');
    Route::get('/resumes/{id}/portfolios', [ResumePortfolioController::class, 'index'])->whereNumber('id');
    Route::get('/resumes/{id}/portfolios/{portfolioId}', [ResumePortfolioController::class, 'show'])->whereNumber('id')->whereNumber('portfolioId');
    Route::post('/resumes', [ResumeController::class, 'store']);
    Route::put('/resumes/{id}', [ResumeController::class, 'update'])->whereNumber('id');
    Route::post('/resumes/{id}/attachment', [ResumeController::class, 'uploadAttachment'])->whereNumber('id');
    Route::post('/resumes/{id}/intentions', [ResumeIntentionController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/works', [ResumeWorkController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/educations', [ResumeEducationController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/projects', [ResumeProjectController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/trainings', [ResumeTrainingController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/languages', [ResumeLanguageController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/skills', [ResumeSkillController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/certificates', [ResumeCertificateController::class, 'store'])->whereNumber('id');
    Route::post('/resumes/{id}/portfolios', [ResumePortfolioController::class, 'store'])->whereNumber('id');
    Route::put('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'update'])->whereNumber('id')->whereNumber('intentionId');
    Route::put('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'update'])->whereNumber('id')->whereNumber('workId');
    Route::put('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'update'])->whereNumber('id')->whereNumber('educationId');
    Route::put('/resumes/{id}/projects/{projectId}', [ResumeProjectController::class, 'update'])->whereNumber('id')->whereNumber('projectId');
    Route::put('/resumes/{id}/trainings/{trainingId}', [ResumeTrainingController::class, 'update'])->whereNumber('id')->whereNumber('trainingId');
    Route::put('/resumes/{id}/languages/{languageId}', [ResumeLanguageController::class, 'update'])->whereNumber('id')->whereNumber('languageId');
    Route::put('/resumes/{id}/skills/{skillId}', [ResumeSkillController::class, 'update'])->whereNumber('id')->whereNumber('skillId');
    Route::put('/resumes/{id}/certificates/{certificateId}', [ResumeCertificateController::class, 'update'])->whereNumber('id')->whereNumber('certificateId');
    Route::put('/resumes/{id}/portfolios/{portfolioId}', [ResumePortfolioController::class, 'update'])->whereNumber('id')->whereNumber('portfolioId');
    Route::delete('/resumes/{id}/intentions/{intentionId}', [ResumeIntentionController::class, 'destroy'])->whereNumber('id')->whereNumber('intentionId');
    Route::delete('/resumes/{id}/works/{workId}', [ResumeWorkController::class, 'destroy'])->whereNumber('id')->whereNumber('workId');
    Route::delete('/resumes/{id}/educations/{educationId}', [ResumeEducationController::class, 'destroy'])->whereNumber('id')->whereNumber('educationId');
    Route::delete('/resumes/{id}/projects/{projectId}', [ResumeProjectController::class, 'destroy'])->whereNumber('id')->whereNumber('projectId');
    Route::delete('/resumes/{id}/trainings/{trainingId}', [ResumeTrainingController::class, 'destroy'])->whereNumber('id')->whereNumber('trainingId');
    Route::delete('/resumes/{id}/languages/{languageId}', [ResumeLanguageController::class, 'destroy'])->whereNumber('id')->whereNumber('languageId');
    Route::delete('/resumes/{id}/skills/{skillId}', [ResumeSkillController::class, 'destroy'])->whereNumber('id')->whereNumber('skillId');
    Route::delete('/resumes/{id}/certificates/{certificateId}', [ResumeCertificateController::class, 'destroy'])->whereNumber('id')->whereNumber('certificateId');
    Route::delete('/resumes/{id}/portfolios/{portfolioId}', [ResumePortfolioController::class, 'destroy'])->whereNumber('id')->whereNumber('portfolioId');
    // ==============================================================================

    // Discovery - 职位搜索
    Route::get('/talent/jobs', [JobSearchController::class, 'index']);
    // ==============================================================================

    // 投递（求职者 / 招聘方）
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show'])->whereNumber('id');
    Route::post('/applications/{id}/withdraw', [ApplicationController::class, 'withdraw'])->whereNumber('id');
    // ==============================================================================

    // 招聘方-企业
    Route::get('/companies/lookup', [CompanyController::class, 'lookup']);
    Route::get('/companies/profile', [CompanyController::class, 'profileShow']);
    Route::put('/companies/profile', [CompanyController::class, 'profileUpdate']);
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

    // Discovery - 简历推荐 / 详情 / 搜索
    Route::get('/talent/resumes/recommend', [ResumeRecommendController::class, 'index']);
    Route::get('/talent/resumes/{id}', [ResumeDetailController::class, 'show'])->whereNumber('id');
    Route::get('/talent/resumes', [ResumeSearchController::class, 'index']);
    // ==============================================================================

    // 工具功能
    Route::post('/tools/ocr/business-license', [ToolController::class, 'recognizeBusinessLicense']);
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/files', [UploadController::class, 'destroy']);
    // ==============================================================================
});
