<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string', 'exists:workspaces,invite_code'],
        ];
    }

    public function bodyParameters()
    {
        return [
            'invite_code' => [
                'description' => 'The invite code for the workspace',
                'example' => 'QWErty1245',
            ]
        ];
    }
}