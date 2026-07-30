<?php

use App\Rc\Controllers\AiResumeController;
use App\Rc\Controllers\ApplicationController;
use App\Rc\Controllers\AuthController;
use App\Rc\Controllers\CompanyAlbumController;
use App\Rc\Controllers\CompanyApplicationController;
use App\Rc\Controllers\CompanyController;
use App\Rc\Controllers\CompanyInterviewController;
use App\Rc\Controllers\CompanySchoolActivityController;
use App\Rc\Controllers\Discovery\AnnouncementRecommendController;
use App\Rc\Controllers\Discovery\AnnouncementSearchController;
use App\Rc\Controllers\Discovery\CompanyDetailController;
use App\Rc\Controllers\Discovery\CompanyFavoriteController;
use App\Rc\Controllers\Discovery\CompanyRecommendController;
use App\Rc\Controllers\Discovery\CompanySearchController;
use App\Rc\Controllers\Discovery\JobDetailController;
use App\Rc\Controllers\Discovery\JobFavoriteController;
use App\Rc\Controllers\Discovery\JobRecommendController;
use App\Rc\Controllers\Discovery\JobSearchController;
use App\Rc\Controllers\Discovery\ResumeDetailController;
use App\Rc\Controllers\Discovery\ResumeFavoriteController;
use App\Rc\Controllers\Discovery\ResumeRecommendController;
use App\Rc\Controllers\Discovery\ResumeSearchController;
use App\Rc\Controllers\HomeController;
use App\Rc\Controllers\ImController;
use App\Rc\Controllers\ImConversationController;
use App\Rc\Controllers\ImInteractionRequestController;
use App\Rc\Controllers\ImQuickPhraseController;
use App\Rc\Controllers\JobController;
use App\Rc\Controllers\MetaController;
use App\Rc\Controllers\NotificationController;
use App\Rc\Controllers\OrderController;
use App\Rc\Controllers\ReportController;
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
use App\Rc\Controllers\SchoolActivityCompanyController;
use App\Rc\Controllers\SchoolActivityController;
use App\Rc\Controllers\SchoolActivityJobController;
use App\Rc\Controllers\SchoolArticleController;
use App\Rc\Controllers\SchoolBoothAreaController;
use App\Rc\Controllers\SchoolBoothController;
use App\Rc\Controllers\SchoolController;
use App\Rc\Controllers\ToolController;
use App\Rc\Controllers\UploadController;
use App\Rc\Controllers\UserCompanyBlacklistController;
use App\Rc\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

// 认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/phone-login', [AuthController::class, 'phoneLogin']);
Route::post('/auth/email-login', [AuthController::class, 'emailLogin']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/wechat-mini-login', [AuthController::class, 'wechatMiniLogin']);
Route::post('/auth/wechat-app-login', [AuthController::class, 'wechatAppLogin']);
Route::post('/auth/wechat-bind-phone', [AuthController::class, 'wechatBindPhone']);
Route::post('/upload/avatar', [UploadController::class, 'avatar']);

// 微信、支付宝异步支付通知（通过平台签名鉴权）
Route::post('/payments/notify/{channel}', [OrderController::class, 'notify'])
    ->whereIn('channel', ['wechat', 'alipay']);

// Discovery - 职位推荐 / 详情（可选登录）
Route::get('/talent/jobs/recommend', [JobRecommendController::class, 'index']);
Route::get('/talent/companies/recommend', [CompanyRecommendController::class, 'index']);
Route::get('/talent/announcements/recommend', [AnnouncementRecommendController::class, 'index']);
Route::get('/talent/jobs/{id}', [JobDetailController::class, 'show'])->whereNumber('id');
Route::get('/talent/companies/{id}', [CompanyDetailController::class, 'show'])->whereNumber('id');

Route::middleware('auth:rc')->group(function (): void {
    // 认证相关
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::get('/auth/organizations', [AuthController::class, 'organizations']);
    // ==============================================================================

    // 用户相关
    Route::get('/users/jobseeker/stats', [UserController::class, 'jobSeekerStats']);
    Route::get('/users/recruiter/stats', [UserController::class, 'recruiterStats']);
    Route::get('/users/campus/stats', [UserController::class, 'campusStats']);
    Route::get('/users/phone/lookup', [UserController::class, 'lookupPhone']);
    Route::post('/users/phone/verification-code', [UserController::class, 'sendPhoneVerificationCode']);
    Route::put('/users/phone', [UserController::class, 'updatePhone']);
    Route::get('/users/company-blacklists', [UserCompanyBlacklistController::class, 'index']);
    Route::post('/users/company-blacklists', [UserCompanyBlacklistController::class, 'store']);
    Route::get('/users/company-blacklists/{id}', [UserCompanyBlacklistController::class, 'show'])->whereNumber('id');
    Route::put('/users/company-blacklists/{id}', [UserCompanyBlacklistController::class, 'update'])->whereNumber('id');
    Route::patch('/users/company-blacklists/{id}', [UserCompanyBlacklistController::class, 'update'])->whereNumber('id');
    Route::delete('/users/company-blacklists/{id}', [UserCompanyBlacklistController::class, 'destroy'])->whereNumber('id');
    // ==============================================================================

    // RC 商品订单与支付
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::post('/orders/{id}/pay', [OrderController::class, 'pay'])->whereNumber('id');
    // ==============================================================================

    // 举报
    Route::post('/reports', [ReportController::class, 'store']);
    // ==============================================================================

    // 元数据
    Route::get('/meta', [MetaController::class, 'index']);
    Route::get('/meta/areas', [MetaController::class, 'areas']);
    Route::get('/meta/industries', [MetaController::class, 'industries']);
    Route::get('/meta/positions', [MetaController::class, 'positions']);
    Route::get('/meta/majors', [MetaController::class, 'majors']);
    Route::get('/meta/companies', [MetaController::class, 'companies']);
    Route::get('/meta/schools', [MetaController::class, 'schools']);
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
    Route::post('/resumes/{id}/refresh', [ResumeController::class, 'refresh'])->whereNumber('id');
    Route::post('/resumes/{id}/attachment', [ResumeController::class, 'uploadAttachment'])->whereNumber('id');
    Route::patch('/resume/{id}/avatar/upload', [ResumeController::class, 'uploadAvatar'])->whereNumber('id');
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
    Route::get('/talent/companies', [CompanySearchController::class, 'index']);
    Route::get('/talent/announcements', [AnnouncementSearchController::class, 'index']);
    Route::get('/talent/favorites/jobs', [JobFavoriteController::class, 'index']);
    Route::get('/talent/favorites/companies', [CompanyFavoriteController::class, 'index']);
    Route::post('/talent/companies/{id}/favorite', [CompanyFavoriteController::class, 'store'])->whereNumber('id');
    Route::delete('/talent/companies/{id}/favorite', [CompanyFavoriteController::class, 'destroy'])->whereNumber('id');
    Route::get('/talent/companies/{id}/jobs', [CompanyDetailController::class, 'jobs'])->whereNumber('id');
    Route::post('/talent/jobs/{id}/favorite', [JobFavoriteController::class, 'store'])->whereNumber('id');
    Route::delete('/talent/jobs/{id}/favorite', [JobFavoriteController::class, 'destroy'])->whereNumber('id');
    // ==============================================================================

    // 投递（求职者）
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/applications', [ApplicationController::class, 'store']);
    Route::get('/applications/check', [ApplicationController::class, 'checkByJobAndUser']);
    Route::get('/applications/{id}', [ApplicationController::class, 'show'])->whereNumber('id');
    Route::post('/applications/{id}/withdraw', [ApplicationController::class, 'withdraw'])->whereNumber('id');
    Route::post('/applications/{id}/accept-interview', [ApplicationController::class, 'acceptInterview'])->whereNumber('id');
    Route::post('/applications/{id}/reject-interview', [ApplicationController::class, 'rejectInterview'])->whereNumber('id');
    Route::post('/applications/{id}/accept-offer', [ApplicationController::class, 'acceptOffer'])->whereNumber('id');
    Route::post('/applications/{id}/reject-offer', [ApplicationController::class, 'rejectOffer'])->whereNumber('id');
    // ==============================================================================

    // 站内通知
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->whereNumber('id');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->whereNumber('id');
    // ==============================================================================

    // 招聘方-企业
    Route::get('/companies/lookup', [CompanyController::class, 'lookup']);
    Route::get('/companies/profile', [CompanyController::class, 'profileShow']);
    Route::put('/companies/profile', [CompanyController::class, 'profileUpdate']);
    Route::post('/companies/bind', [CompanyController::class, 'bind']);
    Route::post('/companies', [CompanyController::class, 'store']);
    Route::get('/companies/albums', [CompanyAlbumController::class, 'index']);
    Route::post('/companies/albums', [CompanyAlbumController::class, 'store']);
    Route::get('/companies/albums/{id}', [CompanyAlbumController::class, 'show'])->whereNumber('id');
    Route::put('/companies/albums/{id}', [CompanyAlbumController::class, 'update'])->whereNumber('id');
    Route::patch('/companies/albums/{id}', [CompanyAlbumController::class, 'update'])->whereNumber('id');
    Route::delete('/companies/albums/{id}', [CompanyAlbumController::class, 'destroy'])->whereNumber('id');
    Route::get('/companies/school-activities', [CompanySchoolActivityController::class, 'index']);
    Route::get('/companies/school-activities/organized', [CompanySchoolActivityController::class, 'organized']);
    Route::get('/companies/school-activities/available', [CompanySchoolActivityController::class, 'available']);
    Route::post('/companies/school-activities', [CompanySchoolActivityController::class, 'store']);
    Route::get('/companies/school-activities/{id}', [CompanySchoolActivityController::class, 'show'])->whereNumber('id');
    Route::put('/companies/school-activities/{id}', [CompanySchoolActivityController::class, 'update'])->whereNumber('id');
    Route::delete('/companies/school-activities/{id}', [CompanySchoolActivityController::class, 'destroy'])->whereNumber('id');
    Route::post('/companies/school-activities/{id}/publish', [CompanySchoolActivityController::class, 'publish'])->whereNumber('id');
    Route::post('/companies/school-activities/{id}/end', [CompanySchoolActivityController::class, 'end'])->whereNumber('id');
    Route::post('/companies/school-activities/{activityId}/apply', [CompanySchoolActivityController::class, 'apply'])->whereNumber('activityId');
    Route::get('/companies/school-activities/{activityId}/my-application', [CompanySchoolActivityController::class, 'myApplication'])->whereNumber('activityId');
    Route::get('/companies/school-activities/{activityId}/jobs', [CompanySchoolActivityController::class, 'myJobs'])->whereNumber('activityId');
    Route::post('/companies/school-activities/{activityId}/jobs', [CompanySchoolActivityController::class, 'storeJobs'])->whereNumber('activityId');
    Route::get('/companies/applications', [CompanyApplicationController::class, 'index']);
    Route::get('/companies/applications/check', [CompanyApplicationController::class, 'checkByJobAndUser']);
    Route::get('/companies/applications/{id}', [CompanyApplicationController::class, 'show'])->whereNumber('id');
    Route::post('/companies/applications/{id}/invite-interview', [CompanyApplicationController::class, 'inviteInterview'])->whereNumber('id');
    Route::post('/companies/applications/{id}/send-offer', [CompanyApplicationController::class, 'sendOffer'])->whereNumber('id');
    Route::post('/companies/applications/{id}/hire', [CompanyApplicationController::class, 'hire'])->whereNumber('id');
    Route::post('/companies/applications/{id}/reject', [CompanyApplicationController::class, 'reject'])->whereNumber('id');
    Route::get('/companies/interviews', [CompanyInterviewController::class, 'index']);
    // ==============================================================================

    // 校招负责人-学校
    Route::get('/schools/profile', [SchoolController::class, 'profileShow']);
    Route::put('/schools/profile', [SchoolController::class, 'profileUpdate']);
    Route::post('/schools/bind', [SchoolController::class, 'bind']);
    Route::get('/schools/booths', [SchoolBoothController::class, 'index']);
    Route::post('/schools/booths', [SchoolBoothController::class, 'store']);
    Route::get('/schools/booths/{id}', [SchoolBoothController::class, 'show'])->whereNumber('id');
    Route::put('/schools/booths/{id}', [SchoolBoothController::class, 'update'])->whereNumber('id');
    Route::delete('/schools/booths/{id}', [SchoolBoothController::class, 'destroy'])->whereNumber('id');
    Route::get('/schools/booths/{boothId}/areas', [SchoolBoothAreaController::class, 'index'])->whereNumber('boothId');
    Route::post('/schools/booths/{boothId}/areas', [SchoolBoothAreaController::class, 'store'])->whereNumber('boothId');
    Route::get('/schools/booths/{boothId}/areas/{id}', [SchoolBoothAreaController::class, 'show'])->whereNumber('boothId')->whereNumber('id');
    Route::put('/schools/booths/{boothId}/areas/{id}', [SchoolBoothAreaController::class, 'update'])->whereNumber('boothId')->whereNumber('id');
    Route::delete('/schools/booths/{boothId}/areas/{id}', [SchoolBoothAreaController::class, 'destroy'])->whereNumber('boothId')->whereNumber('id');
    Route::get('/schools/activities', [SchoolActivityController::class, 'index']);
    Route::get('/schools/activities/participated', [SchoolActivityController::class, 'participated']);
    Route::post('/schools/activities', [SchoolActivityController::class, 'store']);
    Route::get('/schools/activities/{id}', [SchoolActivityController::class, 'show'])->whereNumber('id');
    Route::put('/schools/activities/{id}', [SchoolActivityController::class, 'update'])->whereNumber('id');
    Route::delete('/schools/activities/{id}', [SchoolActivityController::class, 'destroy'])->whereNumber('id');
    Route::post('/schools/activities/{id}/publish', [SchoolActivityController::class, 'publish'])->whereNumber('id');
    Route::post('/schools/activities/{id}/end', [SchoolActivityController::class, 'end'])->whereNumber('id');
    Route::get('/schools/activities/{activityId}/company-applications', [SchoolActivityCompanyController::class, 'index'])->whereNumber('activityId');
    Route::post('/schools/activities/{activityId}/company-invitations', [SchoolActivityCompanyController::class, 'invite'])->whereNumber('activityId');
    Route::post('/schools/activities/{activityId}/company-applications/{id}/approve', [SchoolActivityCompanyController::class, 'approve'])->whereNumber('activityId')->whereNumber('id');
    Route::post('/schools/activities/{activityId}/company-applications/{id}/reject', [SchoolActivityCompanyController::class, 'reject'])->whereNumber('activityId')->whereNumber('id');
    Route::get('/schools/activities/{activityId}/job-applications', [SchoolActivityJobController::class, 'index'])->whereNumber('activityId');
    Route::post('/schools/activities/{activityId}/job-applications/{id}/approve', [SchoolActivityJobController::class, 'approve'])->whereNumber('activityId')->whereNumber('id');
    Route::post('/schools/activities/{activityId}/job-applications/{id}/reject', [SchoolActivityJobController::class, 'reject'])->whereNumber('activityId')->whereNumber('id');
    Route::get('/schools/articles', [SchoolArticleController::class, 'index']);
    Route::post('/schools/articles', [SchoolArticleController::class, 'store']);
    Route::get('/schools/articles/{id}', [SchoolArticleController::class, 'show'])->whereNumber('id');
    Route::put('/schools/articles/{id}', [SchoolArticleController::class, 'update'])->whereNumber('id');
    Route::delete('/schools/articles/{id}', [SchoolArticleController::class, 'destroy'])->whereNumber('id');
    Route::post('/schools/articles/{id}/publish', [SchoolArticleController::class, 'publish'])->whereNumber('id');
    Route::post('/schools/articles/{id}/offline', [SchoolArticleController::class, 'offline'])->whereNumber('id');
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
    Route::get('/talent/favorites/resumes', [ResumeFavoriteController::class, 'index']);
    Route::post('/talent/resumes/{id}/favorite', [ResumeFavoriteController::class, 'store'])->whereNumber('id');
    Route::delete('/talent/resumes/{id}/favorite', [ResumeFavoriteController::class, 'destroy'])->whereNumber('id');
    // ==============================================================================

    // 工具功能
    Route::post('/tools/ocr/business-license', [ToolController::class, 'recognizeBusinessLicense']);
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::delete('/files', [UploadController::class, 'destroy']);
    // ==============================================================================

    // AI-工具
    Route::post('/ai/resume-parses', [AiResumeController::class, 'store']);
    Route::get('/ai/resume-parses/{id}', [AiResumeController::class, 'show'])->whereNumber('id');
    // ==============================================================================

    // IM相关
    Route::get('/im/refresh-token', [ImController::class, 'refreshToken']);
    // IM相关-会话
    Route::get('/im/conversations', [ImConversationController::class, 'index']);
    Route::post('/im/conversations', [ImConversationController::class, 'store']);
    Route::get('/im/conversations/{id}/messages', [ImConversationController::class, 'getMessages'])->whereNumber('id');
    Route::post('/im/conversations/{id}/card-messages', [ImConversationController::class, 'sendCardMessage'])->whereNumber('id');
    Route::post('/im/interaction-requests', [ImInteractionRequestController::class, 'store']);
    Route::post('/im/interaction-requests/{id}/respond', [ImInteractionRequestController::class, 'respond'])->whereNumber('id');
    // IM相关-快捷短语
    Route::get('/im/quick-phrases', [ImQuickPhraseController::class, 'index']);
    Route::post('/im/quick-phrases', [ImQuickPhraseController::class, 'store']);
    Route::get('/im/quick-phrases/{id}', [ImQuickPhraseController::class, 'show'])->whereNumber('id');
    Route::put('/im/quick-phrases/{id}', [ImQuickPhraseController::class, 'update'])->whereNumber('id');
    Route::patch('/im/quick-phrases/{id}', [ImQuickPhraseController::class, 'update'])->whereNumber('id');
    Route::delete('/im/quick-phrases/{id}', [ImQuickPhraseController::class, 'destroy'])->whereNumber('id');
    // ==============================================================================
});
