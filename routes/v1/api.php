<?php

use App\Http\Controllers\v1\Admin\AdminSubscriptionModelController;
use App\Http\Controllers\v1\Auth\LoginController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\User\UserSubscriptionModelController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/* -------------------------- Authentication Routes ------------------------- */

Route::middleware('checkApplicationCredentials')->prefix('auth')->group(function () {
    // Register a new user
    Route::post('register', RegisterController::class)->name('auth.register');
    // Login a uer
    Route::post('login', LoginController::class)->name('auth.login');
});




/* -------------------------- User Routes ------------------------- */

Route::middleware('sanctum')->prefix('user')->group(function () {
    Route::prefix('subscription-model')->group(function () {
        Route::get('/', [UserSubscriptionModelController::class, 'index'])->name('user.sub-model.list');
        Route::get('/{id}', [UserSubscriptionModelController::class, 'show'])->name('user.sub-model.show');
    });
});





/* -------------------------- Admin Routes ------------------------- */

Route::middleware('sanctum')->prefix('admin')->group(function () {
    Route::prefix('subscription-model')->group(function () {
        Route::get('/', [AdminSubscriptionModelController::class, 'index'])->name('admin.sub-model.list');
        Route::get('/{id}', [AdminSubscriptionModelController::class, 'show'])->name('admin.sub-model.show');
        Route::post('/', [AdminSubscriptionModelController::class, 'store'])->name('admin.sub-model.store');
    });
});
