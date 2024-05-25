<?php

use App\Http\Controllers\v1\Admin\AdminSubscriptionModelController;
use App\Http\Controllers\v1\Auth\LoginController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\User\Account\UserAccountController;
use App\Http\Controllers\v1\User\Auth\UserLoginController;
use App\Http\Controllers\v1\User\Auth\UserRegisterController;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Controllers\v1\User\UserSubscriptionModelController;
use Illuminate\Support\Facades\Route;


/* -------------------------- Authentication Routes ------------------------- */

Route::middleware('checkApplicationCredentials')->prefix('auth')->group(function () {
    Route::middleware('throttle:login')->post('/register', UserRegisterController::class)->name('auth.register');
    Route::middleware('throttle:login')->post('/login', UserLoginController::class)->name('auth.login');

    Route::middleware('throttle:otp')->post('/send-code', [UserOtpController::class, 'send'])->name('auth.send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [UserOtpController::class, 'verify'])->name('auth.verify.otp');
});




/* -------------------------- User Routes ------------------------- */

Route::middleware(['auth:sanctum', 'checkApplicationCredentials'])->prefix('user')->group(function () {
    Route::prefix('subscription-model')->group(function () {
        Route::get('/', [UserSubscriptionModelController::class, 'index'])->name('user.list.sub-model');
        Route::get('/{id}', [UserSubscriptionModelController::class, 'show'])->name('user.show.sub-model');
    });


    Route::prefix('account')->group(function () {
        Route::get('/', [UserAccountController::class, 'show'])->name('user.show.account');
        Route::put('{id}/update', [UserAccountController::class, 'update'])->name('user.update.account');
    });
});





/* -------------------------- Admin Routes ------------------------- */

Route::middleware(['sanctum', 'checkApplicationCredentials', 'isRolePermitted:ADMIN'])->prefix('admin')->group(function () {
    Route::prefix('subscription-model')->group(function () {
        Route::get('/', [AdminSubscriptionModelController::class, 'index'])->name('admin.sub-model.list');
        Route::get('/{id}', [AdminSubscriptionModelController::class, 'show'])->name('admin.sub-model.show');
        Route::post('/', [AdminSubscriptionModelController::class, 'store'])->name('admin.sub-model.store');
    });
});
