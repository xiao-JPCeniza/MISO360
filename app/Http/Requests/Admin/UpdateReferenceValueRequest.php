<?php

namespace App\Http\Requests\Admin;

use App\Models\ReferenceValue;
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
        $referenceValue = $this->resolveReferenceValue();

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

    /**
     * Resolve the ReferenceValue from the route. During Form Request validation the
     * route parameter may still be the raw ID, so we resolve the model manually.
     */
    private function resolveReferenceValue(): ReferenceValue
    {
        $value = $this->route('referenceValue');

        if ($value instanceof ReferenceValue) {
            return $value;
        }

        return ReferenceValue::query()->findOrFail($value);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a value name.',
            'name.unique' => 'This value already exists in the selected group.',
        ];
    }
}
