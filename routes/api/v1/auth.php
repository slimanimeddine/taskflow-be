<?php

use App\Http\Controllers\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('sign-up', [AuthController::class, 'signUp']);
Route::middleware(['throttle:sign-in'])->group(function (): void {
    Route::post('sign-in', [AuthController::class, 'signIn']);
});
Route::post('sign-out', [AuthController::class, 'signOut'])->middleware('auth:sanctum');
Route::post('change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth:sanctum'])->whereUuid('id')->where('hash', '[a-f0-9]{40}');
Route::post('/email/verification-notification', [AuthController::class, 'resendEmailVerification'])->middleware(['auth:sanctum']);
Route::post('/forgot-password', [AuthController::class, 'sendPasswordResetLink'])->middleware('guest')->name('password.email');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('guest')->name('password.update');
