<?php

namespace App\Http\Requests;

use App\Enums\ReferenceValueGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequestRequest extends FormRequest
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
        $isAdmin = $this->user()?->isAdmin() ?? false;
        $officeId = $this->input('officeDesignationId');

        $officeRules = [
            'nullable',
            'integer',
            Rule::exists('reference_values', 'id')->where('group_key', ReferenceValueGroup::OfficeDesignation->value)
                ->where('is_active', true),
        ];
        $requestedUserRules = [
            'nullable',
            'integer',
        ];

        if ($isAdmin) {
            array_unshift($officeRules, 'required');
            array_unshift($requestedUserRules, 'required');
            $requestedUserRules[] = Rule::exists('users', 'id')->where(
                fn ($query) => $query
                    ->where('office_designation_id', $officeId)
                    ->where('is_active', true),
            );
        }

        return [
            'controlTicketNumber' => [
                'required',
                'string',
                'max:25',
                'regex:/^CTN-\\d{8}-\\d{4}$/',
            ],
            'natureOfRequestId' => [
                'required',
                'integer',
                Rule::exists('nature_of_requests', 'id')->where('is_active', true),
            ],
            'officeDesignationId' => $officeRules,
            'requestedForUserId' => $requestedUserRules,
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'hasQrCode' => ['required', 'boolean'],
            'qrCodeNumber' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf($this->boolean('hasQrCode')),
                'regex:/^MIS-UID-\\d{5}$/',
            ],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,mp4,mov',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'natureOfRequestId.required' => 'Please select a nature of request.',
            'officeDesignationId.required' => 'Please select an office designation.',
            'requestedForUserId.required' => 'Please select a user for this request.',
            'requestedForUserId.exists' => 'Please select a valid user for the selected office.',
            'qrCodeNumber.required' => 'QR Code Number is required when a QR code is provided.',
            'qrCodeNumber.regex' => 'QR Code Number must match MIS-UID-00000 format.',
            'description.min' => 'Description must be at least 10 characters.',
            'description.max' => 'Description may not exceed 1000 characters.',
        ];
    }
}
