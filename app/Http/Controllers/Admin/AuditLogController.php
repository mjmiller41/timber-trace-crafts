<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AdminAuditLog::query()->with('user')->latest();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('actor_email', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        if ($method = $request->query('method')) {
            $query->where('method', $method);
        }

        $logs = $query->paginate(50)->withQueryString();

        $methods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        return view('admin.audit-logs.index', compact('logs', 'methods'));
    }
}
