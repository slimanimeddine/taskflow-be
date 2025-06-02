<?php

use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('users')->group(function () {
        Route::get('/me', [UserController::class, 'getAuthenticatedUser']);
        Route::delete('/me', [UserController::class, 'deleteUser']);
    });
});