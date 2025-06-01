<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateWorkspaceRequest;
use App\Http\Resources\V1\WorkspaceResource;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Http\Request;
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
}
