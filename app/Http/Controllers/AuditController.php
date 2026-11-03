<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role');
        $role = is_string($role) ? $role : '';
        $allowed = array_keys(PermissionCatalog::ROLES);

        $rows = AuditLog::query()
            ->when(in_array($role, $allowed, true), fn ($query) => $query->where('actor_role', $role))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $names = Employee::whereIn('id', $rows->pluck('actor')->filter()->unique())
            ->get()
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->full_name]);

        return view('admin.audit', [
            'rows' => $rows,
            'role' => $role,
            'roles' => PermissionCatalog::ROLES,
            'names' => $names,
        ]);
    }
}
