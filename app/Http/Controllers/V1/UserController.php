<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\DeleteUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * @group Users
 */
class UserController extends ApiController
{
    /**
     * Get Authenticated User
     *
     * Retrieve the currently authenticated user
     *
     * @authenticated
     *
     * @apiResource scenario=Success App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     *
     * @response 401 scenario=Unauthenticated {
     *      "message": "Unauthenticated"
     * }
     */
    public function getAuthenticatedUser(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Delete User
     *
     * Deletes the authenticated user
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *      "message": "User deleted successfully",
     *     "status": 200
     * }
     * @response 400 scenario="Incorrect password" {
     *      "message": "Incorrect password.",
     *      "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *     "message": "Unauthenticated"
     * }
     */
    public function deleteUser(DeleteUserRequest $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $user->delete();

        return $this->successNoData('User deleted successfully');
    }

    /**
     * List Workspace Members
     *
     * Retrieve a list of all users that are members of a specific workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResourceCollection scenario=Success App\Http\Resources\V1\UserResource
     *
     * @apiResourceModel App\Models\User
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to view this workspace members.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function listWorkspaceMembers(Request $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('listWorkspaceMembers', $workspace)) {
            return $this->unauthorized('You are not authorized to view this workspace members.');
        }

        $members = $workspace->users()->get();

        return UserResource::collection($members);
    }
}
