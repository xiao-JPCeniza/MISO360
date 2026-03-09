<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NatureOfRequestsReportRequest;
use App\Models\NatureOfRequest;
use App\Models\TicketRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class NatureOfRequestsReportController extends Controller
{
    public function __invoke(NatureOfRequestsReportRequest $request): Response
    {
        $year = (int) ($request->integer('year') ?: now()->year);

        $natures = NatureOfRequest::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $driver = DB::connection()->getDriverName();
        $monthSql = match ($driver) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM created_at)',
            default => 'MONTH(created_at)',
        };

        $totals = TicketRequest::query()
            ->selectRaw("nature_of_request_id as nature_id, {$monthSql} as month, COUNT(*) as total")
            ->whereNotNull('nature_of_request_id')
            ->whereYear('created_at', $year)
            ->groupBy('nature_id')
            ->groupByRaw($monthSql)
            ->get()
            ->reduce(function (array $carry, $row) {
                $natureId = (int) $row->nature_id;
                $month = (int) $row->month;
                $carry[$natureId][$month] = (int) $row->total;

                return $carry;
            }, []);

        $monthKeys = range(1, 12);
        $monthTotals = array_fill_keys($monthKeys, 0);

        $rows = $natures->map(function (NatureOfRequest $nature) use ($monthKeys, $totals) {
            $counts = [];
            $rowTotal = 0;

            foreach ($monthKeys as $month) {
                $value = $totals[$nature->id][$month] ?? 0;
                $counts[(string) $month] = $value;
                $rowTotal += $value;
            }

            return [
                'id' => $nature->id,
                'nature' => $nature->name,
                'counts' => $counts,
                'total' => $rowTotal,
            ];
        })->values();

        foreach ($rows as $row) {
            foreach ($monthKeys as $month) {
                $monthTotals[$month] += (int) ($row['counts'][(string) $month] ?? 0);
            }
        }

        $months = collect($monthKeys)->map(function (int $month) use ($year) {
            $label = CarbonImmutable::createFromDate($year, $month, 1)->format('F');

            return [
                'key' => $month,
                'label' => $label,
                'short' => CarbonImmutable::createFromDate($year, $month, 1)->format('M'),
            ];
        })->values();

        return Inertia::render('admin/reports/NatureOfRequestsReport', [
            'year' => $year,
            'months' => $months,
            'rows' => $rows,
            'monthTotals' => $monthTotals,
            'grandTotal' => array_sum($monthTotals),
            'preparedBy' => [
                'name' => $request->user()?->name,
                'positionTitle' => $request->user()?->position_title,
            ],
        ]);
    }
}
