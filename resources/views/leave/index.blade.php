@extends('layouts.app')

@section('title', 'Leave')
@section('heading', 'Leave')

@section('content')
    @php
        $statusStyle = fn ($s) => match ($s) {
            'Approved' => 'bg-ok-soft text-ok',
            'pending' => 'bg-warn-soft text-warn',
            'returned' => 'bg-info-soft text-info',
            'rejected' => 'bg-crit-soft text-crit',
            default => 'bg-paper text-muted',
        };
        $balances = [
            ['Annual', $employee?->annual_balance, 21],
            ['Sick', $employee?->sick_balance, 30],
            ['Other', $employee?->other_balance, 6],
        ];
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-semibold">My leave</h1>
            <p class="mt-0.5 text-sm text-muted">Working days are counted from the calendar, skipping weekends and public holidays.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('leave-approval')
                <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-line bg-surface px-4 py-2 text-sm font-semibold text-ink hover:border-brand">
                    Review leave requests
                </a>
            @endcan
            @can('apply-leave')
                <a href="{{ route('leave.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Apply for leave
                </a>
            @endcan
        </div>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        @foreach ($balances as [$label, $balance, $max])
            <div class="rounded-xl border border-line bg-surface p-4">
                <div class="flex items-baseline justify-between">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted">{{ $label }}</p>
                    <p class="font-display text-2xl font-semibold">{{ (float) $balance }}<span class="text-sm font-normal text-muted"> / {{ $max }}</span></p>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-paper">
                    <div class="h-full rounded-full bg-brand" style="width: {{ min(100, (float) $balance / $max * 100) }}%"></div>
                </div>
                <p class="mt-1.5 text-[11px] text-faint">days remaining</p>
            </div>
        @endforeach
    </div>

    <section class="mt-5 rounded-xl border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-5 py-3">
            <h2 class="font-display text-base font-semibold">My applications</h2>
            <span class="text-xs text-muted">{{ $applications->count() }} recent</span>
        </div>
        @forelse ($applications as $application)
            <a href="{{ route('approvals.show', $application) }}" class="flex items-start gap-3 border-b border-line px-5 py-3 last:border-0 hover:bg-paper">
                <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-paper text-[10px] font-bold text-muted">{{ substr($application->ref, 2) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium">{{ $application->subject }}</span>
                    <span class="block text-[11px] text-muted">{{ $application->ref }} · submitted {{ $application->created_at->format('j M Y') }} · stage {{ $application->stage + 1 }} of {{ count($application->chain) }}</span>
                </span>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusStyle($application->status) }}">{{ ucfirst($application->status) }}</span>
            </a>
        @empty
            <div class="px-5 py-10 text-center">
                <p class="text-sm font-medium">You have not applied for leave yet.</p>
                <p class="mt-1 text-xs text-muted">{{ auth()->user()->can('apply-leave') ? 'Submit a request and it will move to HR for checking.' : 'Any request you raise appears here with its progress.' }}</p>
            </div>
        @endforelse
    </section>

    <section class="mt-5 rounded-xl border border-line bg-surface">
        <div class="border-b border-line px-5 py-3">
            <h2 class="font-display text-base font-semibold">Approved leave history</h2>
        </div>
        @if ($requests->isEmpty())
            <p class="px-5 py-8 text-center text-xs text-muted">No approved leave on record.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="border-b border-line bg-paper text-[11px] uppercase tracking-wide text-muted">
                        <tr>
                            <th class="px-5 py-2 font-semibold">Ref</th>
                            <th class="px-3 py-2 font-semibold">Type</th>
                            <th class="px-3 py-2 font-semibold">From</th>
                            <th class="px-3 py-2 font-semibold">To</th>
                            <th class="px-3 py-2 text-right font-semibold">Days</th>
                            <th class="px-5 py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($requests as $request)
                            <tr>
                                <td class="px-5 py-2.5 font-medium">{{ $request->ref }}</td>
                                <td class="px-3 py-2.5 text-muted">{{ $request->leaveType?->name ?? $request->type_code }}</td>
                                <td class="px-3 py-2.5 text-muted">{{ $request->start_date->format('j M Y') }}</td>
                                <td class="px-3 py-2.5 text-muted">{{ $request->end_date->format('j M Y') }}</td>
                                <td class="px-3 py-2.5 text-right">{{ (float) $request->days }}</td>
                                <td class="px-5 py-2.5"><span class="rounded-full bg-ok-soft px-2 py-0.5 text-[10px] font-semibold text-ok">{{ $request->status }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
