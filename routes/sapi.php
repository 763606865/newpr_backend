<?php

use App\SApi\Controllers\AnnouncementController;
use App\SApi\Controllers\HomeController;
use App\SApi\Middleware\VerifySignatureMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifySignatureMiddleware::class)->group(function (): void {
    Route::get('/ping', [HomeController::class, 'ping']);
    Route::get('/announcements', [AnnouncementController::class, 'index']);
});
