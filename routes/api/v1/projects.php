<?php

use App\Http\Controllers\V1\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('projects/{projectId}', [ProjectController::class, 'show'])->whereUuid('projectId');
    Route::post('projects/{projectId}', [ProjectController::class, 'edit'])->whereUuid('projectId');
    Route::delete('projects/{projectId}', [ProjectController::class, 'delete'])->whereUuid('projectId');

    Route::get('workspaces/{workspaceId}/projects', [ProjectController::class, 'index'])->whereUuid('workspaceId');
    Route::post('workspaces/{workspaceId}/projects', [ProjectController::class, 'create'])->whereUuid('workspaceId');
});
