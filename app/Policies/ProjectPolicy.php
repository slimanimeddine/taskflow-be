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
}
