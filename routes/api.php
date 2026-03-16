<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\StoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dazzle Drys – Public API Routes
|--------------------------------------------------------------------------
*/

// ── Authentication ────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('send-otp',    [AuthController::class, 'sendOtp']);
    Route::post('verify-otp',  [AuthController::class, 'verifyOtp']);
});

// ── Public Store Routes ───────────────────────────────────────────────────────
Route::prefix('stores')->group(function () {
    Route::get('/',                          [StoreController::class, 'index']);
    Route::get('{id}',                       [StoreController::class, 'show']);
    Route::get('{id}/pricing',               [StoreController::class, 'pricing']);
    Route::get('{id}/available-slots',       [StoreController::class, 'availableSlots']);
});

// ── Authenticated Routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Profile
    Route::prefix('user')->group(function () {
        Route::put('profile',       [AuthController::class, 'updateProfile']);
        Route::post('device-token', [AuthController::class, 'registerDeviceToken']);
        Route::post('logout',       [AuthController::class, 'logout']);
    });

    // Bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/',           [BookingController::class, 'index']);
        Route::post('/',          [BookingController::class, 'store']);
        Route::get('{id}',        [BookingController::class, 'show']);
        Route::post('{id}/cancel', [BookingController::class, 'cancel']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/',                    [NotificationController::class, 'index']);
        Route::put('{id}/read',            [NotificationController::class, 'markRead']);
        Route::put('mark-all-read',        [NotificationController::class, 'markAllRead']);
    });
});
