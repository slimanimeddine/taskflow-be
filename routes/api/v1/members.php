<?php

use App\Http\Controllers\V1\MemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('workspaces/{workspaceId}/members', [MemberController::class, 'create'])
        ->whereUuid('workspaceId');

    Route::delete('workspaces/{workspaceId}/users/{userId}/members', [MemberController::class, 'delete'])
        ->whereUuid('workspaceId')
        ->whereUuid('userId');
});