<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordRecoveryController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\EventCategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LocalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminOnly;
use App\Http\Controllers\UserController;

Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);


Route::post('/recover-password', [PasswordRecoveryController::class, 'sendPasswordResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/events/{id}', [EventController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    // Admin routes
    Route::middleware(AdminOnly::class)->group(function () {
        Route::apiResource('/users', UserController::class);
    });


    // Normal user routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('/events', EventController::class)->only(['store']);
    Route::apiResource('/locals', LocalController::class);
    Route::apiResource('/event-categories', EventCategoryController::class);
});
