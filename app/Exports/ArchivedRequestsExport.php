<?php

namespace App\Exports;

use App\Models\TicketRequest;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ArchivedRequestsExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Builder $query
    ) {}

    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Control / Ticket Number',
            'Requester Name',
            'Office Designation',
            'Nature of Request',
            'Category',
            'Status',
            'Remarks / Comments',
            'Action Taken',
            'Date Filed (with time)',
            'Date Completed / Done (with time)',
            'Assigned IT Staff',
            'QR Asset – Device / Equipment Name',
            'QR Asset – Type',
            'QR Asset – Brand / Model',
            'QR Asset – Serial Number',
            'QR Asset – Property / Asset Tag',
            'QR Asset – Office / Location',
        ];
    }

    /**
     * @param  TicketRequest  $row
     * @return array<int, string|int|null>
     */
    public function map(mixed $row): array
    {
        assert($row instanceof TicketRequest);
        $enrollment = $row->relationLoaded('enrollment') ? $row->enrollment : null;
        $assignedStaff = $enrollment?->request_assigned_staff
            ?? $enrollment?->assignedAdmin?->name
            ?? null;
        $actionTaken = $enrollment?->request_action_taken ?? null;
        $dateFiled = $row->created_at
            ? Carbon::parse($row->created_at)->format('Y-m-d H:i')
            : '';
        $dateCompleted = $row->updated_at
            ? Carbon::parse($row->updated_at)->format('Y-m-d H:i')
            : '';

        return [
            $row->control_ticket_number,
            $row->requestedForUser?->name ?? $row->user?->name,
            $row->officeDesignation?->name,
            $row->natureOfRequest?->name,
            $row->category?->name,
            $row->status?->name,
            $row->description,
            $actionTaken,
            $dateFiled,
            $dateCompleted,
            $assignedStaff,
            $enrollment?->equipment_name,
            $enrollment?->equipment_type,
            trim(implode(' ', array_filter([$enrollment?->brand, $enrollment?->model]))),
            $enrollment?->serial_number,
            $enrollment?->asset_tag,
            $enrollment?->location_office_division ?? $enrollment?->location_assigned_to,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 24,
            'C' => 20,
            'D' => 20,
            'E' => 14,
            'F' => 14,
            'G' => 36,
            'H' => 28,
            'I' => 20,
            'J' => 24,
            'K' => 22,
            'L' => 28,
            'M' => 14,
            'N' => 20,
            'O' => 16,
            'P' => 18,
            'Q' => 22,
        ];
    }
}
