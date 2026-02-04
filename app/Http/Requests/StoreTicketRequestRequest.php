<?php

namespace App\Http\Requests;

use App\Enums\ReferenceValueGroup;
use App\Models\NatureOfRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'systemDevelopmentSurveyFormAttachments' => ['nullable', 'array'],
            'systemDevelopmentSurveyFormAttachments.*' => [
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp',
                'max:10240',
            ],
            'systemDevelopmentSurvey' => ['nullable', 'array'],
            'systemDevelopmentSurvey.titleOfProposedSystem' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.targetCompletion' => ['nullable', 'date'],
            'systemDevelopmentSurvey.assignedSystemsEngineer' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.officeEndUser' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.servicesOrFeatures' => ['nullable', 'array'],
            'systemDevelopmentSurvey.servicesOrFeatures.*.serviceFeature' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.servicesOrFeatures.*.specifics' => ['nullable', 'string', 'max:1000'],
            'systemDevelopmentSurvey.servicesOrFeatures.*.accessibility' => ['nullable', 'string', 'in:Public View,Admin/User View Only'],
            'systemDevelopmentSurvey.dataGathering' => ['nullable', 'array'],
            'systemDevelopmentSurvey.dataGathering.*.dataRequired' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.dataGathering.*.specifics' => ['nullable', 'string', 'max:1000'],
            'systemDevelopmentSurvey.forms' => ['nullable', 'array'],
            'systemDevelopmentSurvey.forms.*.titleOfForm' => ['nullable', 'string', 'max:255'],
            'systemDevelopmentSurvey.forms.*.description' => ['nullable', 'string', 'max:1000'],
            'systemDevelopmentSurvey.flowSop' => ['nullable', 'string', 'max:3000'],
            'systemDevelopmentSurvey.headOfOffice' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->isSystemDevelopment()) {
                return;
            }

            $survey = $this->input('systemDevelopmentSurvey');

            if (! is_array($survey)) {
                $validator->errors()->add(
                    'systemDevelopmentSurvey',
                    'Systems Development Survey Form is required for System Development requests.',
                );

                return;
            }

            $this->requireSurveyField($validator, $survey, 'titleOfProposedSystem', 'Title of Proposed System is required.');
            $this->requireSurveyField($validator, $survey, 'flowSop', 'Flow (SOP) is required.');
            $this->requireSurveyField($validator, $survey, 'headOfOffice', 'Head of Office is required.');

            $services = $survey['servicesOrFeatures'] ?? null;
            if (! is_array($services) || count($services) === 0) {
                $validator->errors()->add(
                    'systemDevelopmentSurvey.servicesOrFeatures',
                    'At least one service/feature entry is required.',
                );
            } else {
                foreach ($services as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $this->requireSurveyRowField(
                        $validator,
                        $row,
                        "systemDevelopmentSurvey.servicesOrFeatures.{$index}.serviceFeature",
                        'serviceFeature',
                        'Service/Feature is required.',
                    );
                    $this->requireSurveyRowField(
                        $validator,
                        $row,
                        "systemDevelopmentSurvey.servicesOrFeatures.{$index}.specifics",
                        'specifics',
                        'Specifics is required.',
                    );
                    $this->requireSurveyRowField(
                        $validator,
                        $row,
                        "systemDevelopmentSurvey.servicesOrFeatures.{$index}.accessibility",
                        'accessibility',
                        'Accessibility is required.',
                    );
                }
            }

            $data = $survey['dataGathering'] ?? null;
            if (! is_array($data) || count($data) === 0) {
                $validator->errors()->add(
                    'systemDevelopmentSurvey.dataGathering',
                    'At least one data gathering entry is required.',
                );
            } else {
                foreach ($data as $index => $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $this->requireSurveyRowField(
                        $validator,
                        $row,
                        "systemDevelopmentSurvey.dataGathering.{$index}.dataRequired",
                        'dataRequired',
                        'Data required is required.',
                    );
                    $this->requireSurveyRowField(
                        $validator,
                        $row,
                        "systemDevelopmentSurvey.dataGathering.{$index}.specifics",
                        'specifics',
                        'Specifics is required.',
                    );
                }
            }
        });
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

    private function isSystemDevelopment(): bool
    {
        $natureId = $this->integer('natureOfRequestId');
        if (! $natureId) {
            return false;
        }

        $name = NatureOfRequest::query()
            ->whereKey($natureId)
            ->value('name');

        return is_string($name) && strtolower(trim($name)) === 'system development';
    }

    /**
     * @param  array<string, mixed>  $survey
     */
    private function requireSurveyField(Validator $validator, array $survey, string $key, string $message): void
    {
        $value = $survey[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            $validator->errors()->add("systemDevelopmentSurvey.{$key}", $message);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function requireSurveyRowField(
        Validator $validator,
        array $row,
        string $errorKey,
        string $key,
        string $message,
    ): void {
        $value = $row[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            $validator->errors()->add($errorKey, $message);
        }
    }
}
