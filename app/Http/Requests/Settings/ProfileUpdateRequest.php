<?php

namespace App\Http\Requests\Settings;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
        ];

        // Only validate role if the user can manage roles
        if ($this->user() && $this->user()->canManageRoles()) {
            $rules['role'] = ['sometimes', 'required', 'string', 'in:'.implode(',', Role::values())];
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Only include role in validation data if user can manage roles
        if (! $this->user() || ! $this->user()->canManageRoles()) {
            $this->request->remove('role');
        }
    }
}
