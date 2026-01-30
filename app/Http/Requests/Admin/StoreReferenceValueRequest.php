<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReferenceValueGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferenceValueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_key' => [
                'required',
                'string',
                Rule::in(ReferenceValueGroup::values()),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reference_values', 'name')
                    ->where('group_key', $this->input('group_key')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'group_key.required' => 'Please select a reference group.',
            'group_key.in' => 'Please select a valid reference group.',
            'name.required' => 'Please provide a value name.',
            'name.unique' => 'This value already exists in the selected group.',
        ];
    }
}
