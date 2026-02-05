<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ExportArchivedRequestsRequest extends FormRequest
{
    private const MAX_DAYS_RANGE = 365;

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
            'archive_search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $from = $this->date('date_from');
            $to = $this->date('date_to');
            if ($from && $to && $from->diffInDays($to) > self::MAX_DAYS_RANGE) {
                $validator->errors()->add(
                    'date_to',
                    'Date range must not exceed '.self::MAX_DAYS_RANGE.' days.'
                );
            }
        });
    }
}
