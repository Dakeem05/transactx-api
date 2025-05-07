<?php

use App\Http\Controllers\v1\Admin\AdminSubscriptionModelController;
use App\Http\Controllers\v1\Auth\LoginController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Partner\FlutterwaveController;
use App\Http\Controllers\v1\Partner\PaystackController;
use App\Http\Controllers\v1\User\Account\SubAccount\CreateSubAccountController;
use App\Http\Controllers\v1\User\Account\SubAccount\SubAccountController;
use App\Http\Controllers\v1\User\Account\UserAccountController;
use App\Http\Controllers\v1\User\Auth\UserLoginController;
use App\Http\Controllers\v1\User\Auth\UserRegisterController;
use App\Http\Controllers\v1\User\Auth\ValidateUserEmailController;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Controllers\v1\User\UserSubscriptionModelController;
use App\Http\Controllers\v1\User\Wallet\UserWalletController;
use App\Http\Controllers\v1\Utilities\PaymentController;
use Illuminate\Support\Facades\Route;



Route::post('/webhooks/paystack', [PaystackController::class, 'handleWebhook']);
Route::post('/webhooks/flutterwave', [FlutterwaveController::class, 'handleWebhook']);


/* -------------------------- Authentication Routes ------------------------- */

Route::middleware('checkApplicationCredentials')->prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post('/validate-email', ValidateUserEmailController::class)->name('auth.validate.email');
    Route::middleware('throttle:login')->post('/register', UserRegisterController::class)->name('auth.register');
    Route::middleware('throttle:login')->post('/login', UserLoginController::class)->name('auth.login');

    Route::middleware('throttle:otp')->post('/send-code', [UserOtpController::class, 'send'])->name('auth.send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [UserOtpController::class, 'verify'])->name('auth.verify.otp');
});




/* -------------------------- User Routes ------------------------- */

Route::middleware(['auth:sanctum', 'checkApplicationCredentials', 'user.is.active'])->prefix('user')->group(function () {
    
    Route::prefix('account')->group(function () {
        Route::get('/', [UserAccountController::class, 'show'])->name('user.show.account');
        Route::put('/update', [UserAccountController::class, 'update'])->name('user.update.account');
        Route::post('bvn/verification', [UserAccountController::class, 'verifyBVN'])->name('user.validate.bvn');
    });
    

    
    Route::prefix('sub-account')->group(function () {
        Route::post('/', CreateSubAccountController::class)->name('user.create.sub-account');
        Route::get('/', [SubAccountController::class, 'show'])->name('user.sub-accounts');
        // Route::put('/{id}', [UserAccountController::class, 'updateSubAccount'])->name('user.update.sub-account');
        // Route::delete('/{id}', [UserAccountController::class, 'deleteSubAccount'])->name('user.delete.sub-account');
    });
    /* -------------------------- Verified User Routes ------------------------- */
    Route::middleware('user.is.verified')->group(function () {

        Route::prefix('subscription-model')->group(function () {
            Route::get('/', [UserSubscriptionModelController::class, 'index'])->name('user.list.sub-model');
            Route::get('/{id}', [UserSubscriptionModelController::class, 'show'])->name('user.show.sub-model');
        });
        
        Route::prefix('wallet')->group(function () {
            Route::post('/', [UserWalletController::class, 'store'])->name('user.create.wallet');
            Route::get('/', [UserWalletController::class, 'index'])->name('user.get.wallet');
        });

        Route::prefix('payment')->group(function () {
            Route::get('/banks', [PaymentController::class, 'getBanks'])->name('user.payment.list.banks');
            Route::post('/resolve-account', [PaymentController::class, 'resolveAccount'])->name('user.payment.resolve.account');
        });

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
