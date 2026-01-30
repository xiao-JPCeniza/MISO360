<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'acceptRepair' => ['required', 'accepted'],
            'comments' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
