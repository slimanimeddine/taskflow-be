<?php

use App\Http\Controllers\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('users/me/workspaces', [WorkspaceController::class, 'index'])->middleware('auth:sanctum');
Route::post('workspaces', [WorkspaceController::class, 'create'])->middleware('auth:sanctum');
