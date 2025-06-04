<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:50'],
            'image' => ['nullable', 'image', 'max:5000000', 'mimetypes:image/jpeg,image/png,image/webp,image/jpg,image/svg'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the project',
                'example' => 'My Project',
            ],
            'image' => [
                'description' => 'The image for the project',
            ],
        ];
    }
}
