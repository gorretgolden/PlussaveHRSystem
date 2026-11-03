<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(Request $request): View
    {
        $employee = $request->user()->employee;

        return view('leave.index', [
            'employee' => $employee,
            'types' => LeaveType::orderBy('name')->get(),
            'requests' => $employee
                ? LeaveRequest::with('leaveType')->where('employee_id', $employee->id)->orderByDesc('start_date')->get()
                : collect(),
            'applications' => $employee
                ? HrRequest::where('kind', 'Leave application')
                    ->where('target_employee', $employee->id)
                    ->orderByDesc('created_at')->limit(5)->get()
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('leave.create', [
            'employee' => $request->user()->employee,
            'types' => LeaveType::orderBy('name')->get(),
            'holidays' => Holiday::pluck('day')->map(fn ($d) => Carbon::parse($d)->toDateString())->all(),
        ]);
    }

    public function store(Request $request, LeaveService $leave): RedirectResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($employee, 403, 'Your account is not linked to an employee record.');

        $data = $request->validate([
            'type' => ['required', 'exists:leave_types,code'],
            'from' => ['required', 'date', 'after_or_equal:today'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'reason' => ['nullable', 'string', 'max:400'],
        ], [
            'type.required' => 'Choose a leave type.',
            'type.exists' => 'Choose a leave type from the list.',
            'from.required' => 'Choose the first day of your leave.',
            'from.date' => 'The first day must be a real date.',
            'from.after_or_equal' => 'The first day cannot be in the past.',
            'to.required' => 'Choose the last day of your leave.',
            'to.date' => 'The last day must be a real date.',
            'to.after_or_equal' => 'The last day cannot be before the first day.',
            'reason.max' => 'Keep the reason under 400 characters.',
        ]);

        $from = Carbon::parse($data['from']);
        $to = Carbon::parse($data['to']);

        if ($from->diffInDays($to) > 366) {
            return back()->withInput()->withErrors(['to' => 'Keep one leave request within a year. Split longer leave into separate requests.']);
        }

        $days = $leave->workingDays($from, $to);

        if ($days < 1) {
            return back()->withInput()->withErrors(['from' => 'Those dates contain no working days. Pick a range that includes at least one weekday.']);
        }

        if ($clash = $leave->overlap($employee, $from, $to)) {
            return back()->withInput()->withErrors(['from' => $clash]);
        }

        $balance = [
            'Annual' => $employee->annual_balance,
            'Sick' => $employee->sick_balance,
            'Other' => $employee->other_balance,
        ][$data['type']] ?? null;

        if ($balance !== null && $days > $balance) {
            return back()->withInput()->withErrors(['to' => "You have {$balance} days left in that balance, but this request is for {$days} working days."]);
        }

        $hr = $leave->apply($employee, $user, $data);

        return redirect()->route('leave.index')
            ->with('status', "{$hr->ref} was submitted and is now with ".($hr->chain[0] === 'hr' ? 'HR' : 'the MD').' for approval.');
    }
}
