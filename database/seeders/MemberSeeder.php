<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspaces = Workspace::all();

        foreach ($workspaces as $workspace) {
            Member::create([
                'role' => 'admin',
                'workspace_id' => $workspace->id,
                'user_id' => $workspace->user_id,
            ]);
        }

        $adminUserIds = Member::where('role', 'admin')->pluck('user_id');

        $nonAdminUsers = User::whereNotIn('id', $adminUserIds)->get();

        foreach ($nonAdminUsers as $user) {
            $workspace = $workspaces->random();

            $isAlreadyMember = Member::where('user_id', $user->id)
                ->where('workspace_id', $workspace->id)
                ->exists();

            if (!$isAlreadyMember) {
                Member::create([
                    'role' => 'member',
                    'user_id' => $user->id,
                    'workspace_id' => $workspace->id,
                ]);
            }
        }
    }
}
