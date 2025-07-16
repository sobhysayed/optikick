<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Users\PlayerController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Users\CoachController;
use App\Http\Controllers\API\Users\DoctorController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\AdminController;

// Auth Routes with specific rate limiting
Route::middleware(['throttle:6,1'])->group(function () { // 6 attempts per minute
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-reset-token', [AuthController::class, 'verifyResetToken']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Player Routes
Route::middleware(['auth:sanctum', 'role:player'])->prefix('player')->group(function () {
    Route::get('dashboard', [PlayerController::class, 'getDashboard']);
    Route::get('profile', [PlayerController::class, 'getProfile']);
    Route::get('metrics', [PlayerController::class, 'getMetrics']);
    Route::get('metrics/details/{metricType}', [PlayerController::class, 'getMetricDetail']);
    Route::post('assessments/request', [PlayerController::class, 'requestAssessment']);
    Route::get('training-program/current', [PlayerController::class, 'getTrainingProgram']);
});

Route::middleware(['auth:sanctum', 'role:coach'])->prefix('coach')->group(function () {
    Route::get('dashboard', [CoachController::class, 'getDashboard']);
    Route::get('profile', [CoachController::class, 'getProfile']);
    Route::get('list-all-players', [CoachController::class, 'listAllPlayers']);
    Route::get('players/{player}/program', [CoachController::class, 'getPlayerProgram']);
    Route::get('players/{player}/metrics', [CoachController::class, 'getPlayerMetrics']);
    Route::get('players/{player}/metrics/details/{metricType}', [CoachController::class, 'getPlayerMetricDetail']);
});

// Doctor Routes
Route::middleware(['auth:sanctum', 'role:doctor'])->prefix('doctor')->group(function () {
    Route::get('dashboard', [DoctorController::class, 'getDashboard']);
    Route::get('profile', [DoctorController::class, 'getProfile']);
    Route::get('list-all-players', [DoctorController::class, 'listAllPlayers']);
    Route::get('players/{player}/metrics', [DoctorController::class, 'getPlayerMetrics']);
    Route::get('players/{player}/metrics/{metricType}', [DoctorController::class, 'getPlayerMetricDetail']);
    Route::get('players/{player}/program', [DoctorController::class, 'getPlayerProgram']);
    Route::put('players/{player}/program/edit', [DoctorController::class, 'editPlayerTrainingProgram']);
    Route::get('assessments', [DoctorController::class, 'getAssessmentRequests']);
    Route::get('assessments/{assessment}', [DoctorController::class, 'showAssessmentRequest']);
    Route::post('assessments/{assessment}/approve', [DoctorController::class, 'approveAssessment']);
    Route::post('assessments/{assessment}/reschedule', [DoctorController::class, 'rescheduleAssessment']);

    // Need to review
    Route::post('players/{player}/ai-program', [DoctorController::class, 'generateAIProgram']);
    Route::post('programs/{program}/approve', [DoctorController::class, 'approveAIProgram']);
    Route::post('players/{player}/classification/approve', [DoctorController::class, 'approveClassification']);
});

// Notification Routes (for all authenticated users)
Route::middleware('auth:sanctum')
    ->prefix('notifications')
    ->controller(NotificationController::class)
    ->group(function () {
        Route::get('/', 'getNotifications');
        Route::get('/unread', 'getUnreadNotifications');
        Route::get('/pinned', 'getPinnedNotifications');
        Route::get('/unread/count', 'getUnreadCount');
        Route::get('/{id}', 'getNotificationDetails');
        Route::post('/{id}/mark-as-read', 'markAsRead');
        Route::post('/read-all', 'markAllAsRead');
        Route::post('/{notification}/pin', 'pinNotification');
        Route::post('/{notification}/unpin', 'unpinNotification');
        Route::delete('/{id}', 'deleteNotification');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('messages/conversations', [MessageController::class, 'getConversations']);
    Route::get('messages/conversation/{user}', [MessageController::class, 'getMessages']);
    Route::get('messages/users/search', [MessageController::class, 'searchUsers']);

    Route::post('messages/send/{recipient_id}', [MessageController::class, 'sendMessage']);
    Route::post('messages/{message}/mark-as-read', [MessageController::class, 'markAsRead']);
    Route::post('messages/{message}/react', [MessageController::class, 'react']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'getDashboard']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/teams', [AdminController::class, 'getTeams']);
    Route::get('/stats', [AdminController::class, 'getSystemStats']);
});
