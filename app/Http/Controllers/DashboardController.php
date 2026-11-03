<?php

namespace App\Http\Controllers;

use App\Models\Appraisal;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Employee;
use App\Models\ExitRecord;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\PayrollInput;
use App\Models\PayrollPeriod;
use App\Models\Training;
use App\Models\Vacancy;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const SHIFT_START = '08:30:00';

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        if ($user->role === 'staff') {
            return $this->staffDashboard($request);
        }

        if (! $user->can('hr-dashboard')) {
            return $this->workspaceDashboard($request);
        }

        $staff = Employee::with(['branch', 'department', 'grade'])
            ->whereIn('status', ['Active', 'On Leave'])
            ->orderBy('first_name')
            ->get();

        $onLeave = LeaveRequest::with('employee', 'leaveType')
            ->where('status', 'Approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        $awaiting = HrRequest::where('status', 'pending')
            ->where('maker', '!=', $user->employee_id)
            ->get()
            ->filter(fn (HrRequest $r) => ($r->chain[$r->stage] ?? null) === $user->role);

        $clocked = Attendance::with('employee')
            ->where('work_date', $today)
            ->whereNotNull('clock_in')
            ->get();

        $late = $clocked->filter(fn (Attendance $a) => $a->clock_in > self::SHIFT_START)->values();

        $absent = $staff
            ->where('status', 'Active')
            ->whereNotIn('id', $clocked->pluck('employee_id'))
            ->whereNotIn('id', $onLeave->pluck('employee_id'))
            ->values();

        $contracts = $staff
            ->whereNotNull('contract_end')
            ->filter(fn (Employee $e) => $e->contract_end->between(now()->startOfDay(), now()->addDays(90)))
            ->sortBy('contract_end')
            ->values();

        $probation = $staff
            ->whereNotNull('probation_end')
            ->filter(fn (Employee $e) => $e->probation_end->between(now()->startOfDay(), now()->addDays(45)))
            ->sortBy('probation_end')
            ->values();

        $appraisals = Appraisal::with('employee')
            ->where('status', '!=', 'Completed')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->get();

        $vacancies = Vacancy::with('branch')->orderBy('closes')->get();

        $trainings = Training::whereDate('training_date', '>=', $today)
            ->whereDate('training_date', '<=', now()->addDays(30))
            ->orderBy('training_date')
            ->get();

        $cases = CaseFile::with('employee')
            ->where('stage', '!=', 'Closed')
            ->orderBy('due_date')
            ->get();

        $exits = ExitRecord::with('employee')
            ->whereDate('last_working_day', '>=', now()->subDays(90))
            ->orderByDesc('last_working_day')
            ->get();

        $period = PayrollPeriod::orderByDesc('period')->first();
        $payrollGross = $period?->gross !== null && $period?->gross !== 0
            ? (float) $period->gross
            : $this->draftGross($staff, $period);

        return view('dashboard', [
            'role' => $user->role,
            'headcount' => $staff->count(),
            'activeCount' => $staff->where('status', 'Active')->count(),
            'onLeaveToday' => $onLeave,
            'awaiting' => $awaiting,
            'awaitingCount' => $awaiting->count(),
            'clockedIn' => $clocked->count(),
            'late' => $late,
            'absent' => $absent,
            'contracts' => $contracts,
            'probation' => $probation,
            'appraisals' => $appraisals,
            'vacancies' => $vacancies,
            'trainings' => $trainings,
            'cases' => $cases,
            'exits' => $exits,
            'newStaff' => $staff->filter(fn (Employee $e) => $e->joined->gte(now()->subDays(90)))->values(),
            'upcomingLeave' => LeaveRequest::with('employee', 'leaveType')
                ->where('status', 'Approved')
                ->whereDate('end_date', '>=', $today)
                ->orderBy('start_date')
                ->limit(6)
                ->get(),
            'branches' => $staff->groupBy('branch_id')->map(fn ($group) => [
                'name' => $group->first()->branch?->name ?? 'Unassigned',
                'id' => $group->first()->branch_id,
                'count' => $group->count(),
            ])->sortByDesc('count')->values(),
            'departments' => $staff->groupBy('dept_id')->map(fn ($group) => [
                'name' => $group->first()->department?->name ?? 'Unassigned',
                'id' => $group->first()->dept_id,
                'count' => $group->count(),
            ])->sortByDesc('count')->values(),
            'types' => $staff->groupBy('employment_type')->map(fn ($group, $type) => [
                'name' => $type,
                'count' => $group->count(),
            ])->sortByDesc('count')->values(),
            'period' => $period,
            'payrollGross' => $payrollGross,
            'payrollStaff' => $staff->count(),
            'payrollHistory' => PayrollPeriod::orderByDesc('period')->limit(4)->get(),
            'activity' => AuditLog::orderByDesc('created_at')->limit(6)->get(),
        ]);
    }

    private function draftGross($staff, ?PayrollPeriod $period): float
    {
        $gross = $staff->sum(fn (Employee $e) => (float) $e->basic
            + (float) ($e->grade?->transport ?? 0)
            + (float) ($e->grade?->housing ?? 0)
            + (float) ($e->grade?->airtime ?? 0));

        if ($period) {
            $gross += PayrollInput::where('period_id', $period->id)->get()
                ->sum(fn (PayrollInput $i) => $i->kind === 'Deduction' ? -(float) $i->amount : (float) $i->amount);
        }

        return max(0, $gross);
    }

    private function workspaceDashboard(Request $request): View
    {
        $user = $request->user();
        $canAudit = $user->can('audit-trail');
        $canReports = $user->can('hr-reports');
        $canViewStaff = $user->can('view-employee-file');

        $periods = $canReports ? PayrollPeriod::orderByDesc('period')->limit(4)->get() : collect();
        $periodGross = $periods->mapWithKeys(fn (PayrollPeriod $period) => [$period->id => (float) $period->gross])->all();

        if ($canReports && $periods->isNotEmpty() && (float) $periods->first()->gross === 0.0) {
            $periodGross[$periods->first()->id] = $this->draftGross(
                Employee::with('grade')->whereIn('status', ['Active', 'On Leave'])->get(),
                $periods->first(),
            );
        }

        return view('dashboard-workspace', [
            'role' => $user->role,
            'roleLabel' => PermissionCatalog::roleLabel($user->role),
            'employee' => $user->employee,
            'canAudit' => $canAudit,
            'canReports' => $canReports,
            'canViewStaff' => $canViewStaff,
            'headcount' => $canViewStaff
                ? Employee::whereIn('status', ['Active', 'On Leave'])->count()
                : null,
            'auditToday' => $canAudit ? AuditLog::whereDate('created_at', today())->count() : null,
            'auditWeek' => $canAudit ? AuditLog::where('created_at', '>=', now()->subDays(7))->count() : null,
            'recentAudit' => $canAudit ? AuditLog::orderByDesc('created_at')->limit(6)->get() : collect(),
            'periods' => $periods,
            'periodGross' => $periodGross,
            'myRequests' => $user->employee_id
                ? HrRequest::where('maker', $user->employee_id)->orderByDesc('created_at')->limit(4)->get()
                : collect(),
            'myPermissions' => $user->getAllPermissions()->pluck('name')
                ->map(fn ($name) => ['module' => PermissionCatalog::module($name), 'label' => PermissionCatalog::label($name)])
                ->groupBy('module'),
        ]);
    }

    private function staffDashboard(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        return view('dashboard-staff', [
            'employee' => $employee,
            'balances' => [
                ['label' => 'Annual leave', 'left' => (float) ($employee?->annual_balance ?? 0), 'max' => 21],
                ['label' => 'Sick leave', 'left' => (float) ($employee?->sick_balance ?? 0), 'max' => 30],
                ['label' => 'Other leave', 'left' => (float) ($employee?->other_balance ?? 0), 'max' => 6],
            ],
            'myLeave' => $employee
                ? LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->orderByDesc('start_date')->limit(5)->get()
                : collect(),
            'myRequests' => $employee
                ? HrRequest::where('maker', $employee->id)->orderByDesc('created_at')->limit(5)->get()
                : collect(),
            'myTrainings' => $employee
                ? Training::whereHas('attendees', fn ($query) => $query->where('employee_id', $employee->id))
                    ->orderByDesc('training_date')->limit(4)->get()
                : collect(),
            'awaiting' => $employee
                ? HrRequest::where('status', 'pending')->where('maker', $employee->id)->count()
                : 0,
            'nextLeave' => $employee
                ? LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->where('status', 'Approved')
                    ->whereDate('start_date', '>=', now()->toDateString())->orderBy('start_date')->first()
                : null,
        ]);
    }
}
