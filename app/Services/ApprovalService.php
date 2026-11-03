<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HrRequest;
use App\Models\PayrollPeriod;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public const ACTIONS = [
        'forward' => 'Verified and forwarded',
        'approve' => 'Approved',
        'return' => 'Returned',
        'reject' => 'Rejected',
    ];

    public function stageRole(HrRequest $request): ?string
    {
        return $request->status === 'pending' ? ($request->chain[$request->stage] ?? null) : null;
    }

    public function canAct(HrRequest $request, User $user): bool
    {
        return $this->stageRole($request) === $user->role
            && $request->maker !== $user->employee_id
            && $this->holdsStagePrivilege($request, $user);
    }

    public function allowedActions(HrRequest $request): array
    {
        if ($this->stageRole($request) === null) {
            return [];
        }

        $isFinal = $request->stage >= count($request->chain) - 1;

        return $isFinal ? ['approve', 'return', 'reject'] : ['forward', 'return', 'reject'];
    }

    public function requiredPrivilege(HrRequest $request): ?string
    {
        return match ($this->stageRole($request)) {
            'md' => 'Approve',
            'hr' => 'Verify',
            default => null,
        };
    }

    public function holdsStagePrivilege(HrRequest $request, User $user): bool
    {
        $needed = $this->requiredPrivilege($request);

        return $needed === null || in_array($needed, PermissionCatalog::privileges($user->role), true);
    }

    public function act(HrRequest $request, User $user, string $action, string $comment = ''): bool
    {
        if (! $this->canAct($request, $user)) {
            return false;
        }

        if (! in_array($action, $this->allowedActions($request), true)) {
            return false;
        }

        if (in_array($action, ['return', 'reject'], true) && trim($comment) === '') {
            return false;
        }

        if ($action === 'approve' && $request->kind === 'Leave application' && LeaveService::approvalProblem($request) !== null) {
            return false;
        }

        DB::transaction(function () use ($request, $user, $action, $comment) {
            $actor = $user->employee_id;
            $label = $this->roleLabel($user->role);

            if ($action === 'forward') {
                $request->actions()->create([
                    'actor' => $actor,
                    'actor_role' => $user->role,
                    'action' => "Verified by {$label}, forwarded to MD",
                    'comment' => $comment,
                ]);
                $request->stage++;
                NotificationService::role('md', "{$request->ref} {$request->kind}: {$request->subject} verified by HR, waiting for your approval");
            } elseif ($action === 'approve') {
                $request->actions()->create([
                    'actor' => $actor,
                    'actor_role' => $user->role,
                    'action' => "Approved by {$label}",
                    'comment' => $comment,
                ]);
                $request->status = 'approved';
                $this->applyEffects($request);
                NotificationService::to($request->maker, "{$request->ref} {$request->kind} was approved");
                if ($request->maker_role === 'staff') {
                    NotificationService::role('hr', "{$request->ref} {$request->kind} approved by MD");
                }
            } elseif ($action === 'return') {
                if ($user->role === 'md' && ($request->chain[0] ?? null) === 'hr' && $request->stage > 0) {
                    $request->stage = 0;
                    $request->actions()->create([
                        'actor' => $actor,
                        'actor_role' => $user->role,
                        'action' => 'Returned to HR by MD',
                        'comment' => $comment,
                    ]);
                    NotificationService::role('hr', "{$request->ref} {$request->subject} returned to you by MD");
                    NotificationService::to($request->maker, "{$request->ref} {$request->kind} was sent back to HR by the MD for another check");
                } else {
                    $request->status = 'returned';
                    $request->actions()->create([
                        'actor' => $actor,
                        'actor_role' => $user->role,
                        'action' => "Returned to maker by {$label}",
                        'comment' => $comment,
                    ]);
                    NotificationService::to($request->maker, "{$request->ref} {$request->kind} was returned for correction");
                }
            } elseif ($action === 'reject') {
                $request->status = 'rejected';
                $request->actions()->create([
                    'actor' => $actor,
                    'actor_role' => $user->role,
                    'action' => "Rejected by {$label}",
                    'comment' => $comment,
                ]);
                NotificationService::to($request->maker, "{$request->ref} {$request->kind} was rejected");
                if ($request->kind === 'Monthly payroll') {
                    PayrollPeriod::query()->where('period', $request->payload['period'] ?? '')->update(['status' => 'Draft']);
                }
            }

            $request->save();

            AuditLog::record(
                $actor,
                $user->role,
                self::ACTIONS[$action] ?? $action,
                $request->ref,
                $request->kind.': '.$request->subject.($comment !== '' ? ' | '.$comment : ''),
            );
        });

        return true;
    }

    public function resubmit(HrRequest $request, User $user, string $comment): void
    {
        DB::transaction(function () use ($request, $user, $comment) {
            $request->status = 'pending';
            $request->stage = 0;
            $request->actions()->create([
                'actor' => $user->employee_id,
                'actor_role' => $user->role,
                'action' => 'Corrected and resubmitted',
                'comment' => $comment,
            ]);
            $request->save();

            NotificationService::role($request->chain[0] ?? 'hr', "{$request->ref} resubmitted after correction");
            AuditLog::record($user->employee_id, $user->role, 'Resubmitted', $request->ref, $request->subject);
        });
    }

    private function applyEffects(HrRequest $request): void
    {
        $p = $request->payload ?? [];

        match ($request->kind) {
            'Leave application' => LeaveService::postApproval($request),
            'Personal details change' => $this->changeDetails($p),
            'Transfer, promotion or salary change' => $this->transfer($p),
            'Contract renewal' => $this->renewContract($p),
            'Role permission change' => $this->rolePermissions($p),
            default => null,
        };
    }

    private function changeDetails(array $p): void
    {
        $employee = Employee::find($p['emp'] ?? null);

        if ($employee && in_array($p['field'] ?? '', ['phone', 'email', 'address', 'nok_name', 'nok_relation', 'nok_phone'], true)) {
            $employee->update([$p['field'] => $p['value']]);
        }
    }

    private function transfer(array $p): void
    {
        $employee = Employee::find($p['emp'] ?? null);

        if (! $employee) {
            return;
        }

        $branchId = Branch::where('name', $p['branch'] ?? '')->value('id')
            ?? $employee->branch_id;
        $deptId = Department::where('name', $p['dept'] ?? '')->value('id')
            ?? $employee->dept_id;

        $employee->update([
            'title' => $p['title'] ?? $employee->title,
            'branch_id' => $branchId,
            'dept_id' => $deptId,
            'basic' => $p['salary'] ?? $employee->basic,
            'grade_code' => $p['grade'] ?? $employee->grade_code,
        ]);
    }

    private function renewContract(array $p): void
    {
        $employee = Employee::find($p['emp'] ?? null);

        if ($employee && ! empty($p['newEnd'])) {
            $employee->update(['contract_end' => $p['newEnd']]);
        }
    }

    private function rolePermissions(array $p): void
    {
        if (! isset($p['role'])) {
            return;
        }

        app(RoleAccessService::class)->apply(
            $p['role'],
            $p['p'] ?? [],
            $p['a'] ?? [],
            auth()->user()?->employee_id,
            auth()->user()?->role,
        );
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'md' => 'MD',
            'hr' => 'HR',
            default => 'Staff',
        };
    }
}
