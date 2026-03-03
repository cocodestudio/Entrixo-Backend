<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\DailyHeadcountController;

Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password/send-otp', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'verifyOtp']);
Route::post('/forgot-password/reset', [\App\Http\Controllers\Api\ForgotPasswordController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/register-admin', [\App\Http\Controllers\Api\AuthController::class, 'registerAdmin']);
    Route::post('/revoke-admin', [\App\Http\Controllers\Api\AuthController::class, 'revokeAdmin']);
    Route::post('/change-password', [\App\Http\Controllers\Api\AuthController::class, 'changePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Sessions Management ---
    Route::get('/sessions', [SessionController::class, 'index']);
    Route::post('/sessions', [SessionController::class, 'store']);
    Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
    
    // --- Course Management ---
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    
    // --- Subject/Lab Management ---
    Route::get('/subjects', [SubjectController::class, 'index']);
    Route::post('/subjects', [SubjectController::class, 'store']); 
    Route::delete('/subjects/{id}', [SubjectController::class, 'destroy']);

    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/labs', [\App\Http\Controllers\Api\LabController::class, 'index']);
    Route::post('/labs', [\App\Http\Controllers\Api\LabController::class, 'store']);
    Route::delete('/labs/{id}', [\App\Http\Controllers\Api\LabController::class, 'destroy']);

    Route::get('/courses', [\App\Http\Controllers\Api\StudentController::class, 'getCourses']);
    Route::get('/students', [\App\Http\Controllers\Api\StudentController::class, 'getStudents']);
    Route::put('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'updateStudent']);
    Route::delete('/students/{id}', [\App\Http\Controllers\Api\StudentController::class, 'deleteStudent']);

    Route::get('/resources', [\App\Http\Controllers\Api\ResourceController::class, 'index']);
    Route::post('/resources', [\App\Http\Controllers\Api\ResourceController::class, 'store']);
    Route::delete('/resources/{id}', [\App\Http\Controllers\Api\ResourceController::class, 'destroy']);
    Route::get('/student/assignments', [\App\Http\Controllers\Api\ResourceController::class, 'getStudentAssignments']);
    Route::get('/student/resources', [\App\Http\Controllers\Api\ResourceController::class, 'getStudentResources']);
    Route::post('/update-profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::post('/attendance/mark', [\App\Http\Controllers\Api\AttendanceController::class, 'markAttendance']);
    Route::get('/active-session', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'getActiveSession']);
    Route::post('/manual-attendance/data', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'getStudentsAndAttendance']);
    Route::post('/manual-attendance/save', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'saveManualAttendance']);
    Route::get('/admin/dashboard', [\App\Http\Controllers\Api\AdminDashboardController::class, 'index']);
    Route::get('/student/dashboard', [\App\Http\Controllers\Api\StudentDashboardController::class, 'index']);
    Route::get('/student/sessions', [StudentAttendanceController::class, 'getSessions']);
    Route::get('/student/attendance', [StudentAttendanceController::class, 'getAttendance']);
    Route::get('/admin/headcount/courses', [DailyHeadcountController::class, 'getCourses']);
    Route::get('/admin/headcount/student-count', [DailyHeadcountController::class, 'getStudentCount']);
    Route::get('/admin/headcount/date/{date}', [DailyHeadcountController::class, 'getByDate']);
    Route::post('/admin/headcount/save', [DailyHeadcountController::class, 'save']);
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/clear', [\App\Http\Controllers\Api\NotificationController::class, 'clearAll']);
    Route::post('/fcm-token', [\App\Http\Controllers\Api\NotificationController::class, 'saveToken']);
    Route::get('/admin/attendance/daily-report', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'getDailyReport']);
});