<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateWorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the workspace',
                'example' => 'My Workspace',
            ],
            'image' => [
                'description' => 'The image for the workspace',
            ],
        ];
    }
}
