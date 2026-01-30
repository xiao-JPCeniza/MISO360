<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReferenceValueRequest extends FormRequest
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
        $referenceValue = $this->route('referenceValue');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reference_values', 'name')
                    ->where('group_key', $referenceValue->group_key)
                    ->ignore($referenceValue),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a value name.',
            'name.unique' => 'This value already exists in the selected group.',
        ];
    }
}
