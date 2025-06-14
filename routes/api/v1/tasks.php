<?php

use App\Http\Controllers\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'create']);
        Route::delete('/{taskId}', [TaskController::class, 'delete'])->whereUuid('workspaceId');
    });
});
