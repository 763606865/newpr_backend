<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\FriendLinkController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SchoolActivityController;
use App\Http\Controllers\SiteConfigController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MenuController;

Route::middleware(['optional-rc-auth', 'cms-home-menu'])->group(function (): void {
    // 首页-展示页
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/announcements', [HomeController::class, 'announcement'])->name('home.announcement');
    Route::get('/home/schools', [HomeController::class, 'school'])->name('home.school');
    Route::get('/home/rc/positions', [HomeController::class, 'position'])->name('home.position');
    Route::get('/home/rc/industries', [HomeController::class, 'industry'])->name('home.industry');
    // ===============================================================================

    // 公告页
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcement.index');
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcement.show');
    // ===============================================================================

    // 文章内容
    Route::get('/articles', [ArticleController::class, 'index'])->name('article.index');
    Route::get('/articles/{id}', [ArticleController::class, 'show'])->whereNumber('id')->name('article.show');
    // ===============================================================================

    // 校园活动
    Route::get('/school-activities', [SchoolActivityController::class, 'index'])->name('school-activity.index');
    Route::get('/school-activities/invite/{inviteCode}', [SchoolActivityController::class, 'showByInviteCode'])->name('school-activity.invite.show');
    Route::post('/school-activities/invite/{inviteCode}/companies', [SchoolActivityController::class, 'registerCompanyByInviteCode'])->name('school-activity.invite.register-company');
    Route::post('/school-activities/invite/{inviteCode}/schools', [SchoolActivityController::class, 'registerSchoolByInviteCode'])->name('school-activity.invite.register-school');
    Route::get('/school-activities/{id}', [SchoolActivityController::class, 'show'])->whereNumber('id')->name('school-activity.show');
    Route::get('/school-activities/{id}/companies', [SchoolActivityController::class, 'getCompanies'])->whereNumber('id')->name('school-activity.list-companies');
    // ===============================================================================
});

// 元数据
Route::get('/meta', [MetaController::class, 'index'])->name('meta');
Route::get('/meta/majors', [MetaController::class, 'majors'])->name('meta.majors');
Route::get('/meta/tags', [MetaController::class, 'tags'])->name('meta.tags');
Route::get('/meta/articles', [MetaController::class, 'articles'])->name('meta.articles');
// ===============================================================================

// 首页推荐位
Route::get('/home/recommendations', [RecommendationController::class, 'index'])->name('home.recommendations');
Route::get('/home/banners', [BannerController::class, 'index'])->name('home.banners');
// ===============================================================================

// 广告
Route::get('/ads', [AdController::class, 'index'])->name('ad.index');
// ===============================================================================

// 菜单
Route::get('/menus', [MenuController::class, 'index']);
// ===============================================================================

// 站点配置
Route::get('/site-configs', [SiteConfigController::class, 'index']);
// ===============================================================================

// 友情链接
Route::get('/friend-links', [FriendLinkController::class, 'index']);
// ===============================================================================
