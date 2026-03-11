<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Issue Report</title>
    <style>
        @page {
            size: 8in 13in;
            margin: 9mm 8mm 9mm 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.25;
        }

        .page {
            border: 1px solid #8d8d8d;
            padding: 10px 12px 12px;
        }

        .header-image {
            width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
        }

        .title {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .control-line {
            text-align: right;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .control-value {
            display: inline-block;
            min-width: 180px;
            border-bottom: 1px solid #222222;
            text-align: center;
            margin-left: 8px;
            font-weight: 700;
            color: #b80000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .form-table td,
        .form-table th {
            border: 1px solid #1f1f1f;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 10px;
        }

        .form-table .note {
            font-style: italic;
            font-size: 9px;
            color: #333333;
            background: #f7f7f7;
            padding: 3px 6px;
        }

        .label-cell {
            width: 27%;
            font-weight: 600;
            background: #f4f4f4;
        }

        .value-cell {
            width: 23%;
            color: #a00000;
            font-weight: 600;
        }

        .wide-value {
            color: #111111;
            font-weight: 500;
        }

        .options {
            font-size: 9px;
            line-height: 1.5;
        }

        .acknowledgement {
            font-size: 9px;
            line-height: 1.25;
            color: #111111;
        }

        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #222222;
            width: 230px;
            height: 10px;
            vertical-align: bottom;
            margin-left: 6px;
        }

        .option-item {
            display: inline-block;
            margin-right: 9px;
            white-space: nowrap;
        }

        .long-box {
            min-height: 64px;
        }

        .description-box {
            min-height: 74px;
            white-space: pre-wrap;
            word-break: break-word;
        }

        .attachment-list {
            margin-bottom: 6px;
            word-break: break-word;
        }

        .attachment-preview {
            display: inline-block;
            width: 120px;
            margin-right: 8px;
            margin-bottom: 6px;
            text-align: center;
            font-size: 8px;
            color: #444444;
        }

        .attachment-preview img {
            width: 100%;
            height: 70px;
            object-fit: cover;
            border: 1px solid #b5b5b5;
            margin-bottom: 3px;
        }

        .signatory-table {
            margin-top: 10px;
        }

        .signatory-table td,
        .signatory-table th {
            border: 1px solid #1f1f1f;
            padding: 4px 5px;
            font-size: 10px;
            vertical-align: top;
        }

        .signatory-label {
            width: 17%;
            font-weight: 600;
            background: #f4f4f4;
        }

        .signatory-name {
            width: 43%;
            color: #a00000;
            font-weight: 700;
        }

        .signatory-date {
            width: 20%;
            text-align: center;
        }

        .signatory-signature {
            width: 20%;
            text-align: center;
        }

        .muted {
            color: #666666;
            font-style: italic;
        }

        .footer-wrap {
            margin-top: 14px;
            text-align: center;
        }

        .footer-line {
            border-top: 1px solid #444444;
            margin: 0 20px 4px;
        }

        .footer-motto {
            font-style: italic;
            color: #5b5b5b;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .footer-image {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>
    <div class="page">
        @if (!empty($headerImage))
            <img src="{{ $headerImage }}" alt="MIS Office Header" class="header-image">
        @endif

        <div class="title">System Issue Report Form</div>
        <div class="control-line">
            Control Number:
            <span class="control-value">{{ $controlNumber !== '' ? $controlNumber : 'N/A' }}</span>
        </div>

        <table class="form-table">
            <tr>
                <td colspan="4" class="note">
                    For LGU internal use - Government Application Software
                </td>
            </tr>
            <tr>
                <td class="label-cell">Requesting Department:</td>
                <td class="value-cell">{{ $requestingDepartment !== '' ? $requestingDepartment : 'N/A' }}</td>
                <td class="label-cell">Date Filed:</td>
                <td class="value-cell">{{ $dateFiled !== '' ? $dateFiled : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Requesting Employee:</td>
                <td class="value-cell">{{ $requestingEmployee !== '' ? $requestingEmployee : 'N/A' }}</td>
                <td class="label-cell">Employee Contact No.:</td>
                <td class="value-cell">{{ $employeeContactNo !== '' ? $employeeContactNo : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Employee ID:</td>
                <td class="value-cell">{{ $employeeId !== '' ? $employeeId : 'N/A' }}</td>
                <td colspan="2" class="acknowledgement">
                    I understand that the information I have provided in this System Issue Report is accurate to the best of my knowledge. I acknowledge that the ICT Unit will review, verify, and prioritize the issue based on its severity and impact on operations. I also understand that misrepresentation or withholding of information may delay the diagnosis and resolution of the issue. I further acknowledge that the ICT Unit will process the report in accordance with established protocols, including assessment, prioritization, and scheduling of corrective actions. I understand that submission of this report does not guarantee immediate resolution, and that additional information may be requested if necessary.
                    <br><br>
                    <strong>Signature of Employee:</strong><span class="signature-line"></span>
                </td>
            </tr>
            <tr>
                <td class="label-cell">Nature of Appointment:</td>
                <td colspan="3" class="options">
                    @foreach ($natureOptions as $option)
                        <span class="option-item">
                            {{ $natureOfAppointment === $option ? '[x]' : '[ ]' }} {{ $option }}
                        </span>
                    @endforeach
                    <br>
                    Co-Terminus until Date:
                    <strong>{{ $coTerminusUntilDate !== '' ? $coTerminusUntilDate : 'N/A' }}</strong>
                    &nbsp; | &nbsp;
                    Others, specify:
                    <strong>{{ $natureOfAppointmentOthersSpecify !== '' ? $natureOfAppointmentOthersSpecify : 'N/A' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label-cell">Name of Software:</td>
                <td class="value-cell" colspan="3">{{ $nameOfSoftware !== '' ? $nameOfSoftware : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Type of Request:</td>
                <td colspan="3" class="options">
                    @foreach ($typeRequestOptions as $option)
                        <span class="option-item">
                            {{ in_array($option, $typeOfRequestSelections, true) ? '[x]' : '[ ]' }} {{ $option }}
                        </span>
                    @endforeach
                    <br>
                    Others, specify:
                    <strong>{{ $typeOfRequestOthersSpecify !== '' ? $typeOfRequestOthersSpecify : 'N/A' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label-cell">Error Summary/Title:<br>(Short Description of the problem)</td>
                <td colspan="3" class="wide-value long-box">{{ $errorSummaryTitle !== '' ? $errorSummaryTitle : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label-cell">Detailed Description:<br>Describe what happened, steps before the error appeared, and error message (if any)</td>
                <td colspan="3" class="description-box">
                    {!! $detailedDescription !== '' ? nl2br(e($detailedDescription)) : 'N/A' !!}
                </td>
            </tr>
        </table>

        <table class="signatory-table">
            <tr>
                <td class="signatory-label">Reported By:</td>
                <td class="signatory-name">{{ $reportedBy !== '' ? $reportedBy : 'N/A' }}</td>
                <td class="signatory-date">{{ $reportedByDate !== '' ? $reportedByDate : 'Date' }}</td>
                <td class="signatory-signature">{{ $reportedBySignature !== '' ? $reportedBySignature : 'Signature' }}</td>
            </tr>
            <tr>
                <td class="signatory-label">Accepted By:</td>
                <td class="signatory-name">{{ $acceptedBy !== '' ? $acceptedBy : 'N/A' }}</td>
                <td class="signatory-date">{{ $acceptedByDate !== '' ? $acceptedByDate : 'Date' }}</td>
                <td class="signatory-signature">{{ $acceptedBySignature !== '' ? $acceptedBySignature : 'Signature' }}</td>
            </tr>
            <tr>
                <td class="signatory-label">Evaluated By:</td>
                <td class="signatory-name">{{ $evaluatedBy !== '' ? $evaluatedBy : 'N/A' }}</td>
                <td class="signatory-date">{{ $evaluatedByDate !== '' ? $evaluatedByDate : 'Date' }}</td>
                <td class="signatory-signature">{{ $evaluatedBySignature !== '' ? $evaluatedBySignature : 'Signature' }}</td>
            </tr>
            <tr>
                <td class="signatory-label">Approved By:</td>
                <td class="signatory-name">{{ $approvedBy !== '' ? $approvedBy : 'N/A' }}</td>
                <td class="signatory-date">{{ $approvedByDate !== '' ? $approvedByDate : 'Date' }}</td>
                <td class="signatory-signature">{{ $approvedBySignature !== '' ? $approvedBySignature : 'Signature' }}</td>
            </tr>
        </table>

        <div class="footer-wrap">
            <div class="footer-line"></div>
            <div class="footer-motto">"Lungsod Manolo Fortich"</div>
            @if (!empty($footerImage))
                <img src="{{ $footerImage }}" alt="MIS Office Footer" class="footer-image">
            @endif
        </div>
    </div>
</body>
</html>
