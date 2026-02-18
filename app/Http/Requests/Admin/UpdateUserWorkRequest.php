<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReferenceValueGroup;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserWorkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User && $this->user()?->can('update', $target);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'office_designation_id' => [
                'required',
                'integer',
                Rule::exists('reference_values', 'id')
                    ->where('group_key', ReferenceValueGroup::OfficeDesignation->value)
                    ->where('is_active', true),
            ],
            'position_title' => ['required', 'string', 'max:255'],
        ];
    }
}
