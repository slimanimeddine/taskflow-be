<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function edit(User $user, Workspace $workspace): bool
    {
        $isAdmin = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->where('role', 'admin')
            ->exists();

        return $isAdmin;
    }

    public function show(User $user, Workspace $workspace): bool
    {
        $isMember = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->exists();

        return $isMember;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->edit($user, $workspace);
    }

    public function resetInviteCode(User $user, Workspace $workspace): bool
    {
        return $this->edit($user, $workspace);
    }
}
