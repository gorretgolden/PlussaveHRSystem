@extends('layouts.app')

@section('title', $role === 'md' ? 'Managing Director dashboard' : 'HR dashboard')

@section('content')
    @php
        $canView = auth()->user()->can('view-employee-file');
        $d = fn ($date) => $date ? $date->format('j M Y') : 'No date';
        $pill = function (string $text, string $tone = 'plain') {
            $style = match ($tone) {
                'ok' => 'bg-ok-soft text-ok',
                'warn' => 'bg-warn-soft text-warn',
                'crit' => 'bg-crit-soft text-crit',
                'info' => 'bg-info-soft text-info',
                default => 'bg-paper text-muted',
            };

            return '<span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10.5px] font-semibold '.$style.'">'.e($text).'</span>';
        };
        $kpi = function (string $label, string $value, string $desc, ?string $href = null, string $tone = '') {
            $edge = match ($tone) {
                'alert' => 'border-l-[3px] border-l-warn',
                'critical' => 'border-l-[3px] border-l-crit',
                default => '',
            };
            $inner = '<span class="text-[11.5px] font-semibold uppercase tracking-[0.07em] text-muted">'.e($label).'</span>'
                .'<span class="font-display text-[28px] font-semibold leading-tight tabular-nums">'.e($value).'</span>'
                .'<span class="text-xs text-muted">'.e($desc).'</span>';

            return $href
                ? '<a href="'.e($href).'" class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3 hover:border-brand '.$edge.'">'.$inner.'</a>'
                : '<div class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3 '.$edge.'">'.$inner.'</div>';
        };
        $bars = function ($rows, string $param) {
            $max = max(1, (int) $rows->max('count'));
            if ($rows->isEmpty()) {
                return '<p class="py-6 text-center text-xs text-muted">Nothing to show.</p>';
            }
            $linked = auth()->user()->can('view-employee-file');

            return '<div class="flex flex-col gap-2.5">'.$rows->map(function ($row) use ($max, $param, $linked) {
                $width = max(3, round($row['count'] / $max * 100));
                $tag = $linked ? 'a' : 'div';
                $url = $linked ? ' href="'.e(route('employees.index', [$param => $row['id'] ?? $row['name']])).'"' : '';

                return '<'.$tag.$url.' class="group grid grid-cols-[minmax(90px,150px)_minmax(0,1fr)_2.25rem] items-center gap-2.5 text-[12.5px]">'
                    .'<span class="truncate text-muted group-hover:text-ink" title="'.e($row['name']).'">'.e($row['name']).'</span>'
                    .'<span class="relative h-3 rounded bg-paper ring-1 ring-line/60"><span class="absolute inset-y-0 left-0 rounded bg-brand" style="width:'.$width.'%"></span></span>'
                    .'<span class="text-right font-semibold tabular-nums">'.$row['count'].'</span></'.$tag.'>';
            })->implode('').'</div>';
        };
        $card = fn (string $title, string $body, string $sub = '') => '<section class="min-w-0 rounded-xl border border-line bg-surface p-4"><div class="mb-3 flex items-center justify-between gap-2"><h2 class="font-display text-[15px] font-semibold">'.e($title).'</h2>'.($sub !== '' ? '<span class="text-[11.5px] text-muted">'.e($sub).'</span>' : '').'</div>'.$body.'</section>';
        $row = function (string $title, string $sub) use ($canView, $pill) {
            return '<div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($title).'</span><span class="block truncate text-xs text-muted">'.e($sub).'</span></span>';
        };
        $branchNames = $branches->pluck('name')->values();
        $placeList = $branchNames->count() > 1
            ? $branchNames->slice(0, -1)->implode(', ').' and '.$branchNames->last()
            : $branchNames->first();
        $month = $period ? \Carbon\Carbon::createFromFormat('Y-m', $period->period)->format('F') : 'No period';
        $initial = fn ($employee) => $employee ? '<span class="grid size-8 shrink-0 place-items-center rounded-full bg-brand-soft text-[10px] font-bold text-brand">'.e($employee->initials).'</span>' : '';
    @endphp

    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-faint">Overview</p>
                <h1 class="mt-1 font-display text-[26px] font-semibold leading-tight">{{ $role === 'md' ? 'Managing Director dashboard' : 'HR dashboard' }}</h1>
                <p class="mt-1 max-w-[70ch] text-sm text-muted">Headcount, alerts and approvals across {{ $placeList }}.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if ($role === 'md')
                    <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-[13px] font-semibold text-white hover:bg-brand-strong">Review approvals ({{ $awaitingCount }})</a>
                @elseif (auth()->user()->can('apply-leave'))
                    <a href="{{ route('leave.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-[13px] font-semibold text-white hover:bg-brand-strong">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        Apply for leave
                    </a>
                @endif
            </div>
        </div>

        @if ($role === 'md' && $awaitingCount)
            <div class="rounded-lg border border-warn/30 bg-warn-soft px-4 py-3 text-sm text-warn">
                <b>{{ $awaitingCount }} {{ \Illuminate\Support\Str::plural('item', $awaitingCount) }} waiting for your approval.</b>
                HR has verified them. <a href="{{ route('approvals.index') }}" class="font-semibold underline">Open approvals</a>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            {!! $kpi('Employee count', (string) $headcount, $activeCount.' active, '.$onLeaveToday->count().' on leave', $canView ? route('employees.index') : null) !!}
            {!! $kpi('Waiting for me', (string) $awaitingCount, $role === 'hr' ? 'Staff requests to verify' : 'Verified by HR', route('approvals.index'), $awaitingCount ? 'alert' : '') !!}
            {!! $kpi('Staff absent today', (string) $absent->count(), $late->count().' late after 08:30', null, $absent->count() ? 'alert' : '') !!}
            {!! $kpi('Contracts expiring', (string) $contracts->count(), 'Next 90 days', null, $contracts->contains(fn ($e) => (int) now()->diffInDays($e->contract_end, false) <= 30) ? 'critical' : '') !!}
            {!! $kpi('Probation ending', (string) $probation->count(), 'Next 45 days') !!}
            {!! $kpi('Due for appraisal', (string) $appraisals->count(), 'Next 30 days') !!}
            {!! $kpi('Vacant positions', (string) $vacancies->count(), $vacancies->sum('applicants').' applicants') !!}
            {!! $kpi('Trainings due', (string) $trainings->count(), 'Next 30 days') !!}
            {!! $kpi('Cases due action', (string) $cases->count(), 'Disciplinary and grievance', null, $cases->count() ? 'alert' : '') !!}
            {!! $kpi($month.' payroll', $period ? 'UGX '.number_format($payrollGross / 1e6, 1).'M' : 'Not started', $payrollStaff.' staff · '.($period?->status ?? 'No period')) !!}
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            {!! $card('Employees per branch', $bars($branches, 'branch'), $canView ? 'Tap a bar to filter' : '') !!}
            {!! $card('Employees per department', $bars($departments, 'department')) !!}
            {!! $card('Employment type', $bars($types, 'type'), 'Permanent, contract, probationary, part-time, intern') !!}
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @php
                $leaveBody = '<div>';
                foreach ($upcomingLeave as $leave) {
                    $days = max(0, (int) now()->startOfDay()->diffInDays($leave->start_date, false));
                    $status = $leave->start_date->lte(today()) ? $pill('On leave', 'info') : $pill('In '.$days.'d');
                    $leaveBody .= $row($leave->employee?->full_name ?? $leave->employee_id, ($leave->leaveType?->name ?? $leave->type_code).' · '.$d($leave->start_date).' to '.$d($leave->end_date)).$status.'</div>';
                }
                $leaveBody = $upcomingLeave->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">No approved leave coming up.</p>' : $leaveBody.'</div>';
            @endphp
            {!! $card('Leave tracker', $leaveBody) !!}

            @php
                $due = $contracts->map(fn ($e) => ['employee' => $e, 'label' => 'Contract ends', 'date' => $e->contract_end])
                    ->merge($probation->map(fn ($e) => ['employee' => $e, 'label' => 'Probation ends', 'date' => $e->probation_end]))
                    ->sortBy('date');
                $dueBody = '<div>';
                foreach ($due as $item) {
                    $n = max(0, (int) now()->startOfDay()->diffInDays($item['date'], false));
                    $line = $initial($item['employee']).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($item['employee']->full_name).'</span><span class="block text-xs text-muted">'.e($item['label']).' '.$d($item['date']).'</span></span>'.$pill($n.' days', $n <= 30 ? 'crit' : 'warn');
                    $dueBody .= $canView
                        ? '<a href="'.e(route('employees.show', $item['employee'])).'" class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 hover:bg-paper">'.$line.'</a>'
                        : '<div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0">'.$line.'</div>';
                }
                $dueBody = $due->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">Nothing due.</p>' : $dueBody.'</div>';
            @endphp
            {!! $card('Contract expiry and probation end', $dueBody, 'Alerts at 90, 60 and 30 days') !!}

            @php
                $appraisalBody = '<div>';
                foreach ($appraisals as $appraisal) {
                    $line = $initial($appraisal->employee).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($appraisal->employee?->full_name ?? $appraisal->employee_id).'</span><span class="block text-xs text-muted">'.e($appraisal->cycle.' · '.$appraisal->status).'</span></span>'.$pill($d($appraisal->due_date), (int) now()->diffInDays($appraisal->due_date, false) <= 7 ? 'warn' : 'plain');
                    $appraisalBody .= $canView
                        ? '<a href="'.e(route('employees.show', $appraisal->employee)).'" class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 hover:bg-paper">'.$line.'</a>'
                        : '<div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0">'.$line.'</div>';
                }
                $appraisalBody = $appraisals->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">No appraisals due.</p>' : $appraisalBody.'</div>';
            @endphp
            {!! $card('Staff due for appraisal', $appraisalBody) !!}
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @php
                $attendanceBody = '<div>';
                foreach ($absent as $employee) {
                    $attendanceBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5">'.$initial($employee).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($employee->full_name).'</span><span class="block truncate text-xs text-muted">'.e($employee->branch?->name).' · no clock-in, no approved leave</span></span>'.$pill('Absent', 'crit').'</div>';
                }
                foreach ($late as $entry) {
                    $attendanceBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5">'.$initial($entry->employee).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($entry->employee?->full_name ?? $entry->employee_id).'</span><span class="block text-xs text-muted">Clocked in '.e(substr((string) $entry->clock_in, 0, 5)).'</span></span>'.$pill('Late', 'warn').'</div>';
                }
                $attendanceBody = ($absent->isEmpty() && $late->isEmpty()) ? '<p class="py-6 text-center text-xs text-muted">Everyone is in and on time.</p>' : $attendanceBody.'</div>';
            @endphp
            {!! $card('Staff absent today', $attendanceBody, 'Synced from biometric') !!}

            @php
                $movementBody = '<div>';
                foreach ($newStaff as $employee) {
                    $movementBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5">'.$initial($employee).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($employee->full_name).'</span><span class="block truncate text-xs text-muted">'.e($employee->title.' · joined '.$d($employee->joined)).'</span></span>'.$pill('New', 'ok').'</div>';
                }
                foreach ($exits as $exit) {
                    $movementBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5">'.$initial($exit->employee).'<span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($exit->employee?->full_name ?? $exit->employee_id).'</span><span class="block truncate text-xs text-muted">'.e($exit->type.' · last day '.$d($exit->last_working_day)).'</span></span>'.$pill('Exit').'</div>';
                }
                $movementBody = ($newStaff->isEmpty() && $exits->isEmpty()) ? '<p class="py-6 text-center text-xs text-muted">No starters or exits in the last 90 days.</p>' : $movementBody.'</div>';
            @endphp
            {!! $card('New staff and employee exits', $movementBody, 'Last 90 days') !!}

            @php
                $vacancyBody = '<div>';
                foreach ($vacancies as $vacancy) {
                    $vacancyBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($vacancy->title).'</span><span class="block truncate text-xs text-muted">'.e(($vacancy->branch?->name ?? 'Branch').' · '.$vacancy->applicants.' applicants · closes '.$d($vacancy->closes)).'</span></span>'.$pill($vacancy->stage, 'info').'</div>';
                }
                $vacancyBody = $vacancies->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">No open positions.</p>' : $vacancyBody.'</div>';
            @endphp
            {!! $card('Recruitment: vacant positions', $vacancyBody) !!}
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            @php
                $trainingBody = '<div>';
                foreach ($trainings as $training) {
                    $trainingBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($training->title).'</span><span class="block truncate text-xs text-muted">'.e($training->audience.' · '.$d($training->training_date)).'</span></span>'.$pill($training->mandatory ? 'Mandatory' : 'Optional', $training->mandatory ? 'warn' : 'plain').'</div>';
                }
                $trainingBody = $trainings->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">None in the next 30 days.</p>' : $trainingBody.'</div>';
            @endphp
            {!! $card('Trainings due', $trainingBody) !!}

            @php
                $caseBody = '<div>';
                foreach ($cases as $case) {
                    $caseBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold leading-tight">'.e($case->id.' · '.($case->employee?->full_name ?? $case->employee_id)).'</span><span class="block truncate text-xs text-muted">'.e($case->category).'</span></span>'.$pill($case->stage.($case->due_date ? ' · '.$d($case->due_date) : ''), 'warn').'</div>';
                }
                $caseBody = $cases->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">No open cases.</p>' : $caseBody.'</div>';
            @endphp
            {!! $card('Disciplinary cases due action', $caseBody) !!}

            @php
                $historyBody = '<div class="overflow-x-auto"><table class="w-full text-left text-[13px]"><thead class="text-[11px] uppercase tracking-wide text-muted"><tr class="border-b border-line"><th class="py-2 font-semibold">Period</th><th class="py-2 font-semibold">Staff</th><th class="py-2 text-right font-semibold">Gross (UGX)</th><th class="py-2 text-right font-semibold">Status</th></tr></thead><tbody>';
                foreach ($payrollHistory as $history) {
                    $isCurrent = $period && $history->id === $period->id;
                    $staffCount = $isCurrent ? $payrollStaff : '-';
                    $gross = $isCurrent ? $payrollGross : (float) $history->gross;
                    $historyBody .= '<tr class="border-b border-line last:border-0"><td class="py-2">'.\Carbon\Carbon::createFromFormat('Y-m', $history->period)->format('M Y').'</td><td class="py-2 tabular-nums">'.$staffCount.'</td><td class="py-2 text-right tabular-nums">'.number_format($gross).'</td><td class="py-2 text-right">'.$pill($history->status, $history->status === 'Posted' ? 'ok' : 'warn').'</td></tr>';
                }
                $historyBody .= '</tbody></table></div>';
            @endphp
            {!! $card('Payroll and headcount summary', $historyBody) !!}
        </div>
    </div>
@endsection
