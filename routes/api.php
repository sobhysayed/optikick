<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Users\PlayerController;
use App\Http\Controllers\API\Auth\AuthController;
use App\Http\Controllers\API\Users\CoachController;
use App\Http\Controllers\API\Users\DoctorController;
use App\Http\Controllers\API\Users\PlayerNotificationController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\HealthInfoCardController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\AdminController;

// Auth Routes
// Auth Routes with specific rate limiting
Route::middleware(['throttle:6,1'])->group(function () { // 6 attempts per minute
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('verify-reset-token', [AuthController::class, 'verifyResetToken']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Player Routes
Route::middleware(['auth:sanctum', 'role:player'])->group(function () {
    Route::get('player/dashboard', [PlayerController::class, 'getDashboard']);
    Route::get('player/profile', [PlayerController::class, 'getProfile']);

    Route::get('player/metrics', [PlayerController::class, 'getMetrics']);

    Route::post('player/request-assessment', [PlayerController::class, 'requestAssessment']);

    Route::get('player/training-program', [PlayerController::class, 'getTrainingProgram']);

});

Route::middleware(['auth:sanctum', 'role:coach'])->prefix('coach')->group(function () {
    Route::get('dashboard', [CoachController::class, 'getDashboard']);
    Route::get('profile', [CoachController::class, 'getProfile']);

    Route::get('team/overview', [CoachController::class, 'getTeamOverview']);
    Route::get('players/{player}/program', [CoachController::class, 'getPlayerProgram']);
    Route::get('players/{player}/metrics', [CoachController::class, 'getPlayerMetrics']);
    Route::get('players/{player}/metrics/{metricType}', [CoachController::class, 'getPlayerMetricDetail']);
});

// Doctor Routes
Route::middleware(['auth:sanctum', 'role:doctor'])->prefix('doctor')->group(function () {
    Route::get('dashboard', [DoctorController::class, 'getDashboard']);
    Route::get('profile', [DoctorController::class, 'getProfile']);

    Route::get('metrics/{metricType}/details', [DoctorController::class, 'getMetricDetails']);

    Route::get('team/overview', [DoctorController::class, 'getTeamOverview']);
    Route::get('players/{player}', [DoctorController::class, 'getPlayerDetails']);

    Route::post('players/{player}/ai-program', [DoctorController::class, 'generateAIProgram']);
    Route::post('programs/{program}/approve', [DoctorController::class, 'approveAIProgram']);

    Route::get('assessments', [DoctorController::class, 'getAssessmentRequests']);
    Route::post('assessments/{assessment}/respond', [DoctorController::class, 'respondToAssessment']);

    
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
        Route::post('/{id}/read', 'markAsRead');
        Route::post('/read-all', 'markAllAsRead');
        Route::post('/{notification}/pin', 'pinNotification');
        Route::post('/{notification}/unpin', 'unpinNotification');
        Route::delete('/{id}', 'deleteNotification');
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/health-cards/daily', [HealthInfoCardController::class, 'getDailyCards']);
    Route::post('logout', [AuthController::class, 'logout']);

});

Route::middleware(['auth:sanctum', 'role:player'])->prefix('player')->group(function () {
    Route::get('notifications', [PlayerNotificationController::class, 'getNotifications']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('conversations', [MessageController::class, 'getConversations']);
    Route::get('messages/search', [MessageController::class, 'searchMessages']);
    Route::get('messages/{user}', [MessageController::class, 'getMessages']);
    Route::post('messages/{recipient}', [MessageController::class, 'sendMessage']);
    Route::post('messages/{message}/read', [MessageController::class, 'markAsRead']);
    Route::post('messages/{message}/reaction', [MessageController::class, 'addReaction']);
});

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'getDashboard']);
    Route::get('/users', [AdminController::class, 'getUsers']);
    Route::get('/teams', [AdminController::class, 'getTeams']);
    Route::get('/stats', [AdminController::class, 'getSystemStats']);
});
