@extends('layouts.app')

@section('title', 'My dashboard')

@section('content')
    @php
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
        $card = fn (string $title, string $body) => '<section class="rounded-xl border border-line bg-surface p-4"><h2 class="mb-3 font-display text-[15px] font-semibold">'.e($title).'</h2>'.$body.'</section>';
    @endphp

    <div class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-faint">My workspace</p>
                <h1 class="mt-1 font-display text-[26px] font-semibold leading-tight">My dashboard</h1>
                <p class="mt-1 text-sm text-muted">{{ $employee ? $employee->title.' · '.$employee->branch?->name : 'Your account is not linked to an employee record yet.' }}</p>
            </div>
            @can('apply-leave')
                <a href="{{ route('leave.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-3.5 py-2 text-[13px] font-semibold text-white hover:bg-brand-strong">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Apply for leave
                </a>
            @endcan
        </div>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ($balances as $balance)
                <div class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3">
                    <span class="text-[11.5px] font-semibold uppercase tracking-[0.07em] text-muted">{{ $balance['label'] }}</span>
                    <span class="font-display text-[28px] font-semibold leading-tight tabular-nums">{{ $balance['left'] }}<span class="text-sm font-normal text-muted"> / {{ $balance['max'] }}</span></span>
                    <span class="text-xs text-muted">Working days remaining</span>
                </div>
            @endforeach
            <div class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3">
                <span class="text-[11.5px] font-semibold uppercase tracking-[0.07em] text-muted">Open requests</span>
                <span class="font-display text-[28px] font-semibold leading-tight tabular-nums">{{ $awaiting }}</span>
                <span class="text-xs text-muted">Waiting for a decision</span>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {!! $card('Next leave', $nextLeave
                ? '<p class="font-display text-lg font-semibold">'.$d($nextLeave->start_date).' to '.$d($nextLeave->end_date).'</p><p class="mt-1 text-xs text-muted">'.e($nextLeave->leaveType?->name ?? $nextLeave->type_code).' · '.$nextLeave->days.' working days</p>'
                : '<p class="py-6 text-center text-xs text-muted">No approved leave coming up.</p>') !!}

            @php
                $leaveBody = '<div>';
                foreach ($myLeave as $leave) {
                    $tone = match (strtolower($leave->status)) {
                        'approved' => 'ok',
                        'pending' => 'warn',
                        'rejected' => 'crit',
                        default => 'plain',
                    };
                    $leaveBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0"><span class="min-w-0 flex-1"><span class="block text-[13px] font-semibold">'.e($leave->leaveType?->name ?? $leave->type_code).'</span><span class="block text-xs text-muted">'.$d($leave->start_date).' to '.$d($leave->end_date).' · '.$leave->days.' days</span></span>'.$pill($leave->status, $tone).'</div>';
                }
                $leaveBody = $myLeave->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">You have not applied for leave yet.</p>' : $leaveBody.'</div>';
            @endphp
            {!! $card('My leave', $leaveBody) !!}

            @php
                $requestBody = '<div>';
                foreach ($myRequests as $request) {
                    $tone = match ($request->status) {
                        'approved' => 'ok',
                        'pending' => 'warn',
                        'returned' => 'info',
                        'rejected' => 'crit',
                        default => 'plain',
                    };
                    $requestBody .= '<a href="'.e(route('approvals.show', $request)).'" class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 hover:bg-paper"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold">'.e($request->subject).'</span><span class="block text-xs text-muted">'.e($request->ref.' · '.$request->created_at?->format('j M Y')).'</span></span>'.$pill($request->status, $tone).'</a>';
                }
                $requestBody = $myRequests->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">You have not raised any requests.</p>' : $requestBody.'</div>';
            @endphp
            {!! $card('My requests', $requestBody) !!}
        </div>

        @php
            $trainingBody = '<div>';
            foreach ($myTrainings as $training) {
                $trainingBody .= '<div class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0"><span class="min-w-0 flex-1"><span class="block truncate text-[13px] font-semibold">'.e($training->title).'</span><span class="block truncate text-xs text-muted">'.e($training->provider.' · '.$d($training->training_date)).'</span></span>'.$pill($training->mandatory ? 'Mandatory' : 'Optional', $training->mandatory ? 'warn' : 'plain').'</div>';
            }
            $trainingBody = $myTrainings->isEmpty() ? '<p class="py-6 text-center text-xs text-muted">No trainings on your record yet.</p>' : $trainingBody.'</div>';
        @endphp
        {!! $card('My trainings', $trainingBody) !!}
    </div>
@endsection
