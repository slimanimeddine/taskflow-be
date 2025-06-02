<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateMemberRequest;
use App\Http\Resources\V1\MemberResource;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * @group Members
 */
class MemberController extends ApiController
{
    /**
     * Create member
     *
     * Create a new member in a workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\MemberResource
     *
     * @apiResourceModel App\Models\Member
     * 
     * @response 400 scenario=Error {
     *       "message": "You are already a member of this workspace.",
     *       "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function create(CreateMemberRequest $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        $isMember = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->exists();

        if ($isMember) {
            return $this->error('You are already a member of this workspace.');
        }

        $member = Member::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        return new MemberResource($member);
    }
}
