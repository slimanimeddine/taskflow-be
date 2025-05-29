<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class DeleteUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'current_password:sanctum'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'password' => [
                'description' => 'The password of the user attempting to delete their account.',
                'example' => 'password',
            ],
        ];
    }
}
