<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class BulkEditTasksPositionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tasks' => ['required', 'array'],
            'tasks.*.id' => ['required', 'exists:tasks,id'],
            'tasks.*.position' => ['required', 'integer'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'tasks' => [
                'description' => 'An array of tasks to be edited',
            ],
            'tasks.*.id' => [
                'description' => 'The ID of the task to be edited',
                'example' => '01972a18-9d62-72ff-8a2b-d55e57b34d1c',
            ],
            'tasks.*.position' => [
                'description' => 'The position of the task in the list',
                'example' => 10,
            ]
        ];
    }
}