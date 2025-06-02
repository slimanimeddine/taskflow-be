<?php

use App\Http\Controllers\V1\MemberController;
use Illuminate\Support\Facades\Route;

Route::post('workspaces/{workspaceId}/members', [MemberController::class, 'create'])
    ->whereUuid('workspaceId')
    ->middleware('auth:sanctum');
