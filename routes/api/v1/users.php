<?php

use App\Http\Controllers\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::get('users/me', [UserController::class, 'getAuthenticatedUser'])->middleware('auth:sanctum');
Route::delete('users/me', [UserController::class, 'deleteUser'])->middleware('auth:sanctum');
