<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function delete(User $user, Member $member): bool
    {
        $actionTaker = Member::where('user_id', $user->id)
            ->where('workspace_id', $member->workspace->id)
            ->first();

        return ($user->id === $member->user_id) || ($user->id !== $member->user_id && $actionTaker->role === 'admin' && $member->role === 'member');
    }
}
