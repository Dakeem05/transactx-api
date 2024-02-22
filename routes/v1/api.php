<?php

use App\Http\Controllers\v1\Auth\RegisterController;
use App\Models\Role;
use Illuminate\Http\Request;
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

Route::get('/me', function (Request $request) {
    return Role::user_role_id();
})->middleware('throttle:login');


/* -------------------------- Authentication Routes ------------------------- */
Route::middleware('checkApplicationCredentials')->prefix('auth')->group(function () {
    // Register a new user
    Route::post('register', RegisterController::class)->name('auth.register');
});
