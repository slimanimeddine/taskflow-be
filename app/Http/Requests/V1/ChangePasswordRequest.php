<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'current_password' => [
                'description' => 'The current password of the user.',
                'example' => 'password',
            ],
            'new_password' => [
                'description' => 'The new password of the user.',
                'example' => 'new_password',
            ],
            'new_password_confirmation' => [
                'description' => 'The new password confirmation of the user.',
                'example' => 'new_password',
            ],
        ];
    }
}
