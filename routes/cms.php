<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\SchoolActivityController;
use Illuminate\Support\Facades\Route;

Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/home/announcements', [HomeController::class, 'announcement'])->name('home.announcement');
Route::get('/home/schools', [HomeController::class, 'school'])->name('home.school');
Route::get('/home/rc/positions', [HomeController::class, 'position'])->name('home.position');
Route::get('/home/rc/industries', [HomeController::class, 'industry'])->name('home.industry');

Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcement.index');
Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcement.show');

Route::get('/school-activities', [SchoolActivityController::class, 'index'])->name('school-activity.index');
Route::get('/school-activities/{id}', [SchoolActivityController::class, 'show'])->whereNumber('id')->name('school-activity.show');

Route::get('/meta', [MetaController::class, 'index'])->name('meta');
Route::get('/meta/majors', [MetaController::class, 'majors'])->name('meta.majors');
Route::get('/meta/tags', [MetaController::class, 'tags'])->name('meta.tags');
