<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'verification' => $this->input('verification', 'all'),
            'search' => $this->input('search', ''),
        ]);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'verification' => ['required', 'string', Rule::in(['all', 'verified', 'unverified'])],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
