<?php

use App\Http\Controllers\V1\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'create']);
        Route::delete('/{taskId}', [TaskController::class, 'delete'])->whereUuid('taskId');
        Route::patch('/{taskId}', [TaskController::class, 'edit'])->whereUuid('taskId');
        Route::patch('/', [TaskController::class, 'bulkEditPositions']);
        Route::get('/{taskId}', [TaskController::class, 'show'])->whereUuid('taskId');
    });
});
