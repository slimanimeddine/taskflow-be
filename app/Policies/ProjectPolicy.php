<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function show(User $user, Project $project): bool
    {
        $isMember = Member::where('user_id', $user->id)
            ->where('workspace_id', $project->workspace_id)
            ->exists();

        return $isMember;
    }

    public function edit(User $user, Project $project): bool
    {
        return $this->show($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        $isAdmin = Member::where('user_id', $user->id)
            ->where('workspace_id', $project->workspace_id)
            ->where('role', 'admin')
            ->exists();

        return $isAdmin;
    }
}
