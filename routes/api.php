<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CleanerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\CleanerDashboardController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\SettingController;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');

// Public Routes
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/public/cleaners', [CleanerController::class, 'publicList']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Shared Settings Routes
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings/profile', [SettingController::class, 'updateProfile']);
    Route::post('/settings/password', [SettingController::class, 'updatePassword']);
});

// Cleaner Routes (Protected by Custom Cleaner Middleware)
Route::middleware('auth.cleaner')->prefix('cleaner')->group(function () {
    Route::get('/dashboard', [CleanerDashboardController::class, 'index']);
    Route::get('/schedule', [CleanerDashboardController::class, 'schedule']);
    Route::post('/jobs/{id}/status', [CleanerDashboardController::class, 'updateStatus']);
    // Allow cleaners to create bookings
    Route::post('/bookings', [BookingController::class, 'storeCleanerBooking']);
});

// Customer Routes (Protected by Custom Customer Middleware)
Route::middleware('auth.customer')->prefix('customer')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index']);
    // Customer Booking Routes
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{id}/rate', [BookingController::class, 'rate']);
});

// Admin Routes (Protected by Custom Admin Middleware)
Route::middleware('auth.admin')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/analytics', [AdminDashboardController::class, 'analytics']);
    Route::get('/customers', [AdminDashboardController::class, 'customers']);
    Route::apiResource('services', ServiceController::class);
    // Route::post('/cleaner/bookings', [BookingController::class, 'storeCleanerBooking']); // Removed redundant route
    Route::apiResource('bookings', BookingController::class);
    Route::post('/cleaners/{id}/approve', [CleanerController::class, 'approve']);
    Route::post('/cleaners/{id}/reject', [CleanerController::class, 'reject']);
    Route::apiResource('cleaners', CleanerController::class);
    Route::apiResource('users', \App\Http\Controllers\UserController::class);

    // Admin Only Settings
    Route::post('/settings/general', [SettingController::class, 'updateGeneral']);
    Route::post('/settings/hours', [SettingController::class, 'updateHours']);
});
