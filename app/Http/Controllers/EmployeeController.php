<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HrRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Employee::with(['department', 'branch', 'grade']);
        $filter = fn (string $key): string => is_string($value = $request->query($key)) ? trim($value) : '';

        if ($q = $filter('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('id', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhereHas('branch', fn ($branch) => $branch->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$q}%"));
            });
        }

        if ($dept = $filter('department')) {
            $query->where('dept_id', $dept);
        }

        if ($branch = $filter('branch')) {
            $query->where('branch_id', $branch);
        }

        if ($type = $filter('type')) {
            $query->where('employment_type', $type);
        }

        if ($status = $filter('status')) {
            $query->where('status', $status);
        }

        return view('employees.index', [
            'employees' => $query->orderBy('first_name')->paginate(10)->withQueryString(),
            'departments' => Department::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'types' => Employee::distinct()->orderBy('employment_type')->pluck('employment_type'),
            'counts' => Employee::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Employee $employee): View
    {
        $employee->load(['department', 'branch', 'grade', 'supervisor']);

        return view('employees.show', [
            'employee' => $employee,
            'documents' => $employee->documents()->get(),
            'leaves' => LeaveRequest::where('employee_id', $employee->id)->orderByDesc('start_date')->limit(6)->get(),
            'requests' => HrRequest::where('target_employee', $employee->id)->orderByDesc('created_at')->limit(6)->get(),
            'appraisals' => $employee->appraisals()->orderByDesc('due_date')->limit(4)->get(),
        ]);
    }
}
