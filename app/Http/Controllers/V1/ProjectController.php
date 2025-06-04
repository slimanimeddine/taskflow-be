<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateProjectRequest;
use App\Http\Resources\V1\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;

/**
 * @group Projects
 */
class ProjectController extends ApiController
{
    /**
     * List workspace projects
     *
     * List all projects in a specific workspace that the authenticated user has access to.
     *
     * @authenticated
     *
     * @apiResourceCollection scenario=Success App\Http\Resources\V1\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to view projects in this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function index(Request $request, string $workspaceId)
    {
        $authUser = $request->user();

        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($authUser->cannot('listWorkspaceProjects', $workspace)) {
            return $this->unauthorized('You are not authorized to view projects in this workspace.');
        }

        $projects = $workspace->projects()->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Create project
     *
     * Create a new project in a specific workspace for the authenticated user.
     *
     * @authenticated
     * 
     * @urlParam workspaceId string required the id of the workspace Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to create a project in this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function create(CreateProjectRequest $request, string $workspaceId)
    {
        $user = $request->user();
        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('createProject', $workspace)) {
            return $this->unauthorized('You are not authorized to create a project in this workspace.');
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('projects');
        }

        $project = Project::create([
            'name' => $request->name,
            'image_path' => $imagePath,
            'workspace_id' => $workspace->id,
        ]);

        return new ProjectResource($project);
    }

}
