<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'token' => [
                'description' => 'The password reset token',
                'example' => '1234567890abcdef1234567890abcdef1234567890abcdef1234567890abcdef',
            ],
            'email' => [
                'description' => 'The email address of the user',
                'example' => 'johndoe@gmail.com',
            ],
            'password' => [
                'description' => 'The new password for the user',
                'example' => 'newpassword123',
            ],
            'password_confirmation' => [
                'description' => 'The confirmation of the new password',
                'example' => 'newpassword123',
            ],
        ];
    }
}
