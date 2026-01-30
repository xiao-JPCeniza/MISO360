<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReferenceValueGroup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReferenceValueRequest;
use App\Http\Requests\Admin\UpdateReferenceValueRequest;
use App\Models\ReferenceValue;
use App\Models\TicketArchive;
use App\Models\TicketEnrollment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatusManagementController extends Controller
{
    public function index(): Response
    {
        $usageCounts = [
            ReferenceValueGroup::Status->value => $this->usageCounts('warranty_status'),
            ReferenceValueGroup::Category->value => $this->usageCounts('equipment_type'),
            ReferenceValueGroup::OfficeDesignation->value => $this->usageCounts('location_office_division'),
            ReferenceValueGroup::Remarks->value => $this->usageCounts('request_remarks'),
        ];

        $values = ReferenceValue::query()
            ->orderBy('name')
            ->get()
            ->groupBy('group_key');

        $groups = collect(ReferenceValueGroup::cases())
            ->mapWithKeys(function (ReferenceValueGroup $group) use ($values, $usageCounts) {
                $items = $values->get($group->value, collect())
                    ->map(function (ReferenceValue $value) use ($usageCounts, $group) {
                        return [
                            'id' => $value->id,
                            'name' => $value->name,
                            'isActive' => $value->is_active,
                            'usageCount' => $usageCounts[$group->value][$value->name] ?? 0,
                            'updatedAt' => optional($value->updated_at)->toDateTimeString(),
                        ];
                    })
                    ->values();

                return [$this->payloadKeyForGroup($group) => $items];
            })
            ->toArray();

        return Inertia::render('admin/status/Index', [
            'groups' => $groups,
        ]);
    }

    public function store(StoreReferenceValueRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validated();
        $groupKey = $validated['group_key'];
        $name = trim($validated['name']);

        $referenceValue = ReferenceValue::create([
            'group_key' => $groupKey,
            'name' => $name,
            'system_seeded' => false,
            'is_active' => true,
        ]);

        $auditLogger->log($request, 'reference-values.created', $referenceValue, [
            'group_key' => $groupKey,
            'name' => $name,
        ]);

        return redirect()->route('admin.status.index');
    }

    public function update(
        UpdateReferenceValueRequest $request,
        ReferenceValue $referenceValue,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validated();
        $previousName = $referenceValue->name;
        $nextName = trim($validated['name']);
        $isActive = $validated['is_active'] ?? $referenceValue->is_active;

        DB::transaction(function () use ($referenceValue, $previousName, $nextName, $isActive) {
            $referenceValue->update([
                'name' => $nextName,
                'is_active' => $isActive,
            ]);

            if ($previousName !== $nextName) {
                $this->cascadeRename($referenceValue->group_key, $previousName, $nextName);
            }
        });

        $auditLogger->log($request, 'reference-values.updated', $referenceValue, [
            'group_key' => $referenceValue->group_key,
            'previous_name' => $previousName,
            'name' => $nextName,
            'is_active' => $isActive,
        ]);

        return redirect()->route('admin.status.index');
    }

    public function destroy(
        Request $request,
        ReferenceValue $referenceValue,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $referenceValue->update([
            'is_active' => false,
        ]);

        $auditLogger->log($request, 'reference-values.removed', $referenceValue, [
            'group_key' => $referenceValue->group_key,
            'name' => $referenceValue->name,
        ]);

        return redirect()->route('admin.status.index');
    }

    /**
     * @return array<string, int>
     */
    private function usageCounts(string $column): array
    {
        $enrollmentCounts = TicketEnrollment::query()
            ->select($column, DB::raw('count(*) as total'))
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('total', $column);

        $archiveCounts = TicketArchive::query()
            ->select($column, DB::raw('count(*) as total'))
            ->whereNotNull($column)
            ->groupBy($column)
            ->pluck('total', $column);

        $counts = $enrollmentCounts->mapWithKeys(fn ($count, $name) => [$name => (int) $count])->toArray();

        foreach ($archiveCounts as $name => $count) {
            $counts[$name] = ($counts[$name] ?? 0) + (int) $count;
        }

        return $counts;
    }

    private function cascadeRename(string $groupKey, string $previousName, string $nextName): void
    {
        match ($groupKey) {
            ReferenceValueGroup::Status->value => $this->updateColumnValue('warranty_status', $previousName, $nextName),
            ReferenceValueGroup::Category->value => $this->updateColumnValue('equipment_type', $previousName, $nextName),
            ReferenceValueGroup::OfficeDesignation->value => $this->updateColumnValue('location_office_division', $previousName, $nextName),
            ReferenceValueGroup::Remarks->value => $this->updateColumnValue('request_remarks', $previousName, $nextName),
            default => null,
        };
    }

    private function updateColumnValue(string $column, string $previousName, string $nextName): void
    {
        TicketEnrollment::query()
            ->where($column, $previousName)
            ->update([$column => $nextName]);

        TicketArchive::query()
            ->where($column, $previousName)
            ->update([$column => $nextName]);
    }

    private function payloadKeyForGroup(ReferenceValueGroup $group): string
    {
        return match ($group) {
            ReferenceValueGroup::Status => 'status',
            ReferenceValueGroup::Category => 'category',
            ReferenceValueGroup::OfficeDesignation => 'officeDesignation',
            ReferenceValueGroup::Remarks => 'remarks',
        };
    }
}
