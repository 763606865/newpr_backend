<?php

use App\SApi\Controllers\AnnouncementController;
use App\SApi\Controllers\CompanyController;
use App\SApi\Controllers\HomeController;
use App\SApi\Controllers\JobController;
use App\SApi\Controllers\ResumeController;
use App\SApi\Controllers\UserController;
use App\SApi\Middleware\VerifySignatureMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifySignatureMiddleware::class)->group(function (): void {
    Route::get('/ping', [HomeController::class, 'ping']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/resumes', [ResumeController::class, 'index']);
    Route::get('/companies', [CompanyController::class, 'index']);
    Route::get('/users', [UserController::class, 'index']);
});
