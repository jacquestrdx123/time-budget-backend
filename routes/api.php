<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ClockSessionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PersonalTodoController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

$auth = ['middleware' => 'auth.jwt'];

// Auth (no prefix; exact paths for frontend compatibility)
Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);
    Route::get('me', MeController::class)->middleware('auth.jwt');
    Route::post('forgot-password', ForgotPasswordController::class);
    Route::post('reset-password', ResetPasswordController::class);
});

// Users
Route::middleware($auth)->prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);
    Route::get('/{userId}', [UserController::class, 'show']);
    Route::post('/', [UserController::class, 'store']);
    Route::patch('/{userId}', [UserController::class, 'update']);
    Route::delete('/{userId}', [UserController::class, 'destroy']);
});

// Projects (specific paths before :projectId)
Route::middleware($auth)->prefix('projects')->group(function () {
    Route::get('/', [ProjectController::class, 'index']);
    Route::get('/{projectId}/tasks', [ProjectController::class, 'tasks']);
    Route::get('/{projectId}/shifts', [ProjectController::class, 'shifts']);
    Route::get('/{projectId}', [ProjectController::class, 'show']);
    Route::post('/', [ProjectController::class, 'store']);
    Route::patch('/{projectId}', [ProjectController::class, 'update']);
    Route::delete('/{projectId}', [ProjectController::class, 'destroy']);
});

// Tasks
Route::middleware($auth)->prefix('tasks')->group(function () {
    Route::get('/', [TaskController::class, 'index']);
    Route::get('/{taskId}', [TaskController::class, 'show']);
    Route::post('/', [TaskController::class, 'store']);
    Route::patch('/{taskId}', [TaskController::class, 'update']);
    Route::delete('/{taskId}', [TaskController::class, 'destroy']);
});

// Shifts (active and clock-in/out before :shiftId)
Route::middleware($auth)->prefix('shifts')->group(function () {
    Route::get('/', [ShiftController::class, 'index']);
    Route::get('/active/{userId}', [ShiftController::class, 'active']);
    Route::post('/clock-in', [ShiftController::class, 'clockIn']);
    Route::post('/clock-out', [ShiftController::class, 'clockOut']);
    Route::get('/{shiftId}', [ShiftController::class, 'show']);
    Route::post('/', [ShiftController::class, 'store']);
    Route::patch('/{shiftId}', [ShiftController::class, 'update']);
    Route::delete('/{shiftId}', [ShiftController::class, 'destroy']);
});

// Clock sessions
Route::middleware($auth)->prefix('clock-sessions')->group(function () {
    Route::get('/', [ClockSessionController::class, 'index']);
});

// Reminders
Route::middleware($auth)->prefix('reminders')->group(function () {
    Route::get('/', [ReminderController::class, 'index']);
    Route::get('/{reminderId}', [ReminderController::class, 'show']);
    Route::post('/', [ReminderController::class, 'store']);
    Route::patch('/{reminderId}', [ReminderController::class, 'update']);
    Route::delete('/{reminderId}', [ReminderController::class, 'destroy']);
});

// Personal todos
Route::middleware($auth)->prefix('personal-todos')->group(function () {
    Route::get('/', [PersonalTodoController::class, 'index']);
    Route::get('/{todoId}', [PersonalTodoController::class, 'show']);
    Route::post('/', [PersonalTodoController::class, 'store']);
    Route::patch('/{todoId}', [PersonalTodoController::class, 'update']);
    Route::delete('/{todoId}', [PersonalTodoController::class, 'destroy']);
});

// Notifications (specific paths before :notificationId)
Route::middleware($auth)->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/preferences/me', [NotificationController::class, 'preferencesMe']);
    Route::put('/preferences/me', [NotificationController::class, 'updatePreferencesMe']);
    Route::get('/devices/me', [NotificationController::class, 'devicesMe']);
    Route::post('/devices', [NotificationController::class, 'storeDevice']);
    Route::delete('/devices/{deviceId}', [NotificationController::class, 'destroyDevice']);
    Route::post('/test-push', [NotificationController::class, 'testPush']);
    Route::post('/send', [NotificationController::class, 'send']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::get('/{notificationId}', [NotificationController::class, 'show']);
    Route::patch('/{notificationId}/read', [NotificationController::class, 'markRead']);
    Route::delete('/{notificationId}', [NotificationController::class, 'destroy']);
});

// Settings
Route::middleware($auth)->prefix('settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::get('/{key}', [SettingsController::class, 'show']);
    Route::put('/{key}', [SettingsController::class, 'update']);
    Route::post('/', [SettingsController::class, 'store']);
    Route::delete('/{key}', [SettingsController::class, 'destroy']);
});
