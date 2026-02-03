<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProfileSlideTextPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileSlideRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'text_position' => [
                'required',
                'string',
                Rule::in(array_column(ProfileSlideTextPosition::cases(), 'value')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'image.image' => 'The file must be an image (jpg, jpeg, png, or webp).',
            'image.mimes' => 'The image must be a jpg, jpeg, png, or webp file.',
            'image.max' => 'The image must not exceed 5 MB.',
            'title.required' => 'Please provide a title.',
            'text_position.required' => 'Please select text position (left or right).',
        ];
    }
}
