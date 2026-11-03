<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HrRequest;
use App\Services\NotificationService;
use App\Services\RoleAccessService;
use App\Support\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function index(Request $request): View
    {
        $roles = collect(PermissionCatalog::ROLES)->map(function (string $label, string $code) {
            $role = Role::where('name', $code)->where('guard_name', 'web')->with('permissions')->first();

            return [
                'code' => $code,
                'label' => $label,
                'role' => $role,
                'permissions' => $role?->permissions->pluck('name')->all() ?? [],
                'users' => $role?->users()->count() ?? 0,
            ];
        });

        $selectedCode = $request->query('role');
        $selectedCode = is_string($selectedCode) ? $selectedCode : 'hr';
        $selected = $roles->firstWhere('code', $selectedCode) ?? $roles->firstWhere('code', 'hr') ?? $roles->first();

        $pending = HrRequest::with('actions')
            ->where('kind', 'Role permission change')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (HrRequest $r) => ($r->payload['role'] ?? null) === $selected['code'])
            ->first();

        $history = HrRequest::with('actions')
            ->where('kind', 'Role permission change')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.roles', [
            'roles' => $roles,
            'selected' => $selected,
            'groups' => PermissionCatalog::MODULES,
            'checked' => $selected['permissions'],
            'allPrivileges' => PermissionCatalog::PRIVILEGES,
            'privileges' => PermissionCatalog::privileges($selected['code']),
            'pending' => $pending,
            'history' => $history,
            'canEdit' => in_array($request->user()->role, ['hr', 'admin'], true),
        ]);
    }

    public function update(Request $request, string $roleCode, RoleAccessService $access): RedirectResponse
    {
        $role = Role::where('name', $roleCode)->where('guard_name', 'web')->firstOrFail();
        abort_unless(array_key_exists($role->name, PermissionCatalog::ROLES), 404);
        abort_unless(in_array($request->user()->role, ['hr', 'admin'], true), 403, 'Only HR or the system admin can change a role.');

        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionCatalog::names())],
            'privileges' => ['array'],
            'privileges.*' => ['string', 'distinct', Rule::in(PermissionCatalog::PRIVILEGES)],
        ]);

        $permissions = $this->sorted($data['permissions'] ?? []);
        $privileges = $this->sorted($data['privileges'] ?? []);
        $before = $this->sorted($role->permissions->pluck('name')->all());
        $beforePrivileges = $this->sorted(PermissionCatalog::privileges($role->name));

        if (in_array('Initiate', $privileges, true) && in_array('Approve', $privileges, true)) {
            throw ValidationException::withMessages([
                'privileges' => 'This role can both initiate and approve. One person cannot be maker and checker on the same item.',
            ]);
        }

        if ($role->name === 'md' && ! in_array('Approve', $privileges, true)) {
            throw ValidationException::withMessages([
                'privileges' => 'The Managing Director role must keep Approve so requests can still be decided.',
            ]);
        }

        if ($role->name === 'hr' && ! in_array('Verify', $privileges, true)) {
            throw ValidationException::withMessages([
                'privileges' => 'The HR Manager role must keep Verify so staff requests can still reach the MD.',
            ]);
        }

        if ($role->name === $request->user()->role
            && $request->user()->can('user-roles-and-permissions')
            && ! in_array('user-roles-and-permissions', $permissions, true)) {
            throw ValidationException::withMessages([
                'permissions' => 'Keep USER ROLES AND PERMISSIONS on your own role so you can still reach this page.',
            ]);
        }

        $added = array_values(array_diff($permissions, $before));
        $removed = array_values(array_diff($before, $permissions));

        if (! $added && ! $removed && $privileges === $beforePrivileges) {
            return back()->with('status', 'No changes to save.');
        }

        $label = PermissionCatalog::roleLabel($role->name);

        $pending = HrRequest::where('kind', 'Role permission change')
            ->where('status', 'pending')
            ->get()
            ->first(fn (HrRequest $r) => ($r->payload['role'] ?? null) === $role->name);

        if ($pending) {
            throw ValidationException::withMessages([
                'permissions' => $pending->ref.' is already waiting for MD approval for this role. Wait for that decision or ask the MD to act on it.',
            ]);
        }

        DB::transaction(function () use ($request, $role, $permissions, $privileges, $added, $removed, $label) {
            $hr = HrRequest::createWithRef([
                'kind' => 'Role permission change',
                'subject' => $label.': '.count($added).' added, '.count($removed).' removed',
                'maker' => $request->user()->employee_id,
                'maker_role' => $request->user()->role,
                'chain' => ['md'],
                'stage' => 0,
                'status' => 'pending',
                'fields' => [
                    ['Role', $label],
                    ['Added', $this->labels($added) ?: 'None'],
                    ['Removed', $this->labels($removed) ?: 'None'],
                    ['Privileges', $privileges ? implode(', ', $privileges) : 'None'],
                    ['Permissions after change', count($permissions)],
                ],
                'payload' => ['role' => $role->name, 'p' => $permissions, 'a' => $privileges],
            ]);

            $hr->actions()->create([
                'actor' => $request->user()->employee_id,
                'actor_role' => $request->user()->role,
                'action' => 'Submitted',
                'comment' => 'Role permissions changed from the Administration screen.',
            ]);

            NotificationService::role('md', "{$hr->ref} Role permission change: {$hr->subject} is waiting for your approval");
            AuditLog::record($request->user()->employee_id, $request->user()->role, 'Submitted', $hr->ref, 'Role permission change: '.$hr->subject);
        });

        return redirect()->route('admin.roles', ['role' => $role->name])
            ->with('status', 'Sent to the MD for approval.');
    }

    private function sorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    private function labels(array $slugs): string
    {
        return implode(', ', array_map(fn ($slug) => PermissionCatalog::label($slug), $slugs));
    }
}
