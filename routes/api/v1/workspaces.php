<?php

use App\Http\Controllers\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::get('users/me/workspaces', [WorkspaceController::class, 'index'])->middleware('auth:sanctum');
Route::post('workspaces', [WorkspaceController::class, 'create'])->middleware('auth:sanctum');
Route::post('workspaces/{workspaceId}', [WorkspaceController::class, 'edit'])->middleware('auth:sanctum')->whereUuid('workspaceId');
Route::get('workspaces/{workspaceId}', [WorkspaceController::class, 'show'])->middleware('auth:sanctum')->whereUuid('workspaceId');
