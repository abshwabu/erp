<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\HR\Controllers\EmployeeController;
use App\Modules\HR\Controllers\AttendanceController;
use App\Modules\HR\Controllers\LeaveController;
use App\Modules\HR\Controllers\DepartmentController;
use App\Modules\HR\Controllers\PositionController;
use App\Modules\HR\Controllers\EmployeeDocumentController;

Route::prefix('api/hr')->middleware('auth:api,sanctum')->group(function () {
    // Employees
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::post('employees', [EmployeeController::class, 'store']);
    Route::get('employees/{id}', [EmployeeController::class, 'show']);
    Route::patch('employees/{id}', [EmployeeController::class, 'update']);
    Route::delete('employees/{id}', [EmployeeController::class, 'destroy']);
    Route::get('employees/{id}/leave-balances', [EmployeeController::class, 'leaveBalances']);
    Route::get('employees/{id}/attendance', [EmployeeController::class, 'attendance']);

    // Employee Documents
    Route::get('employees/{id}/documents', [EmployeeDocumentController::class, 'index']);
    Route::post('employees/{id}/documents', [EmployeeDocumentController::class, 'store']);
    Route::get('employees/{id}/documents/{documentId}', [EmployeeDocumentController::class, 'show']);
    Route::get('employees/{id}/documents/{documentId}/download', [EmployeeDocumentController::class, 'download']);
    Route::delete('employees/{id}/documents/{documentId}', [EmployeeDocumentController::class, 'destroy']);

    // Departments
    Route::get('departments', [DepartmentController::class, 'index']);
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::get('departments/{id}', [DepartmentController::class, 'show']);
    Route::patch('departments/{id}', [DepartmentController::class, 'update']);
    Route::delete('departments/{id}', [DepartmentController::class, 'destroy']);

    // Positions
    Route::get('positions', [PositionController::class, 'index']);
    Route::post('positions', [PositionController::class, 'store']);
    Route::get('positions/{id}', [PositionController::class, 'show']);
    Route::patch('positions/{id}', [PositionController::class, 'update']);
    Route::delete('positions/{id}', [PositionController::class, 'destroy']);

    // Attendance
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut']);
    Route::get('attendance', [AttendanceController::class, 'index']);
    Route::get('attendance/summary', [AttendanceController::class, 'summary']);

    // Leave
    Route::get('leave/types', [LeaveController::class, 'types']);
    Route::get('leave/requests', [LeaveController::class, 'index']);
    Route::post('leave/requests', [LeaveController::class, 'store']);
    Route::patch('leave/requests/{id}/approve', [LeaveController::class, 'approve']);
    Route::patch('leave/requests/{id}/reject', [LeaveController::class, 'reject']);
    Route::patch('leave/requests/{id}/cancel', [LeaveController::class, 'cancel']);
});
