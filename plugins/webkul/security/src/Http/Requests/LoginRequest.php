<?php

namespace Webkul\Security\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'       => ['required', 'email'],
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'email' => [
                'description' => 'User email address.',
                'example'     => 'admin@example.com',
            ],
            'password' => [
                'description' => 'User password.',
                'example'     => 'password',
            ],
            'device_name' => [
                'description' => 'Optional mobile device name used to identify the current session.',
                'example'     => 'iPhone 15 Pro',
            ],
        ];
    }
}
