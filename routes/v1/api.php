<?php

use App\Http\Controllers\v1\Admin\AdminSubscriptionModelController;
use App\Http\Controllers\v1\Auth\LoginController;
use App\Http\Controllers\v1\Auth\RegisterController;
use App\Http\Controllers\v1\Partner\FlutterwaveController;
use App\Http\Controllers\v1\Partner\PaystackController;
use App\Http\Controllers\v1\Partner\SafehavenController;
use App\Http\Controllers\v1\User\Account\SubAccount\CreateSubAccountController;
use App\Http\Controllers\v1\User\Account\SubAccount\SubAccountController;
use App\Http\Controllers\v1\User\Account\UserAccountController;
use App\Http\Controllers\v1\User\Auth\ResendRegisterOtp;
use App\Http\Controllers\v1\User\Auth\UserLoginController;
use App\Http\Controllers\v1\User\Auth\UserRegisterController;
use App\Http\Controllers\v1\User\Auth\UserResetPasswordController;
use App\Http\Controllers\v1\User\Auth\ValidateUserEmailController;
use App\Http\Controllers\v1\User\Auth\VerifyRegisterOtp;
use App\Http\Controllers\v1\User\Otp\UserOtpController;
use App\Http\Controllers\v1\User\Transaction\TransactionPinController;
use App\Http\Controllers\v1\User\Transaction\TransactionsController;
use App\Http\Controllers\v1\User\UserSubscriptionModelController;
use App\Http\Controllers\v1\User\Wallet\UserWalletController;
use App\Http\Controllers\v1\Utilities\AirtimeServiceController;
use App\Http\Controllers\v1\Utilities\BeneficiaryController;
use App\Http\Controllers\v1\Utilities\CableTVServiceController;
use App\Http\Controllers\v1\Utilities\DataServiceController;
use App\Http\Controllers\v1\Utilities\PaymentController;
use App\Http\Controllers\v1\Utilities\UtilityServiceController;
use Illuminate\Support\Facades\Route;



Route::post('/webhooks/paystack', [PaystackController::class, 'handleWebhook']);
Route::post('/webhooks/flutterwave', [FlutterwaveController::class, 'handleWebhook']);
Route::post('/webhooks/savehaven', [SafehavenController::class, 'handleWebhook']);


/* -------------------------- Authentication Routes ------------------------- */

Route::middleware('checkApplicationCredentials')->prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->post('/validate-email', ValidateUserEmailController::class)->name('auth.validate.email');
    Route::middleware('throttle:login')->post('/register', UserRegisterController::class)->name('auth.register');
    Route::middleware('throttle:otp')->post('/resend-register-otp', ResendRegisterOtp::class)->name('auth.resend.register.otp');
    Route::middleware('throttle:otp')->post('/verify-register-otp', VerifyRegisterOtp::class)->name('auth.verify.register.otp');
    Route::middleware('throttle:login')->post('/login', UserLoginController::class)->name('auth.login');

    Route::middleware('throttle:otp')->post('/send-code', [UserOtpController::class, 'send'])->name('auth.send.otp');
    Route::middleware('throttle:otp')->post('/verify-code', [UserOtpController::class, 'verify'])->name('auth.verify.otp');
    Route::middleware('throttle:login')->post('/reset-password', UserResetPasswordController::class)->name('auth.reset.password');
});




/* -------------------------- User Routes ------------------------- */

Route::middleware(['auth:sanctum', 'checkApplicationCredentials', 'user.is.active', 'user.is.email.verified'])->prefix('user')->group(function () {
    
    Route::prefix('account')->group(function () {
        Route::get('/', [UserAccountController::class, 'show'])->name('user.show.account');
        Route::middleware('user.is.main.account')->put('/update', [UserAccountController::class, 'update'])->name('user.update.account');
        Route::middleware('user.is.main.account')->post('/update-avatar', [UserAccountController::class, 'updateAvatar'])->name('user.update.avatar');
        Route::middleware('user.is.main.account')->post('bvn/initiate-verification', [UserAccountController::class, 'initiateBvnVerification'])->name('user.bvn.initiate.verification');
        Route::middleware('user.is.main.account')->post('bvn/validate-verification', [UserAccountController::class, 'validateBvnVerification'])->name('user.bvn.validate.verification');
        Route::middleware('user.is.main.account')->delete('/', [UserAccountController::class, 'destroy'])->name('user.delete.account');
        Route::middleware('user.is.main.account')->post('/delete-account', [UserAccountController::class, 'verifyOtpAndDeleteAccount'])->name('user.verify.otp.and.delete.account');
    });
    
    Route::middleware('user.is.main.account')->prefix('security')->group(function () {
        Route::prefix('transaction-pin')->group(function () {
            Route::post('/set', [TransactionPinController::class, 'setTransactionPin'])->name('user.set.transaction.pin');
            Route::get('/change', [TransactionPinController::class, 'changeTransactionPin'])->name('user.change.transaction.pin');
            Route::post('/verify-otp', [TransactionPinController::class, 'verifyTransactionPinOtp'])->name('user.verify.otp.transaction.pin');
            Route::get('/resend-otp', [TransactionPinController::class, 'resendTransactionPinOtp'])->name('user.resend.otp.transaction.pin');
            Route::post('/update', [TransactionPinController::class, 'updateTransactionPin'])->name('user.update.transaction.pin');
            Route::post('/verify', [TransactionPinController::class, 'verifyTransactionPin'])->name('user.verify.transaction.pin');
        });
        Route::post('/change-password', [UserAccountController::class, 'changePassword'])->name('user.change.password');
    });

    /* -------------------------- Verified User Routes ------------------------- */
    Route::middleware('user.is.verified')->group(function () {          
        Route::middleware(['user.is.main.account', 'user.is.organization'])->prefix('sub-account')->group(function () {
            Route::post('/', CreateSubAccountController::class)->name('user.create.sub-account');
            Route::get('/', [SubAccountController::class, 'show'])->name('user.sub-accounts');
            Route::put('/{id}', [SubAccountController::class, 'update'])->name('user.update.sub-account');
            Route::delete('/{id}', [SubAccountController::class, 'destroy'])->name('user.delete.sub-account');
        });
        
        Route::prefix('wallet')->group(function () {
            Route::get('/', [UserWalletController::class, 'index'])->name('user.get.wallet');
            Route::middleware('user.is.main.account')->get('/initiate-create', [UserWalletController::class, 'initiateCreateWallet'])->name('user.initiate.create.wallet');
            Route::middleware('user.is.main.account')->post('/', [UserWalletController::class, 'store'])->name('user.create.wallet');
        });
        
        Route::prefix('subscription-model')->group(function () {
            Route::get('/', [UserSubscriptionModelController::class, 'index'])->name('user.list.sub-model');
            Route::get('/{id}', [UserSubscriptionModelController::class, 'show'])->name('user.show.sub-model');
        });
        
        Route::prefix(('transactions'))->group(function () {
            Route::get('/query-users', [TransactionsController::class, 'queryUsers'])->name('user.transactions.query.username');
            Route::post('/send-money-to-username', [TransactionsController::class, 'sendMoneyToUsername'])->name('user.transactions.send.money.to.username');
            Route::post('/send-money-to-email', [TransactionsController::class, 'sendMoneyToEmail'])->name('user.transactions.send.money.to.email');
            Route::prefix('payment')->group(function () {
                Route::get('/banks', [PaymentController::class, 'getBanks'])->name('user.transactions.payment.list.banks');
                Route::post('/resolve-account', [PaymentController::class, 'resolveAccount'])->name('user.transactions.payment.resolve.account');
                Route::post('/send-money', [TransactionsController::class, 'sendMoney'])->name('user.transactions.payment.send.money');
            });
            Route::prefix('beneficiary')->group(function () {
                Route::get('/', [BeneficiaryController::class, 'getBeneficiaries'])->name('user.transactions.beneficiary');
                Route::get('/{query}', [BeneficiaryController::class, 'searchBeneficiaries'])->name('user.transactions.search.beneficiary');
                Route::delete('/{id}', [BeneficiaryController::class, 'deleteBeneficiary'])->name('user.transactions.beneficiary.delete');
                Route::post('/send-money', [BeneficiaryController::class, 'sendMoney'])->name('user.transactions.beneficiary.send.money');
            });
            Route::get('/recents', [TransactionsController::class, 'getRecentRecipients'])->name('user.transactions.recents');
            Route::get('/get-request-styles', [TransactionsController::class, 'getRequestStyles'])->name('user.transactions.get.request.styles');
            Route::post('/request-money-from-username', [TransactionsController::class, 'requestMoneyFromUsername'])->name('user.transactions.request.money.from.username');
            Route::post('/request-money-from-email', [TransactionsController::class, 'requestMoneyFromEmail'])->name('user.transactions.request.money.from.email');
            Route::get('/get-request-money-recents', [TransactionsController::class, 'getRecentRequestMoneyRecipients'])->name('user.transactions.get.request.recents');
            Route::get('/history', [TransactionsController::class, 'transactionHistory'])->name('user.transactions.history');
        });

        Route::prefix('services')->group(function () {
            Route::prefix('airtime')->group(function () {
                Route::get('networks', [AirtimeServiceController::class, 'getNetworks']);
                Route::post('buy', [AirtimeServiceController::class, 'buyAirtime']);
                Route::prefix('beneficiary')->group(function () {
                    Route::get('/', [BeneficiaryController::class, 'getAirtimeBeneficiaries'])->name('user.services.airtime.beneficiary');
                    Route::get('/{query}', [BeneficiaryController::class, 'searchAirtimeBeneficiaries'])->name('user.services.airtime.search.beneficiary');
                    Route::delete('/{id}', [BeneficiaryController::class, 'deleteBeneficiary'])->name('user.services.airtime.beneficiary.delete');
                    Route::post('/buy', [BeneficiaryController::class, 'buyAirtime'])->name('user.services.airtime.beneficiary.buy.airtime');
                });
                // Route::get('history', [AirtimeServiceController::class, 'history']);
            });
            
            Route::prefix('data')->group(function () {
                Route::get('networks', [DataServiceController::class, 'getNetworks']);
                Route::post('plans', [DataServiceController::class, 'getPlans']);
                Route::post('buy', [DataServiceController::class, 'buyData']);
                Route::prefix('beneficiary')->group(function () {
                    Route::get('/', [BeneficiaryController::class, 'getDataBeneficiaries'])->name('user.services.data.beneficiary');
                    Route::get('/{query}', [BeneficiaryController::class, 'searchDataBeneficiaries'])->name('user.services.data.search.beneficiary');
                    Route::delete('/{id}', [BeneficiaryController::class, 'deleteBeneficiary'])->name('user.services.data.beneficiary.delete');
                    Route::post('/buy', [BeneficiaryController::class, 'buyData'])->name('user.services.data.beneficiary.buy.data');
                });
                // Route::get('history', [DataServiceController::class, 'history']);
            });
            
            Route::prefix('cabletv')->group(function () {
                Route::get('companies', [CableTVServiceController::class, 'getCompanies']);
                Route::post('packages', [CableTVServiceController::class, 'getPackages']);
                Route::post('verify', [CableTVServiceController::class, 'verifyNumber']);
                Route::post('buy', [CableTVServiceController::class, 'buySubscription']);
                Route::prefix('beneficiary')->group(function () {
                    Route::get('/', [BeneficiaryController::class, 'getCableTVBeneficiaries'])->name('user.services.cabletv.beneficiary');
                    Route::get('/{query}', [BeneficiaryController::class, 'searchCableTVBeneficiaries'])->name('user.services.cabletv.search.beneficiary');
                    Route::delete('/{id}', [BeneficiaryController::class, 'deleteBeneficiary'])->name('user.services.cabletv.beneficiary.delete');
                    Route::post('/buy', [BeneficiaryController::class, 'buyCableTVSub'])->name('user.services.cabletv.beneficiary.buy');
                });
                // Route::get('history', [CableTVServiceController::class, 'history']);
            });
            
            Route::prefix('utility')->group(function () {
                Route::get('companies', [UtilityServiceController::class, 'getCompanies']);
                Route::post('packages', [UtilityServiceController::class, 'getPackages']);
                Route::post('verify', [UtilityServiceController::class, 'verifyNumber']);
                Route::post('buy', [UtilityServiceController::class, 'buySubscription']);
                Route::prefix('beneficiary')->group(function () {
                    Route::get('/', [BeneficiaryController::class, 'getUtilityBeneficiaries'])->name('user.services.utility.beneficiary');
                    Route::get('/{query}', [BeneficiaryController::class, 'searchUtilityBeneficiaries'])->name('user.services.utility.search.beneficiary');
                    Route::delete('/{id}', [BeneficiaryController::class, 'deleteBeneficiary'])->name('user.services.utility.beneficiary.delete');
                    Route::post('/buy', [BeneficiaryController::class, 'buyUtilitySub'])->name('user.services.utility.beneficiary.buy');
                });
                Route::get('history/{id}', [UtilityServiceController::class, 'history']);
            });
    
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
