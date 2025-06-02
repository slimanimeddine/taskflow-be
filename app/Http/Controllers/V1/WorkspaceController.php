<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateWorkspaceRequest;
use App\Http\Requests\V1\EditWorkspaceRequest;
use App\Http\Resources\V1\WorkspaceResource;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @group Workspaces
 */
class WorkspaceController extends ApiController
{
    /**
     * List authenticated user workspaces
     *
     * List all workspaces the authenticated user belongs to.
     *
     * @authenticated
     *
     * @apiResourceCollection scenario=Success App\Http\Resources\V1\WorkspaceResource
     *
     * @apiResourceModel App\Models\Workspace
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $workspaces = $user->allWorkspaces()->get();

        return WorkspaceResource::collection($workspaces);
    }

    /**
     * Create workspace
     *
     * Create a new workspace for the authenticated user.
     *
     * @authenticated
     *
     * @apiResource scenario=Success App\Http\Resources\V1\WorkspaceResource
     *
     * @apiResourceModel App\Models\Workspace
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     */
    public function create(CreateWorkspaceRequest $request)
    {
        $user = $request->user();

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('workspaces');
        }

        $workspace = Workspace::create([
            'name' => $request->name,
            'image_path' => $imagePath,
            'user_id' => $user->id,
            'invite_code' => Str::random(10),
        ]);

        Member::create([
            'role' => 'admin',
            'user_id' => $user->id,
            'workspace_id' => $workspace->id,
        ]);

        return new WorkspaceResource($workspace);
    }

    /**
     * Edit workspace
     *
     * Edit the specified workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\WorkspaceResource
     *
     * @apiResourceModel App\Models\Workspace
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to edit this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function edit(EditWorkspaceRequest $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('edit', $workspace)) {
            return $this->unauthorized('You are not authorized to edit this workspace.');
        }

        $imagePath = null;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($workspace->image_path) {
                Storage::delete($workspace->image_path);
            }
            $imagePath = $request->file('image')->store('workspaces');
        }

        $workspace->update([
            'name' => $request->name,
            'image_path' => $imagePath,
        ]);

        return new WorkspaceResource($workspace);
    }

    /**
     * Show workspace
     *
     * Show the specified workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\WorkspaceResource
     *
     * @apiResourceModel App\Models\Workspace
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to visit this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function show(Request $request, string $workspaceId)
    {
        $user = $request->user();

        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('show', $workspace)) {
            return $this->unauthorized('You are not authorized to visit this workspace.');
        }

        return new WorkspaceResource($workspace);
    }

    /**
     * Delete workspace
     *
     * Delete the specified workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @response 200 scenario=Success {
     *       "message": "Workspace deleted successfully",
     *       "status": 200
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to edit this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function delete(Request $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('delete', $workspace)) {
            return $this->unauthorized('You are not authorized to delete this workspace.');
        }

        $workspace->delete();

        return $this->successNoData('Workspace deleted successfully');
    }

    /**
     * Reset workspace invite code
     *
     * Reset the invite code for the specified workspace.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\WorkspaceResource
     *
     * @apiResourceModel App\Models\Workspace
     * 
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to reset the invite code for this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function resetInviteCode(Request $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('resetInviteCode', $workspace)) {
            return $this->unauthorized('You are not authorized to reset the invite code for this workspace.');
        }

        $workspace->update([
            'invite_code' => Str::random(10),
        ]);

        return new WorkspaceResource($workspace);
    }
}
