<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:50'],
            'description' => ['nullable', 'string'],
            'due_date' => ['required', 'date'],
            'status' => ['required', 'string', 'in:backlog,todo,in_progress,in_review,done'],
            'workspace_id' => ['required', 'uuid', 'exists:workspaces,id'],
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'assignee_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the task',
                'example' => 'My Task',
            ],
            'description' => [
                'description' => 'A detailed description of the task',
                'example' => 'This is a detailed description of the task.',
            ],
            'due_date' => [
                'description' => 'The due date for the task',
                'example' => '2023-10-01T12:00:00Z',
            ],
            'status' => [
                'description' => 'The current status of the task',
                'example' => 'todo',
            ],
            'workspace_id' => [
                'description' => 'The ID of the workspace to which the task belongs',
                'example' => '123e4567-e89b-12d3-a456-426614174000',
            ],
            'project_id' => [
                'description' => 'The ID of the project to which the task belongs',
                'example' => '123e4567-e89b-12d3-a456-426614174001',
            ],
            'assignee_id' => [
                'description' => 'The ID of the user assigned to the task',
                'example' => '123e4567-e89b-12d3-a456-426614174002',
            ],
        ];
    }
}
