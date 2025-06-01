<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class EditWorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:50'],
            'image' => ['nullable', 'image', 'max:5000000', 'mimetypes:image/jpeg,image/png,image/webp,image/jpg,image/svg'],
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
