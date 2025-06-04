<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateMemberRequest;
use App\Http\Resources\V1\MemberResource;
use App\Models\Member;
use App\Models\User;
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

        if (! $workspace) {
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

    /**
     * Delete member
     *
     * Delete a member from a workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     * @urlParam userId string required the id of the member Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @response 200 scenario=Success {
     *       "message": "Member deleted successfully.",
     *       "status": 200
     * }
     * @response 400 scenario="Cannot Delete Last Member" {
     *       "message": "You cannot delete the last member of a workspace.",
     *       "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to delete this member.",
     *       "status": 403
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     * @response 404 scenario="User Not Found" {
     *       "message": "User not found",
     *       "status": 404
     * }
     * @response 404 scenario="Member Not Found" {
     *       "message": "This user is not a member of this workspace.",
     *       "status": 404
     * }
     */
    public function delete(Request $request, string $workspaceId, string $userId)
    {
        $authUser = $request->user();

        $user = User::find($userId);
        $workspace = Workspace::find($workspaceId);
        $member = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->first();

        if (! $workspace) {
            return $this->notFound('Workspace not found');
        }

        $workspaceMembersCount = Member::where('workspace_id', $workspace->id)->count();

        if ($workspaceMembersCount <= 1) {
            return $this->error('You cannot delete the last member of a workspace.');
        }

        if (! $user) {
            return $this->notFound('User not found');
        }

        if (! $member) {
            return $this->notFound('This user is not a member of this workspace.');
        }

        if ($authUser->cannot('delete', $member)) {
            return $this->unauthorized('You are not authorized to delete this member.');
        }

        $member->delete();

        return $this->successNoData('Member deleted successfully.');
    }

    /**
     * Show authenticated user member
     *
     * Retrieve the member details of the authenticated user in a specific workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\MemberResource
     *
     * @apiResourceModel App\Models\Member
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     * @response 404 scenario="Member Not Found" {
     *       "message": "This user is not a member of this workspace.",
     *       "status": 404
     * }
     */
    public function showAuthUserMember(Request $request, string $workspaceId)
    {
        $authUser = $request->user();
        $workspace = Workspace::find($workspaceId);
        $member = Member::where('user_id', $authUser->id)
            ->where('workspace_id', $workspace->id)
            ->first();

        if (! $workspace) {
            return $this->notFound('Workspace not found');
        }

        if (! $member) {
            return $this->notFound('This user is not a member of this workspace.');
        }

        return new MemberResource($member);
    }

    /**
     * Promote member
     *
     * Promote a member to admin in a workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     * @urlParam userId string required the id of the member Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\MemberResource
     *
     * @apiResourceModel App\Models\Member
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to promote this member.",
     *       "status": 403
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     * @response 404 scenario="User Not Found" {
     *       "message": "User not found",
     *       "status": 404
     * }
     * @response 404 scenario="Member Not Found" {
     *       "message": "This user is not a member of this workspace.",
     *       "status": 404
     * }
     */
    public function promote(Request $request, string $workspaceId, string $userId)
    {
        $authUser = $request->user();
        $workspace = Workspace::find($workspaceId);
        $user = User::find($userId);
        $member = Member::where('user_id', $user->id)
            ->where('workspace_id', $workspace->id)
            ->first();

        if (! $workspace) {
            return $this->notFound('Workspace not found');
        }
        if (! $user) {
            return $this->notFound('User not found');
        }
        if (! $member) {
            return $this->notFound('This user is not a member of this workspace.');
        }
        if ($authUser->cannot('promote', $member)) {
            return $this->unauthorized('You are not authorized to promote this member.');
        }

        $member->update(['role' => 'admin']);

        return new MemberResource($member);
    }
}
