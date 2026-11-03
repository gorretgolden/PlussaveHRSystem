@extends('layouts.app')

@section('title', 'Employees')
@section('heading', 'Employees')

@section('content')
    @php
        $statusStyle = fn ($s) => match ($s) {
            'Active' => 'bg-ok-soft text-ok',
            'On Leave' => 'bg-info-soft text-info',
            'Suspended' => 'bg-warn-soft text-warn',
            'Resigned' => 'bg-paper text-muted',
            'Terminated' => 'bg-crit-soft text-crit',
            default => 'bg-paper text-muted',
        };
        $total = $counts->sum();
        $param = fn ($key) => is_string($value = request($key)) ? $value : '';
        $qFilter = $param('q');
        $statusFilter = $param('status');
        $deptFilter = $param('department');
        $branchFilter = $param('branch');
        $typeFilter = $param('type');
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-semibold">Employee register</h1>
            <p class="mt-0.5 text-sm text-muted">{{ $total }} people on record across {{ $branches->count() }} branches.</p>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-1.5">
        <a href="{{ route('employees.index') }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusFilter !== '' ? 'bg-surface text-muted ring-1 ring-line' : 'bg-brand text-white' }}">All · {{ $total }}</a>
        @foreach ($counts as $status => $count)
            <a href="{{ route('employees.index', ['status' => $status]) }}" class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusFilter === $status ? 'bg-brand text-white' : 'bg-surface text-muted ring-1 ring-line' }}">{{ $status }} · {{ $count }}</a>
        @endforeach
    </div>

    <form method="GET" class="mt-3 rounded-xl border border-line bg-surface p-3">
        <div class="flex flex-col gap-2 sm:flex-row">
            <input type="search" name="q" value="{{ $qFilter }}" placeholder="Search by name, staff number, title or email"
                   class="w-full flex-1 rounded-lg border border-line bg-white px-3 py-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">
            <select name="department" class="rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All departments</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}" @selected($deptFilter === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
            <select name="branch" class="rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All branches</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected($branchFilter === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-lg border border-line bg-white px-3 py-2 text-sm">
                <option value="">All employment types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($typeFilter === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <input type="hidden" name="status" value="{{ $statusFilter }}">
            <button class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Filter</button>
        </div>
    </form>

    <div class="mt-3 overflow-hidden rounded-xl border border-line bg-surface">
        @if ($employees->isEmpty())
            <p class="px-4 py-12 text-center text-sm text-muted">No employees match those filters.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="border-b border-line bg-paper text-[11px] uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Employee</th>
                        <th class="px-4 py-2.5 font-semibold">Department</th>
                        <th class="px-4 py-2.5 font-semibold">Branch</th>
                        <th class="px-4 py-2.5 font-semibold">Grade</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Basic</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($employees as $employee)
                        <tr class="hover:bg-paper/70">
                            <td class="px-4 py-3">
                                <a href="{{ route('employees.show', $employee) }}" class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-brand-soft text-xs font-bold text-brand">{{ $employee->initials }}</span>
                                    <span class="min-w-0">
                                        <span class="block truncate font-medium">{{ $employee->full_name }}</span>
                                        <span class="block text-[11px] text-muted">{{ $employee->id }} · {{ $employee->title }}</span>
                                    </span>
                                </a>
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $employee->department?->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $employee->branch?->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $employee->grade_code }} · {{ $employee->grade?->name }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format((float) $employee->basic) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusStyle($employee->status) }}">{{ $employee->status }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        <div class="border-t border-line px-4 py-3">
            {{ $employees->links() }}
        </div>
    </div>
@endsection
