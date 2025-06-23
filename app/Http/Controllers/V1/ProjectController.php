<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\CreateProjectRequest;
use App\Http\Requests\V1\EditProjectRequest;
use App\Http\Resources\V1\ProjectResource;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Show project
     *
     * Show the specified project.
     *
     * @authenticated
     *
     * @urlParam projectId string required the id of the project Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to view this project.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Project not found",
     *       "status": 404
     * }
     */
    public function show(Request $request, string $projectId)
    {
        $user = $request->user();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        if ($user->cannot('show', $project)) {
            return $this->unauthorized('You are not authorized to view this project.');
        }

        return new ProjectResource($project);
    }

    /**
     * Edit project
     *
     * Edit the specified project.
     *
     * @authenticated
     *
     * @urlParam workspaceId string required the id of the project Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\ProjectResource
     *
     * @apiResourceModel App\Models\Project
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to edit this project.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Project not found",
     *       "status": 404
     * }
     */
    public function edit(EditProjectRequest $request, string $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        if ($user->cannot('edit', $project)) {
            return $this->unauthorized('You are not authorized to edit this project.');
        }

        $imagePath = null;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            if ($project->image_path) {
                Storage::delete($project->image_path);
            }
            $imagePath = $request->file('image')->store('projects');
        }

        $project->update([
            'name' => $request->name,
            'image_path' => $imagePath,
        ]);

        return new ProjectResource($project);
    }

    /**
     * Delete project
     *
     * Delete the specified project.
     *
     * @authenticated
     *
     * @urlParam projectId string required the id of the project Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @response 200 scenario=Success {
     *       "message": "Project deleted successfully",
     *       "status": 200
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to delete this project.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Project not found",
     *       "status": 404
     * }
     */
    public function delete(Request $request, string $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        if ($user->cannot('delete', $project)) {
            return $this->unauthorized('You are not authorized to delete this project.');
        }

        $project->delete();

        return $this->successNoData('Project deleted successfully');
    }

    /**
     * View project stats
     *
     * View statistics for the specified project.
     *
     * @authenticated
     *
     * @urlParam projectId string required the id of the project Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @response 200 scenario=Success {
     *       "message": "message",
     *       "data": [
     *           "total_tasks": 50,
     *           "incomplete_tasks": 5,
     *           "completed_tasks": 30,
     *           "overdue_tasks": 5,
     *       ]
     *       "status": 200
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "message",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "message",
     *       "status": 404
     * }
     */
    public function viewProjectStats(Request $request, string $projectId)
    {
        $user = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return $this->notFound('Project not found');
        }

        if ($user->cannot('viewProjectStats', $project)) {
            return $this->unauthorized('You are not authorized to view stats for this project.');
        }

        $totalTasks = $project->tasks()->count();
        $incompleteTasks = $project->tasks()->where('status', '!=', 'done')->count();
        $completedTasks = $project->tasks()->where('status', 'done')->count();
        $overdueTasks = $project->tasks()->where('status', '!=', 'done')
            ->where('due_date', '<', now())->count();

        return $this->success('Project stats retrieved successfully', [
            'total_tasks' => $totalTasks,
            'incomplete_tasks' => $incompleteTasks,
            'completed_tasks' => $completedTasks,
            'overdue_tasks' => $overdueTasks,
        ]);
    }
}
