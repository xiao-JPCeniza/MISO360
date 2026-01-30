<?php

namespace App\Http\Controllers;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use Illuminate\Support\Collection;

class ReferenceValueOptionsController extends Controller
{
    public function __invoke(): array
    {
        return [
            'status' => $this->optionsForGroup(ReferenceValueGroup::Status),
            'category' => $this->optionsForGroup(ReferenceValueGroup::Category),
            'officeDesignation' => $this->optionsForGroup(ReferenceValueGroup::OfficeDesignation),
            'remarks' => $this->optionsForGroup(ReferenceValueGroup::Remarks),
        ];
    }

    private function optionsForGroup(ReferenceValueGroup $group): Collection
    {
        return ReferenceValue::query()
            ->active()
            ->forGroup($group)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
