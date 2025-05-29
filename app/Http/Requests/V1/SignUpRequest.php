<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'name' => [
                'description' => 'The name of the user.',
                'example' => 'john doe',
            ],
            'email' => [
                'description' => 'The email address of the user. Must be unique.',
                'example' => 'johndoe@gmail.com',
            ],
            'password' => [
                'description' => 'A secure password for the user. Must be at least 8 characters long.',
                'example' => 'password',
            ],
        ];
    }
}
