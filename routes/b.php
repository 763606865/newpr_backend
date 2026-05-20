<?php

use App\B\Controllers\AttendanceRuleController;
use App\B\Controllers\AttendanceScheduleController;
use App\B\Controllers\AuthController;
use App\B\Controllers\CompanyController;
use App\B\Controllers\DepartmentController;
use App\B\Controllers\EmployeeController;
use App\B\Controllers\LeaveTypeController;
use App\B\Controllers\PositionController;
use Illuminate\Support\Facades\Route;

// B端认证相关路由
Route::post('/auth/send-verification-code', [AuthController::class, 'sendVerificationCode']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:b')->group(function (): void {
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    // ==============================================================================

    // 申请入驻/企业管理
    Route::resource('companies', CompanyController::class);
    // ==============================================================================

    // 部门管理
    Route::resource('departments', DepartmentController::class);
    // ==============================================================================

    // 岗位管理
    Route::resource('positions', PositionController::class);
    // ==============================================================================

    // 考勤规则
    Route::resource('attendance-rules', AttendanceRuleController::class);
    // ==============================================================================

    // 考勤记录
    Route::get('/attendance-schedules', [AttendanceScheduleController::class, 'index']);
    Route::get('/attendance-schedules/export', [AttendanceScheduleController::class, 'export']);
    Route::get('/attendance-schedules/{id}', [AttendanceScheduleController::class, 'show']);
    // ==============================================================================

    // 假期类型
    Route::resource('leave-types', LeaveTypeController::class);
    // ==============================================================================

    // 职工管理
    // 职工管理-列表
    Route::get('/employees', [EmployeeController::class, 'index']);
    // 职工管理-创建
    Route::get('/employees/create', [EmployeeController::class, 'create']);
    // 职工管理-用户远程搜索（避免全量下拉）
    Route::get('/employees/search-users', [EmployeeController::class, 'searchUsers']);
    // 职工管理-保存
    Route::post('/employees', [EmployeeController::class, 'store']);
    // 职工管理-详情
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    // 职工管理-编辑页
    Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit']);
    // 职工管理-编辑
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);
    // 职工管理-删除
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);
    // ==============================================================================
});
// ==============================================================================
