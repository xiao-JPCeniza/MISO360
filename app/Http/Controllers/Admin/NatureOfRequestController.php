<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNatureOfRequestRequest;
use App\Http\Requests\Admin\UpdateNatureOfRequestRequest;
use App\Models\NatureOfRequest;
use App\Models\TicketArchive;
use App\Models\TicketEnrollment;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NatureOfRequestController extends Controller
{
    public function index()
    {
        $usageCounts = TicketEnrollment::query()
            ->select('request_nature', DB::raw('count(*) as total'))
            ->whereNotNull('request_nature')
            ->groupBy('request_nature')
            ->pluck('total', 'request_nature');

        $requests = NatureOfRequest::query()
            ->orderBy('name')
            ->get()
            ->map(function (NatureOfRequest $request) use ($usageCounts) {
                return [
                    'id' => $request->id,
                    'name' => $request->name,
                    'isActive' => $request->is_active,
                    'usageCount' => $usageCounts[$request->name] ?? 0,
                    'updatedAt' => optional($request->updated_at)->toDateTimeString(),
                ];
            })
            ->values();

        return Inertia::render('admin/nature-of-requests/Index', [
            'requests' => $requests,
        ]);
    }

    public function store(StoreNatureOfRequestRequest $request)
    {
        NatureOfRequest::create([
            'name' => $request->string('name')->trim()->toString(),
            'is_active' => true,
        ]);

        return redirect()->route('admin.nature-of-requests.index');
    }

    public function update(UpdateNatureOfRequestRequest $request, NatureOfRequest $natureOfRequest)
    {
        $validated = $request->validated();
        $previousName = $natureOfRequest->name;
        $nextName = trim($validated['name']);
        $isActive = $validated['is_active'] ?? $natureOfRequest->is_active;

        DB::transaction(function () use ($natureOfRequest, $previousName, $nextName, $isActive) {
            $natureOfRequest->update([
                'name' => $nextName,
                'is_active' => $isActive,
            ]);

            if ($previousName !== $nextName) {
                TicketEnrollment::query()
                    ->where('request_nature', $previousName)
                    ->update(['request_nature' => $nextName]);

                TicketArchive::query()
                    ->where('request_nature', $previousName)
                    ->update(['request_nature' => $nextName]);
            }
        });

        return redirect()->route('admin.nature-of-requests.index');
    }

    public function destroy(NatureOfRequest $natureOfRequest)
    {
        $natureOfRequest->update([
            'is_active' => false,
        ]);

        return redirect()->route('admin.nature-of-requests.index');
    }
}
