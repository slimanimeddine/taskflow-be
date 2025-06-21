<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\ApiController;
use App\Http\Requests\V1\BulkEditTasksPositionsRequest;
use App\Http\Requests\V1\CreateTaskRequest;
use App\Http\Requests\V1\EditTaskRequest;
use App\Http\Resources\V1\TaskResource;
use App\Models\Member;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Sorts\AssigneeNameSort;
use App\Sorts\ProjectNameSort;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\Enums\FilterOperator;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Tasks
 */
class TaskController extends ApiController
{
    /**
     * Create task
     *
     * Create a new task in a project within a workspace.
     *
     * @authenticated
     *
     * @apiResource scenario=Success App\Http\Resources\V1\TaskResource
     *
     * @apiResourceModel App\Models\Task
     *
     * @response 400 scenario="Assignee is not a member" {
     *       "message": "Assignee is not a member of the workspace.",
     *       "status": 400
     * }
     * @response 400 scenario="Project does not belong to workspace" {
     *       "message": "Project does not belong to the specified workspace.",
     *       "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to create a task in this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     * @response 404 scenario="Project Not Found" {
     *       "message": "Project not found",
     *       "status": 404
     * }
     * @response 404 scenario="Assignee Not Found" {
     *       "message": "Assignee not found",
     *       "status": 404
     * }
     */
    public function create(CreateTaskRequest $request)
    {
        $user = $request->user();
        $workspace = Workspace::find($request->workspace_id);
        $project = Project::find($request->project_id);
        $assignee = User::find($request->assignee_id);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if (!$project) {
            return $this->notFound('Project not found');
        }

        if (!$assignee) {
            return $this->notFound('Assignee not found');
        }

        $member = Member::where('user_id', $assignee->id)
            ->where('workspace_id', $workspace->id)
            ->exists();

        if (!$member) {
            return $this->error('Assignee is not a member of the workspace.', 400);
        }

        if ($user->cannot('createTask', $workspace)) {
            return $this->unauthorized('You are not authorized to create a task in this workspace.');
        }

        if ($workspace->id !== $project->workspace_id) {
            return $this->error('Project does not belong to the specified workspace.', 400);
        }

        $highestPosition = Task::where('workspace_id', $project->workspace_id)
            ->where('status', $request)
            ->max('position');

        $task = Task::create([
            'name' => $request->name,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'position' => $highestPosition !== null ? $highestPosition + 1 : 0,
            'workspace_id' => $workspace->id,
            'project_id' => $project->id,
            'assignee_id' => $assignee->id,
        ]);

        return new TaskResource($task);
    }

    /**
     * List tasks
     *
     * List all tasks in a workspace, with optional filters for project, assignee, and status.
     *
     * @authenticated
     *
     * @queryParam filter[name] string task name. Example: Design mockup
     * @queryParam filter[due_date] string task due date. Example: 2023-10-01
     * @queryParam filter[position] integer task position. Example: 1000
     * @queryParam filter[status] string task status. Enum:backlog,todo,in_progress,in_review,done. Example: in_progress
     * @queryParam filter[workspace] string task workspace id. Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     * @queryParam filter[project] string task project id. Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     * @queryParam filter[assignee] string task assignee id. Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     * @queryParam sort string sort tasks. Enum:name,-name,due_date,-due_date,status,-status,project,-project,assignee,-assignee. Example: -due_date
     * @queryParam paginate integer required whether to paginate the results. Enum:1,0. Example: 1
     * @queryParam page integer the page number to retrieve. Example: 1
     *
     * @responseFile status=200 scenario="Paginated" storage/responses/tasks.paginated.json
     * @responseFile status=200 scenario="Not Paginated" storage/responses/tasks.not_paginated.json
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to view tasks in this workspace.",
     *       "status": 403
     * }
     * @response 404 scenario="Workspace Not Found" {
     *       "message": "Workspace not found",
     *       "status": 404
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $queryParams = $request->query();

        $workspaceId = data_get($queryParams, 'filter.workspace');

        $workspace = Workspace::find($workspaceId);

        if (!$workspace) {
            return $this->notFound('Workspace not found');
        }

        if ($user->cannot('viewTasks', $workspace)) {
            return $this->unauthorized('You are not authorized to view tasks in this workspace.');
        }

        $paginate = data_get($queryParams, 'paginate');

        $query = QueryBuilder::for(Task::with(['assignee', 'project']))
            ->allowedFilters([
                'name',
                AllowedFilter::scope('due_date'),
                AllowedFilter::operator('position', FilterOperator::EQUAL),
                AllowedFilter::exact('status'),
                AllowedFilter::exact('workspace', 'workspace_id'),
                AllowedFilter::exact('project', 'project_id'),
                AllowedFilter::exact('assignee', 'assignee_id'),
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'name',
                'due_date',
                'status',
                AllowedSort::custom('project', new ProjectNameSort),
                AllowedSort::custom('assignee', new AssigneeNameSort),
            ]);

        if ($paginate === '1') {
            $tasks = $query->paginate(10);
        } else {
            $tasks = $query->get();
        }

        return TaskResource::collection($tasks);
    }

    /**
     * Delete task
     *
     * Delete the specified task.
     *
     * @authenticated
     *
     * @urlParam taskId string required the id of the task Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @response 200 scenario=Success {
     *       "message": "Task deleted successfully",
     *       "status": 200
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to delete this task.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Task not found",
     *       "status": 404
     * }
     */
    public function delete(Request $request, string $taskId)
    {
        $user = $request->user();
        $task = Task::find($taskId);

        if (!$task) {
            return $this->notFound('Task not found');
        }

        if ($user->cannot('delete', $task)) {
            return $this->unauthorized('You are not authorized to delete this task.');
        }

        $task->delete();

        return $this->successNoData('Task deleted successfully');
    }

    /**
     * Edit task
     *
     * Edit the specified task.
     *
     * @authenticated
     *
     * @urlParam taskId string required the id of the task Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\TaskResource
     *
     * @apiResourceModel App\Models\Task
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to edit this task.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Task not found",
     *       "status": 404
     * }
     */
    public function edit(EditTaskRequest $request, string $taskId)
    {
        $user = $request->user();
        $task = Task::find($taskId);

        if (!$task) {
            return $this->notFound('Task not found');
        }

        if ($user->cannot('edit', $task)) {
            return $this->unauthorized('You are not authorized to edit this task.');
        }

        $task->update($request->validated());

        return new TaskResource($task);
    }

    /**
     * Show task
     *
     * Show the specified task.
     *
     * @authenticated
     *
     * @urlParam taskId string required the id of the task Example: 01972a18-9d62-72ff-8a2b-d55e57b34d1c
     *
     * @apiResource scenario=Success App\Http\Resources\V1\TaskResource
     *
     * @apiResourceModel App\Models\Task
     *
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     * @response 403 scenario=Unauthorized {
     *       "message": "You are not authorized to view this task.",
     *       "status": 403
     * }
     * @response 404 scenario="Not Found" {
     *       "message": "Task not found",
     *       "status": 404
     * }
     */
    public function show(Request $request, string $taskId)
    {
        $user = $request->user();
        $task = Task::find($taskId);

        if (!$task) {
            return $this->notFound('Task not found');
        }

        if ($user->cannot('show', $task)) {
            return $this->unauthorized('You are not authorized to view this task.');
        }

        return new TaskResource($task);
    }

    /**
     * Bulk edit tasks positions
     *
     * Bulk edit tasks positions in a workspace.
     *
     * @authenticated
     *
     * @response 200 scenario=Success {
     *       "message": "Tasks positions updated successfully",
     *       "status": 200
     * }
     * @response 400 scenario="No tasks provided" {
     *       "message": "No tasks provided for bulk edit.",
     *       "status": 400
     * }
     * @response 401 scenario=Unauthenticated {
     *       "message": "Unauthenticated",
     * }
     */
    public function bulkEditPositions(BulkEditTasksPositionsRequest $request)
    {
        $tasks = $request->validated('tasks');

        $totalTasks = count($tasks);

        if ($totalTasks === 0) {
            return $this->error('No tasks provided for bulk edit.', 400);
        }

        Task::upsert($tasks, uniqueBy: ['id'], update: ['position']);

        return $this->successNoData('Tasks updated successfully');
    }
}
