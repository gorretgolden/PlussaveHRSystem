@extends('layouts.app')

@section('title', $hr->ref)
@section('heading', $hr->ref)

@section('content')
    @php
        $user = auth()->user();
        $names = \App\Models\Employee::all()->mapWithKeys(fn ($e) => [$e->id => $e->full_name]);
        $statusStyle = fn ($s) => match ($s) {
            'approved' => 'bg-ok-soft text-ok',
            'pending' => 'bg-warn-soft text-warn',
            'returned' => 'bg-info-soft text-info',
            'rejected' => 'bg-crit-soft text-crit',
            default => 'bg-paper text-muted',
        };
        $stageLabel = fn ($role) => $role === 'md' ? 'Managing Director' : ($role === 'hr' ? 'HR Manager' : 'Staff');
        $isMaker = $hr->maker === $user->employee_id;
        $returnAction = $hr->status === 'returned'
            ? $hr->actions->reverse()->first(fn ($a) => str_contains(strtolower($a->action), 'return'))
            : null;
    @endphp

    <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-brand">
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to approvals
    </a>

    <div class="mt-3 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="rounded-xl border border-line bg-surface p-5">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line pb-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">{{ $hr->kind }}</p>
                        <h1 class="mt-1 font-display text-xl font-semibold leading-snug">{{ $hr->subject }}</h1>
                        <p class="mt-1 text-xs text-muted">{{ $hr->ref }} · raised by {{ $names[$hr->maker] ?? $hr->maker }} on {{ $hr->created_at->format('j M Y, H:i') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statusStyle($hr->status) }}">{{ ucfirst($hr->status) }}</span>
                </div>

                <dl class="mt-4 divide-y divide-line">
                    @foreach (($hr->fields ?? []) as [$label, $value])
                        <div class="grid gap-1 py-2.5 sm:grid-cols-[200px_minmax(0,1fr)] sm:gap-4">
                            <dt class="text-xs font-medium text-muted">{{ $label }}</dt>
                            <dd class="text-sm">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Approval trail</h2>
                <ol class="mt-4 space-y-4">
                    @foreach ($hr->actions as $action)
                        <li class="flex gap-3">
                            <span class="mt-1 size-2 shrink-0 rounded-full {{ str_contains(strtolower($action->action), 'reject') ? 'bg-crit' : (str_contains(strtolower($action->action), 'return') ? 'bg-warn' : 'bg-brand') }}"></span>
                            <div class="min-w-0 flex-1 border-b border-line pb-3 last:border-0 last:pb-0">
                                <p class="text-sm font-medium">{{ $action->action }}</p>
                                <p class="text-[11px] text-muted">{{ $names[$action->actor] ?? $action->actor }} · {{ $stageLabel($action->actor_role) }} · {{ $action->acted_at?->format('j M Y, H:i') }}</p>
                                @if ($action->comment)
                                    <p class="mt-1.5 rounded-md bg-paper px-2.5 py-1.5 text-xs text-ink">"{{ $action->comment }}"</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            @if ($canAct)
                <section class="rounded-xl border border-brand/30 bg-surface p-5">
                    <h2 class="font-display text-base font-semibold">Your decision</h2>
                    <p class="mt-0.5 text-xs text-muted">
                        @if ($stageRole === 'hr')
                            Check the balance and cover, then verify the request and forward it to the MD.
                        @elseif ($hr->kind === 'Role permission change')
                            Approving applies the new access to this role straight away.
                        @else
                            Approving applies the change to the staff record straight away.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('approvals.act', $hr) }}" class="mt-3">
                        @csrf
                        <label for="comment" class="block text-sm font-medium">Comment <span class="font-normal text-muted">(required when returning or rejecting)</span></label>
                        <textarea id="comment" name="comment" rows="3" maxlength="600" class="mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">{{ old('comment') }}</textarea>
                        @error('comment')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                        @error('action')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror

                        <div class="mt-3 flex flex-wrap gap-2">
                            @if ($stageRole === 'hr')
                                <button name="action" value="forward" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Verify and forward to MD</button>
                            @else
                                <button name="action" value="approve" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Approve</button>
                            @endif
                            <button name="action" value="return" class="rounded-lg border border-line px-4 py-2 text-sm font-medium text-muted hover:bg-paper">{{ $stageRole === 'md' && count($hr->chain) > 1 ? 'Return to HR' : 'Return to maker' }}</button>
                            <button name="action" value="reject" class="rounded-lg border border-crit/30 px-4 py-2 text-sm font-medium text-crit hover:bg-crit-soft">Reject</button>
                        </div>
                    </form>
                </section>
            @elseif ($hr->status === 'returned' && $isMaker)
                <section class="rounded-xl border border-info/30 bg-surface p-5">
                    <h2 class="font-display text-base font-semibold">Fix and resubmit</h2>
                    <p class="mt-0.5 text-xs text-muted">This came back to you. Make the correction, then resubmit it into the same approval chain.</p>
                    <form method="POST" action="{{ route('approvals.resubmit', $hr) }}" class="mt-3">
                        @csrf
                        <label for="resubmit-comment" class="block text-sm font-medium">What did you change?</label>
                        <textarea id="resubmit-comment" name="comment" rows="3" maxlength="600" required class="mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20"></textarea>
                        @error('comment')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                        <button class="mt-3 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Resubmit</button>
                    </form>
                </section>
            @elseif ($hr->status === 'pending')
                <div class="rounded-xl border border-line bg-paper px-4 py-3 text-sm text-muted">
                    @if ($isMaker)
                        This is your request. It is waiting with {{ $stageLabel($hr->chain[$hr->stage] ?? 'md') }} and you cannot approve your own work.
                    @elseif ($stageRole === $user->role)
                        Your role does not hold the {{ $stageRole === 'md' ? 'Approve' : 'Verify' }} privilege at this stage, so this request cannot move.
                    @else
                        This request is at {{ $stageLabel($hr->chain[$hr->stage] ?? 'md') }} stage, so it is not yours to act on yet.
                    @endif
                </div>
            @endif
        </div>

        <aside class="space-y-5">
            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Progress</h2>
                <ol class="mt-3 space-y-3 text-xs">
                    <li class="flex gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-brand text-[10px] font-bold text-white">1</span>
                        <span><span class="block font-semibold">Submitted by {{ $names[$hr->maker] ?? $hr->maker }}</span><span class="text-muted">{{ $stageLabel($hr->maker_role) }} · {{ $hr->created_at->format('j M, H:i') }}</span></span>
                    </li>
                    @foreach ($hr->chain as $i => $role)
                        @php
                            $done = $hr->status === 'approved' || $i < $hr->stage;
                            $current = $hr->status === 'pending' && $i === $hr->stage;
                            $returnedHere = $returnAction && $returnAction->actor_role === $role;
                        @endphp
                        <li class="flex gap-2.5">
                            <span class="grid size-5 shrink-0 place-items-center rounded-full text-[10px] font-bold {{ $done ? 'bg-brand text-white' : ($returnedHere ? 'bg-warn-soft text-warn ring-1 ring-warn/30' : ($current ? 'bg-accent-soft text-accent ring-1 ring-accent/40' : 'bg-paper text-faint')) }}">{{ $i + 2 }}</span>
                            <span>
                                <span class="block font-semibold {{ $current ? 'text-accent' : 'text-ink' }}">{{ $stageLabel($role) }}</span>
                                <span class="text-muted">{{ $done ? 'Done' : ($returnedHere ? 'Returned it for correction' : ($current ? 'Waiting now' : 'Not reached')) }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>

            @if ($hr->target_employee)
                @php $target = \App\Models\Employee::find($hr->target_employee); @endphp
                @if ($target)
                    @php $canOpenFile = $user->can('view-employee-file'); @endphp
                    <section class="rounded-xl border border-line bg-surface p-5">
                        <h2 class="font-display text-base font-semibold">Employee</h2>
                        <{{ $canOpenFile ? 'a' : 'div' }} @if ($canOpenFile) href="{{ route('employees.show', $target) }}" @endif class="mt-3 flex items-center gap-3 rounded-lg{{ $canOpenFile ? ' hover:bg-paper' : '' }}">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-soft text-xs font-bold text-brand">{{ $target->initials }}</span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium">{{ $target->full_name }}</span>
                                <span class="block truncate text-[11px] text-muted">{{ $target->id }} · {{ $target->title }}</span>
                            </span>
                        </{{ $canOpenFile ? 'a' : 'div' }}>
                    </section>
                @endif
            @endif
        </aside>
    </div>
@endsection
