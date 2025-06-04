<?php

use App\Http\Controllers\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])->whereUuid('workspaceId');
    Route::post('workspaces/{workspaceId}/projects', [ProjectController::class, 'create'])->whereUuid('workspaceId');
});