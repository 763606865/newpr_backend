<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/home/announcements', [HomeController::class, 'announcement'])->name('home.announcement');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcement.index');
Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcement.show');
