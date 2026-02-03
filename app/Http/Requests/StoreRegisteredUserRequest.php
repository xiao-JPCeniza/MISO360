<?php

namespace App\Http\Requests;

use App\Enums\ReferenceValueGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegisteredUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'position_title' => ['required', 'string', 'max:255'],
            'office_designation_id' => [
                'required',
                'integer',
                Rule::exists('reference_values', 'id')
                    ->where('group_key', ReferenceValueGroup::OfficeDesignation->value)
                    ->where('is_active', true),
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your full name.',
            'position_title.required' => 'Please provide your position or designation.',
            'office_designation_id.required' => 'Please select your office.',
            'office_designation_id.exists' => 'The selected office is not available.',
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Please create a password.',
        ];
    }
}
