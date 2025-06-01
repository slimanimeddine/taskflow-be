<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\Response;

class WorkspacePolicy
{
    /**
     * Determine whether the user can update the model.
     */
    public function edit(User $user, Workspace $workspace): bool
    {
        $isAdmin = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->where('role', 'admin')
            ->exists();

        return $isAdmin;
    }

    /**
     * Determine whether the user can see the model.
     */
    public function show(User $user, Workspace $workspace): bool
    {
        $isMember = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->exists();

        return $isMember;
    }
}
