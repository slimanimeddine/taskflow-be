<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function delete(User $user, Task $task): bool
    {
        $isMember = Member::where('user_id', $user->id)
            ->where('workspace_id', $task->workspace_id)
            ->exists();

        return $isMember;
    }
}
