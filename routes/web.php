<?php

use App\Http\Controllers\Admin\StoreOwnerAuthController;
use App\Http\Controllers\Admin\StoreOwnerDashboardController;
use App\Http\Controllers\Admin\SuperAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dazzle Drys – Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => redirect('/store/login'));

// ── Super Admin ───────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',   [SuperAdminController::class, 'showLogin'])->name('login');
    Route::post('login',  [SuperAdminController::class, 'login']);
    Route::post('logout', [SuperAdminController::class, 'logout'])->name('logout');

    Route::middleware('auth:admin')->group(function () {
        Route::get('dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

        // Stores
        Route::get('stores',              [SuperAdminController::class, 'stores'])->name('stores');
        Route::get('stores/{id}',         [SuperAdminController::class, 'storeShow'])->name('stores.show');
        Route::post('stores/{id}/approve',[SuperAdminController::class, 'storeApprove'])->name('stores.approve');
        Route::post('stores/{id}/reject', [SuperAdminController::class, 'storeReject'])->name('stores.reject');
        Route::post('stores/{id}/toggle', [SuperAdminController::class, 'storeToggle'])->name('stores.toggle');

        // Users
        Route::get('users',              [SuperAdminController::class, 'users'])->name('users');
        Route::post('users/{id}/toggle', [SuperAdminController::class, 'userToggle'])->name('users.toggle');

        // Bookings
        Route::get('bookings', [SuperAdminController::class, 'bookings'])->name('bookings');

        // Garment Categories
        Route::get('categories',          [SuperAdminController::class, 'categories'])->name('categories');
        Route::post('categories',         [SuperAdminController::class, 'categoryStore'])->name('categories.store');
        Route::delete('categories/{id}',  [SuperAdminController::class, 'categoryDelete'])->name('categories.delete');

        // Store Owners
        Route::get('store-owners',              [SuperAdminController::class, 'storeOwners'])->name('store_owners');
        Route::post('store-owners/{id}/toggle', [SuperAdminController::class, 'storeOwnerToggle'])->name('store_owners.toggle');
    });
});

// ── Store Owner ───────────────────────────────────────────────────────────────
Route::prefix('store')->name('store.')->group(function () {
    Route::get('login',    [StoreOwnerAuthController::class, 'showLogin'])->name('login');
    Route::post('login',   [StoreOwnerAuthController::class, 'login']);
    Route::get('register', [StoreOwnerAuthController::class, 'showRegister'])->name('register');
    Route::post('register',[StoreOwnerAuthController::class, 'register']);
    Route::post('logout',  [StoreOwnerAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:store_owner')->group(function () {
        Route::get('dashboard', [StoreOwnerDashboardController::class, 'dashboard'])->name('dashboard');

        // Store
        Route::get('store/create',  [StoreOwnerDashboardController::class, 'storeCreate'])->name('store.create');
        Route::post('store',        [StoreOwnerDashboardController::class, 'storeStore'])->name('store.store');
        Route::get('store/edit',    [StoreOwnerDashboardController::class, 'storeEdit'])->name('store.edit');
        Route::put('store',         [StoreOwnerDashboardController::class, 'storeUpdate'])->name('store.update');
        Route::post('store/toggle', [StoreOwnerDashboardController::class, 'toggleAvailability'])->name('store.toggle');

        // Timings
        Route::get('timings',  [StoreOwnerDashboardController::class, 'timings'])->name('timings');
        Route::post('timings', [StoreOwnerDashboardController::class, 'updateTimings']);

        // Pricing
        Route::get('pricing',         [StoreOwnerDashboardController::class, 'pricing'])->name('pricing');
        Route::post('pricing',        [StoreOwnerDashboardController::class, 'pricingStore']);
        Route::put('pricing/{id}',    [StoreOwnerDashboardController::class, 'pricingUpdate'])->name('pricing.update');
        Route::delete('pricing/{id}', [StoreOwnerDashboardController::class, 'pricingDelete'])->name('pricing.delete');

        // Slots
        Route::get('slots',         [StoreOwnerDashboardController::class, 'slots'])->name('slots');
        Route::post('slots',        [StoreOwnerDashboardController::class, 'slotsStore']);
        Route::delete('slots/{id}', [StoreOwnerDashboardController::class, 'slotsDelete'])->name('slots.delete');

        // Bookings
        Route::get('bookings',            [StoreOwnerDashboardController::class, 'bookings'])->name('bookings');
        Route::put('bookings/{id}/status',[StoreOwnerDashboardController::class, 'bookingUpdate'])->name('bookings.update');
    });
});
