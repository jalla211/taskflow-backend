<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ---------- PUBLIC ROUTES ----------
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// ---------- PROTECTED ROUTES (auth:sanctum) ----------
Route::middleware('auth:sanctum')->group(function () {

    // === AUTH ===
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // === PROFILE ===
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile/password', [UserController::class, 'updatePassword']);
    Route::post('/profile/picture', [UserController::class, 'uploadProfilePicture']);

    // === USER MANAGEMENT (Admin only) ===
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::put('/users/{id}/deactivate', [UserController::class, 'deactivate']);
    Route::put('/users/{id}/reactivate', [UserController::class, 'reactivate']);
    Route::get('/roles', [UserController::class, 'roles']);

    // === PROJECTS ===
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/members', [ProjectController::class, 'addMember']);
    Route::delete('/projects/{id}/members/{user_id}', [ProjectController::class, 'removeMember']);
    Route::put('/projects/{id}/archive', [ProjectController::class, 'archive']);
    Route::put('/projects/{id}/close', [ProjectController::class, 'close']);

    // === TASKS ===
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::put('/tasks/{id}/assign', [TaskController::class, 'assign']);
    Route::put('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
    Route::get('/tasks/{id}/subtasks', [TaskController::class, 'getSubtasks']);
    Route::post('/tasks/{id}/subtasks', [TaskController::class, 'createSubtask']);
    Route::get('/tasks/{id}/dependencies', [TaskController::class, 'getDependencies']);
    Route::post('/tasks/{id}/dependencies', [TaskController::class, 'addDependency']);
    Route::delete('/tasks/{id}/dependencies/{dependencyId}', [TaskController::class, 'removeDependency']);

    // === COMMENTS ===
    Route::get('/tasks/{taskId}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{taskId}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{id}', [CommentController::class, 'update']);
    Route::delete('/comments/{id}', [CommentController::class, 'destroy']);

    // === ATTACHMENTS ===
    Route::post('/tasks/{taskId}/attachments', [AttachmentController::class, 'store']);
    Route::get('/attachments/{id}', [AttachmentController::class, 'download']);
    Route::delete('/attachments/{id}', [AttachmentController::class, 'destroy']);

    // === DASHBOARD ===
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // === REPORTS (Admin & PM) ===
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/export', [ReportController::class, 'export']);

    // === ADMIN SETTINGS (Admin only) ===
    // Task Statuses
    Route::get('/admin/statuses', [AdminController::class, 'statuses']);
    Route::post('/admin/statuses', [AdminController::class, 'storeStatus']);
    Route::put('/admin/statuses/{id}', [AdminController::class, 'updateStatus']);
    Route::delete('/admin/statuses/{id}', [AdminController::class, 'deleteStatus']);

    // Priorities
    Route::get('/admin/priorities', [AdminController::class, 'priorities']);
    Route::post('/admin/priorities', [AdminController::class, 'storePriority']);
    Route::delete('/admin/priorities/{id}', [AdminController::class, 'deletePriority']);
    Route::put('/admin/priorities/{id}', [AdminController::class, 'updatePriority']);
    // Tags
    Route::get('/admin/tags', [AdminController::class, 'tags']);
    Route::post('/admin/tags', [AdminController::class, 'storeTag']);
    Route::delete('/admin/tags/{id}', [AdminController::class, 'deleteTag']);
    Route::put('/admin/tags/{id}', [AdminController::class, 'updateTag']);  

    // Audit Logs
    Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs']);
// === NOTIFICATIONS ===
Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
Route::put('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
Route::put('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
Route::delete('/notifications/{id}', [App\Http\Controllers\Api\NotificationController::class, 'destroy']);

// === NOTIFICATION PREFERENCES ===
Route::get('/notification-preferences', [App\Http\Controllers\Api\NotificationController::class, 'getPreferences']);
Route::put('/notification-preferences', [App\Http\Controllers\Api\NotificationController::class, 'updatePreferences']);
});