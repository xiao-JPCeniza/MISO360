<?php

namespace App\Exports;

use App\Models\NatureOfRequest;
use App\Models\TicketRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class NatureOfRequestsMonthlySummaryExport implements FromArray, WithColumnWidths, WithEvents
{
    use Exportable;

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    /**
     * @var Collection<int, array{key: string, label: string, monthNumber: int}>
     */
    private Collection $months;

    /**
     * @var Collection<int, NatureOfRequest>
     */
    private Collection $natures;

    /**
     * @var array<int, array<string, int>> counts[natureId][monthKey] = total
     */
    private array $counts;

    private int $tableStartRow = 2;

    private int $dataStartRow = 3;

    private int $tableEndRow = 3;

    public function __construct(
        CarbonImmutable $from,
        CarbonImmutable $to,
        private readonly User $preparedBy,
        private readonly ?User $reviewedBy = null,
        private readonly string $typeLabel = 'Internal',
    ) {
        $this->from = $from->startOfYear();
        $this->to = $to->endOfYear();

        $this->months = $this->buildMonths($this->from);
        $this->natures = NatureOfRequest::query()->orderBy('name')->get(['id', 'name']);
        $this->counts = $this->loadCounts($this->months, $this->from, $this->to);

        $this->tableEndRow = $this->dataStartRow + $this->natures->count();
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    public function array(): array
    {
        $rows = [];

        $headerRow = [];
        $headerRow[] = '';
        $headerRow[] = 'Nature of Request';
        foreach ($this->months as $m) {
            $headerRow[] = $m['label'];
        }
        $headerRow[] = 'Total';
        $rows[] = $headerRow;

        $monthlyTotals = array_fill(0, $this->months->count(), 0);
        $grandTotal = 0;

        foreach ($this->natures as $nature) {
            $row = [$this->typeLabel, $nature->name];
            $rowTotal = 0;

            foreach ($this->months as $idx => $m) {
                $value = (int) ($this->counts[$nature->id][$m['key']] ?? 0);
                $row[] = $value;
                $rowTotal += $value;
                $monthlyTotals[$idx] += $value;
                $grandTotal += $value;
            }

            $row[] = $rowTotal;
            $rows[] = $row;
        }

        $totalRow = ['', 'TOTAL'];
        foreach ($monthlyTotals as $t) {
            $totalRow[] = $t;
        }
        $totalRow[] = $grandTotal;
        $rows[] = $totalRow;

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        $widths = [
            'A' => 12,
            'B' => 56,
        ];

        $monthCols = $this->months->count() + 1; // + Total
        for ($i = 0; $i < $monthCols; $i++) {
            $col = Coordinate::stringFromColumnIndex(3 + $i); // C...
            $widths[$col] = 12;
        }

        return $widths;
    }

    /**
     * @return array<class-string, callable>
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $lastColumnIndex = 3 + $this->months->count(); // months + total
                $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);

                $sheet->insertNewRowBefore(1, 1);

                $sheet->mergeCells('A1:B1');
                $sheet->setCellValue('A1', 'MISO');
                $sheet->mergeCells("C1:{$lastColumn}1");
                $sheet->setCellValue('C1', 'Number of Transactions or Instances');

                $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => Color::COLOR_WHITE],
                        'size' => 12,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['argb' => '1E3A8A'],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                $sheet->getStyle("A2:{$lastColumn}2")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => Color::COLOR_WHITE],
                        'size' => 11,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['argb' => '2563EB'],
                    ],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(20);

                $tableStartRow = 1;
                $tableEndRow = $this->tableEndRow;

                $sheet->getStyle("A{$tableStartRow}:{$lastColumn}{$tableEndRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('000000'));

                $sheet->getStyle("A3:A{$tableEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("B3:B{$tableEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle("C3:{$lastColumn}{$tableEndRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $totalRowIndex = $tableEndRow;
                $sheet->getStyle("A{$totalRowIndex}:{$lastColumn}{$totalRowIndex}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['argb' => 'E5E7EB'],
                    ],
                ]);

                $sheet->freezePane('C3');

                $preparedByName = mb_strtoupper($this->preparedBy->name);
                $preparedByTitle = $this->preparedBy->position_title ?: $this->preparedBy->role->displayName();

                $reviewedByName = $this->reviewedBy?->name ? mb_strtoupper($this->reviewedBy->name) : '';
                $reviewedByTitle = $this->reviewedBy?->position_title ?? '';

                $footerStart = $tableEndRow + 3;

                $sheet->setCellValue("A{$footerStart}", 'Prepared by:');
                $sheet->setCellValue('A'.($footerStart + 2), $preparedByName);
                $sheet->setCellValue('A'.($footerStart + 3), $preparedByTitle);

                $sheet->setCellValue('A'.($footerStart + 6), 'Reviewed and Approved by:');
                $sheet->setCellValue('A'.($footerStart + 8), $reviewedByName);
                $sheet->setCellValue('A'.($footerStart + 9), $reviewedByTitle);

                $sheet->mergeCells("A{$footerStart}:B{$footerStart}");
                $sheet->mergeCells('A'.($footerStart + 2).':B'.($footerStart + 2));
                $sheet->mergeCells('A'.($footerStart + 3).':B'.($footerStart + 3));

                $sheet->mergeCells('A'.($footerStart + 6).':B'.($footerStart + 6));
                $sheet->mergeCells('A'.($footerStart + 8).':B'.($footerStart + 8));
                $sheet->mergeCells('A'.($footerStart + 9).':B'.($footerStart + 9));

                $sheet->getStyle("A{$footerStart}:B".($footerStart + 9))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A'.($footerStart + 2).':B'.($footerStart + 2))->applyFromArray([
                    'font' => ['bold' => true, 'underline' => true],
                ]);
                $sheet->getStyle('A'.($footerStart + 8).':B'.($footerStart + 8))->applyFromArray([
                    'font' => ['bold' => true, 'underline' => true],
                ]);

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageSetup()->setHorizontalCentered(true);

                $printEnd = $footerStart + 10;
                $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$printEnd}");

                $sheet->getDefaultRowDimension()->setRowHeight(16);
            },
        ];
    }

    /**
     * @return Collection<int, array{key: string, label: string, monthNumber: int}>
     */
    private function buildMonths(CarbonImmutable $year): Collection
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $dt = $year->setMonth($m)->startOfMonth();
            $months[] = [
                'key' => $dt->format('Y-m'),
                'label' => $dt->format('F'),
                'monthNumber' => $m,
            ];
        }

        return collect($months);
    }

    /**
     * @param  Collection<int, array{key: string, label: string, monthNumber: int}>  $months
     * @return array<int, array<string, int>>
     */
    private function loadCounts(Collection $months, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $ymExpr = "strftime('%Y-%m', created_at)";
        } elseif ($driver === 'pgsql') {
            $ymExpr = "to_char(created_at, 'YYYY-MM')";
        } else {
            $ymExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        }

        $monthKeys = $months->pluck('key')->all();

        $rows = TicketRequest::query()
            ->selectRaw("nature_of_request_id as nature_id, {$ymExpr} as ym, COUNT(*) as total")
            ->whereBetween('created_at', [$from->startOfDay()->toDateTimeString(), $to->endOfDay()->toDateTimeString()])
            ->whereIn(DB::raw($ymExpr), $monthKeys)
            ->groupBy('nature_of_request_id', DB::raw($ymExpr))
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $natureId = (int) $row->nature_id;
            $ym = (string) $row->ym;
            $total = (int) $row->total;
            $counts[$natureId][$ym] = $total;
        }

        return $counts;
    }
}
