<?php

namespace App\Http\Controllers;

use App\Models\NatureOfRequest;

class NatureOfRequestOptionsController extends Controller
{
    public function __invoke()
    {
        return NatureOfRequest::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
