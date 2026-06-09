<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetaController;
use Illuminate\Support\Facades\Route;

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/home/announcements', [HomeController::class, 'announcement'])->name('home.announcement');
Route::get('/home/rc/positions', [HomeController::class, 'position'])->name('home.position');
Route::get('/home/rc/industries', [HomeController::class, 'industry'])->name('home.industry');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcement.index');
Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcement.show');

Route::get('/meta', [MetaController::class, 'index'])->name('meta');
