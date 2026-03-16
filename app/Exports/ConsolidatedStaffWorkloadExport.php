<?php

namespace App\Exports;

use App\Models\TicketRequest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConsolidatedStaffWorkloadExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(
        private Collection $tickets
    ) {}

    public function collection(): Collection
    {
        // Sort by staff, then by status (active first), then by updated_at desc.
        return $this->tickets
            ->sortBy([
                fn (TicketRequest $t) => $this->resolveStaffName($t) ?: 'ZZZ',
                fn (TicketRequest $t) => $this->isActive($t) ? 0 : 1,
                fn (TicketRequest $t) => $t->updated_at?->timestamp ?? 0,
            ])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Admin / Staff',
            'Status',
            'Control / Ticket Number',
            'Requester Name',
            'Activity / Nature of Request',
            'Category Type',
            'Equipment Type',
            'Equipment Name',
            'Office / Location',
        ];
    }

    /**
     * @param  TicketRequest  $row
     * @return array<int, string|null>
     */
    public function map(mixed $row): array
    {
        assert($row instanceof TicketRequest);

        $staffName = $this->resolveStaffName($row);
        $requester = $row->requestedForUser?->name ?? $row->user?->name;
        $activity = $row->natureOfRequest?->name ?? $row->description;
        $category = $row->category?->name;
        $status = $row->status?->name ?? '—';

        $enrollment = $row->relationLoaded('enrollment') ? $row->enrollment : null;

        return [
            $staffName ?: 'Unassigned / General',
            $status,
            $row->control_ticket_number,
            $requester,
            $activity,
            $category,
            $enrollment?->equipment_type,
            $enrollment?->equipment_name,
            $enrollment?->location_office_division ?? $enrollment?->location_assigned_to,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        return [
            'A' => 26,
            'B' => 14,
            'C' => 22,
            'D' => 24,
            'E' => 32,
            'F' => 18,
            'G' => 18,
            'H' => 22,
            'I' => 26,
        ];
    }

    private function resolveStaffName(TicketRequest $ticket): ?string
    {
        $enrollment = $ticket->relationLoaded('enrollment') ? $ticket->enrollment : null;
        $fromEnrollment = $enrollment?->request_assigned_staff
            ?? $enrollment?->assignedAdmin?->name
            ?? null;

        if ($fromEnrollment) {
            return $fromEnrollment;
        }

        return $ticket->assignedStaff?->name;
    }

    private function isActive(TicketRequest $ticket): bool
    {
        $statusName = $ticket->status?->name;

        if (! $statusName) {
            return true;
        }

        return $statusName !== 'Completed';
    }
}
