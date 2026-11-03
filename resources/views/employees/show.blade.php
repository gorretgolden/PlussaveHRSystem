@extends('layouts.app')

@section('title', $employee->full_name)
@section('heading', $employee->full_name)

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
        $money = fn ($n) => 'UGX '.number_format((float) $n);
    @endphp

    <a href="{{ route('employees.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-brand">
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to employees
    </a>

    <div class="mt-3 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-5">
            <section class="rounded-xl border border-line bg-surface p-5">
                <div class="flex flex-wrap items-start gap-4">
                    <span class="grid size-16 place-items-center rounded-2xl bg-brand-soft font-display text-xl font-bold text-brand">{{ $employee->initials }}</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="font-display text-2xl font-semibold">{{ $employee->full_name }}</h1>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $statusStyle($employee->status) }}">{{ $employee->status }}</span>
                        </div>
                        <p class="mt-0.5 text-sm text-muted">{{ $employee->title }} · {{ $employee->department?->name }}</p>
                        <p class="mt-1 text-xs text-faint">{{ $employee->id }} · {{ $employee->branch?->name }} · joined {{ $employee->joined->format('j M Y') }}</p>
                    </div>
                </div>

                <dl class="mt-5 grid gap-4 border-t border-line pt-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Phone</dt>
                        <dd class="mt-0.5 text-sm">{{ $employee->phone ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Email</dt>
                        <dd class="mt-0.5 truncate text-sm">{{ $employee->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Address</dt>
                        <dd class="mt-0.5 text-sm">{{ $employee->address ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">National ID</dt>
                        <dd class="mt-0.5 text-sm">{{ $employee->nin ?: '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Next of kin</dt>
                        <dd class="mt-0.5 text-sm">{{ $employee->nok_name ?: '-' }}@if ($employee->nok_relation) <span class="text-muted">({{ $employee->nok_relation }})</span>@endif</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-wide text-muted">Supervisor</dt>
                        <dd class="mt-0.5 text-sm">{{ $employee->supervisor?->full_name ?? '-' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="rounded-xl border border-line bg-surface">
                <div class="flex items-center justify-between border-b border-line px-5 py-3">
                    <h2 class="font-display text-base font-semibold">Leave history</h2>
                    <span class="text-xs text-muted">{{ $leaves->count() }} record{{ $leaves->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="border-b border-line bg-paper text-[11px] uppercase tracking-wide text-muted">
                            <tr>
                                <th class="px-5 py-2 font-semibold">Ref</th>
                                <th class="px-3 py-2 font-semibold">Type</th>
                                <th class="px-3 py-2 font-semibold">Dates</th>
                                <th class="px-3 py-2 text-right font-semibold">Days</th>
                                <th class="px-5 py-2 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse ($leaves as $leave)
                                <tr>
                                    <td class="px-5 py-2.5 font-medium">{{ $leave->ref }}</td>
                                    <td class="px-3 py-2.5 text-muted">{{ $leave->type_code }}</td>
                                    <td class="px-3 py-2.5 text-muted">{{ $leave->start_date->format('j M') }} to {{ $leave->end_date->format('j M Y') }}</td>
                                    <td class="px-3 py-2.5 text-right">{{ (float) $leave->days }}</td>
                                    <td class="px-5 py-2.5"><span class="rounded-full bg-ok-soft px-2 py-0.5 text-[10px] font-semibold text-ok">{{ $leave->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-xs text-muted">No leave has been taken yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-line bg-surface">
                <div class="border-b border-line px-5 py-3">
                    <h2 class="font-display text-base font-semibold">Requests for this employee</h2>
                </div>
                @forelse ($requests as $request)
                    @php $openRequest = in_array(auth()->user()->role, ['hr', 'md'], true) || $request->maker === auth()->user()->employee_id; @endphp
                    <{{ $openRequest ? 'a' : 'div' }} @if ($openRequest) href="{{ route('approvals.show', $request) }}" @endif class="flex items-center justify-between gap-3 border-b border-line px-5 py-2.5 last:border-0{{ $openRequest ? ' hover:bg-paper' : '' }}">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium">{{ $request->subject }}</span>
                            <span class="block text-[11px] text-muted">{{ $request->ref }} · {{ $request->kind }}</span>
                        </span>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ match ($request->status) { 'approved' => 'bg-ok-soft text-ok', 'pending' => 'bg-warn-soft text-warn', 'returned' => 'bg-info-soft text-info', 'rejected' => 'bg-crit-soft text-crit', default => 'bg-paper text-muted' } }}">{{ ucfirst($request->status) }}</span>
                    </{{ $openRequest ? 'a' : 'div' }}>
                @empty
                    <p class="px-5 py-6 text-xs text-muted">No requests have been raised for this employee.</p>
                @endforelse
            </section>
        </div>

        <aside class="space-y-5">
            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Pay and grade</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-muted">Basic salary</dt><dd class="font-medium">{{ $money($employee->basic) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Grade</dt><dd class="font-medium">{{ $employee->grade_code }} · {{ $employee->grade?->name }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Employment</dt><dd class="font-medium">{{ $employee->employment_type }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Contract ends</dt><dd class="font-medium">{{ $employee->contract_end?->format('j M Y') ?? 'Not fixed' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Staff loan</dt><dd class="font-medium">{{ $money($employee->loan_balance) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-muted">Savings</dt><dd class="font-medium">{{ $money($employee->savings) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Leave balances</h2>
                <div class="mt-3 space-y-2.5">
                    @foreach ([['Annual', $employee->annual_balance], ['Sick', $employee->sick_balance], ['Other', $employee->other_balance]] as [$label, $balance])
                        <div>
                            <div class="flex justify-between text-xs"><span class="text-muted">{{ $label }}</span><span class="font-semibold">{{ (float) $balance }} days</span></div>
                            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-paper">
                                <div class="h-full rounded-full bg-brand" style="width: {{ min(100, (float) $balance / 30 * 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Document file</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($documents as $document)
                        <li class="flex items-center gap-2 text-xs">
                            <span class="grid size-4 shrink-0 place-items-center rounded-full {{ $document->present ? 'bg-ok-soft text-ok' : 'bg-crit-soft text-crit' }}">
                                @if ($document->present)
                                    <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="m5 12 4 4L19 6"/></svg>
                                @else
                                    <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                @endif
                            </span>
                            <span class="{{ $document->present ? 'text-ink' : 'text-muted' }}">{{ $document->doc_type }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
@endsection
