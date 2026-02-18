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
     * Normalize file inputs to arrays so validation and controller handle single or multiple files.
     */
    protected function prepareForValidation(): void
    {
        $files = $this->allFiles();
        $normalized = [];

        if (isset($files['attachments'])) {
            $normalized['attachments'] = is_array($files['attachments'])
                ? array_values($files['attachments'])
                : [$files['attachments']];
        }
        if (isset($files['systemIssueReportAttachments'])) {
            $normalized['systemIssueReportAttachments'] = is_array($files['systemIssueReportAttachments'])
                ? array_values($files['systemIssueReportAttachments'])
                : [$files['systemIssueReportAttachments']];
        }
        if (isset($files['systemDevelopmentSurveyFormAttachments'])) {
            $att = $files['systemDevelopmentSurveyFormAttachments'];
            $normalized['systemDevelopmentSurveyFormAttachments'] = is_array($att)
                ? $att
                : [0 => $att];
        }

        if ($normalized !== []) {
            $this->merge($normalized);
            foreach ($normalized as $key => $value) {
                $this->files->set($key, $value);
            }
        }
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

            'systemChangeRequestForm' => ['nullable', 'array'],
            'systemChangeRequestForm.controlNumber' => ['nullable', 'string', 'max:25'],
            'systemChangeRequestForm.date' => ['nullable', 'date'],
            'systemChangeRequestForm.officeDivision' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.requestedByName' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.nameOfSoftware' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.typeOfRequest' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.descriptionOfRequest' => ['nullable', 'string', 'max:1000'],
            'systemChangeRequestForm.purposeObjectiveOfModification' => ['nullable', 'string', 'max:3000'],
            'systemChangeRequestForm.detailedDescriptionOfRequestedChange' => ['nullable', 'string', 'max:5000'],
            'systemChangeRequestForm.evaluatedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.approvedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.notedBy' => ['nullable', 'string', 'max:255'],
            'systemChangeRequestForm.remarks' => ['nullable', 'string', 'max:2000'],

            'systemIssueReport' => ['nullable', 'array'],
            'systemIssueReport.controlNumber' => ['nullable', 'string', 'max:25'],
            'systemIssueReport.requestingDepartment' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.dateFiled' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.requestingEmployee' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.employeeContactNo' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.employeeId' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.signatureOfEmployee' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.natureOfAppointment' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.natureOfAppointmentOthersSpecify' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.coTerminusUntilDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.nameOfSoftware' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.typeOfRequest' => ['nullable', 'array'],
            'systemIssueReport.typeOfRequest.*' => ['nullable', 'string', 'max:100'],
            'systemIssueReport.typeOfRequestOthersSpecify' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.errorSummaryTitle' => ['nullable', 'string', 'max:500'],
            'systemIssueReport.detailedDescription' => ['nullable', 'string', 'max:5000'],
            'systemIssueReport.reportedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.reportedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.reportedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.acceptedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.acceptedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.acceptedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.evaluatedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.evaluatedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.evaluatedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.approvedBy' => ['nullable', 'string', 'max:255'],
            'systemIssueReport.approvedByDate' => ['nullable', 'string', 'max:50'],
            'systemIssueReport.approvedBySignature' => ['nullable', 'string', 'max:255'],
            'systemIssueReportAttachments' => ['nullable', 'array'],
            'systemIssueReportAttachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:10240',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->isSystemDevelopment()) {
                if ($this->isSystemModification()) {
                    $this->validateSystemChangeRequestForm($validator);
                }
                if ($this->isSystemErrorBugReport()) {
                    $this->validateSystemIssueReport($validator);
                }

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

    private function isSystemModification(): bool
    {
        $natureId = $this->integer('natureOfRequestId');
        if (! $natureId) {
            return false;
        }

        $name = NatureOfRequest::query()
            ->whereKey($natureId)
            ->value('name');

        return is_string($name) && strtolower(trim($name)) === 'system modification';
    }

    private function isSystemErrorBugReport(): bool
    {
        $natureId = $this->integer('natureOfRequestId');
        if (! $natureId) {
            return false;
        }

        $name = NatureOfRequest::query()
            ->whereKey($natureId)
            ->value('name');

        return is_string($name) && strtolower(trim($name)) === 'system error / bug report';
    }

    private function validateSystemIssueReport(Validator $validator): void
    {
        $form = $this->input('systemIssueReport');

        if (! is_array($form)) {
            $validator->errors()->add(
                'systemIssueReport',
                'System Issue Report Form is required for System Error / Bug Report requests.',
            );

            return;
        }

        $this->requireIssueReportField($validator, $form, 'controlNumber', 'Control Number is required.');
        $this->requireIssueReportField($validator, $form, 'requestingDepartment', 'Requesting Department is required.');
        $this->requireIssueReportField($validator, $form, 'dateFiled', 'Date Filed is required.');
        $this->requireIssueReportField($validator, $form, 'requestingEmployee', 'Requesting Employee is required.');
        $this->requireIssueReportField($validator, $form, 'employeeContactNo', 'Employee Contact No. is required.');
        $this->requireIssueReportField($validator, $form, 'employeeId', 'Employee ID is required.');
        $this->requireIssueReportField($validator, $form, 'signatureOfEmployee', 'Signature of Employee is required.');
        $this->requireIssueReportField($validator, $form, 'natureOfAppointment', 'Nature of Appointment is required.');
        $this->requireIssueReportField($validator, $form, 'nameOfSoftware', 'Name of Software is required.');
        $typeOfRequest = $form['typeOfRequest'] ?? null;
        if (! is_array($typeOfRequest) || count($typeOfRequest) === 0) {
            $validator->errors()->add(
                'systemIssueReport.typeOfRequest',
                'At least one Type of Request must be selected.',
            );
        }
        $this->requireIssueReportField($validator, $form, 'errorSummaryTitle', 'Error Summary/Title is required.');
        $this->requireIssueReportField($validator, $form, 'detailedDescription', 'Detailed Description is required.');
        $detailed = $form['detailedDescription'] ?? '';
        if (is_string($detailed) && strlen(trim($detailed)) > 0 && strlen(trim($detailed)) < 10) {
            $validator->errors()->add(
                'systemIssueReport.detailedDescription',
                'Detailed Description must be at least 10 characters.',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $form
     */
    private function requireIssueReportField(Validator $validator, array $form, string $key, string $message): void
    {
        $value = $form[$key] ?? null;
        if (! is_string($value) || trim($value) === '') {
            $validator->errors()->add("systemIssueReport.{$key}", $message);
        }
    }

    private function validateSystemChangeRequestForm(Validator $validator): void
    {
        $form = $this->input('systemChangeRequestForm');

        if (! is_array($form)) {
            $validator->errors()->add(
                'systemChangeRequestForm',
                'System Change Request Form is required for System Modification requests.',
            );

            return;
        }

        $this->requireScrField($validator, $form, 'controlNumber', 'Control Number is required.');
        $this->requireScrField($validator, $form, 'date', 'Date is required.');
        $this->requireScrField($validator, $form, 'officeDivision', 'Office/Division is required.');
        $this->requireScrField($validator, $form, 'requestedByName', 'Requested by (Name) is required.');
        $this->requireScrField($validator, $form, 'nameOfSoftware', 'Name of Software is required.');
        $this->requireScrField($validator, $form, 'typeOfRequest', 'Type of Request is required.');
        $this->requireScrField($validator, $form, 'descriptionOfRequest', 'Description of Request is required.');
        $this->requireScrField(
            $validator,
            $form,
            'purposeObjectiveOfModification',
            'Purpose/Objective of Modification is required.',
        );
        $this->requireScrField(
            $validator,
            $form,
            'detailedDescriptionOfRequestedChange',
            'Detailed Description of the Requested Change is required.',
        );
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

    /**
     * @param  array<string, mixed>  $form
     */
    private function requireScrField(Validator $validator, array $form, string $key, string $message): void
    {
        $value = $form[$key] ?? null;

        if ($key === 'date') {
            if (! is_string($value) || trim($value) === '') {
                $validator->errors()->add("systemChangeRequestForm.{$key}", $message);
            }

            return;
        }

        if (! is_string($value) || trim($value) === '') {
            $validator->errors()->add("systemChangeRequestForm.{$key}", $message);
        }
    }
}
