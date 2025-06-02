<?php

use App\Http\Controllers\V1\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('users/me/workspaces', [WorkspaceController::class, 'index']);

    Route::prefix('workspaces')->group(function () {
        Route::post('/', [WorkspaceController::class, 'create']);
        Route::post('/{workspaceId}', [WorkspaceController::class, 'edit'])->whereUuid('workspaceId');
        Route::get('/{workspaceId}', [WorkspaceController::class, 'show'])->whereUuid('workspaceId');
        Route::delete('/{workspaceId}', [WorkspaceController::class, 'delete'])->whereUuid('workspaceId');
        Route::patch('/{workspaceId}/reset-invite-code', [WorkspaceController::class, 'resetInviteCode'])->whereUuid('workspaceId');
    });
});