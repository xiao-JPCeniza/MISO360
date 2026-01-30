<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        if (! $request->user()?->canManageRoles()) {
            abort(403);
        }

        $logs = AuditLog::query()
            ->with('actor:id,name,email,role')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/Index', [
            'logs' => $logs,
        ]);
    }
}
