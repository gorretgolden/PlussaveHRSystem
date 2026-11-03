@extends('layouts.app')

@section('title', $roleLabel.' workspace')

@section('content')
    @php
        $headline = match ($role) {
            'admin' => 'System administration',
            'finance' => 'Finance workspace',
            'auditor' => 'Auditor workspace',
            default => 'Workspace',
        };
        $intro = match ($role) {
            'admin' => 'Accounts, roles and system controls. Employee records stay with HR.',
            'finance' => 'Payroll periods and posting status. Employee records stay with HR.',
            'auditor' => 'Read-only access to records and the audit trail.',
            default => 'Your workspace.',
        };
        $kpi = function (string $label, string $value, string $desc, ?string $href = null) {
            $inner = '<span class="text-[11.5px] font-semibold uppercase tracking-[0.07em] text-muted">'.e($label).'</span>'
                .'<span class="font-display text-[26px] font-semibold leading-tight tabular-nums">'.e($value).'</span>'
                .'<span class="text-xs text-muted">'.e($desc).'</span>';

            return $href
                ? '<a href="'.e($href).'" class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3 hover:border-brand">'.$inner.'</a>'
                : '<div class="flex flex-col gap-0.5 rounded-xl border border-line bg-surface px-3.5 py-3">'.$inner.'</div>';
        };
        $links = collect([
            ['label' => 'Employee register', 'desc' => 'Search and open staff files', 'route' => 'employees.index', 'show' => $canViewStaff, 'icon' => 'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8'],
            ['label' => 'Roles and permissions', 'desc' => 'Review what each role may do', 'route' => 'admin.roles', 'show' => in_array($role, ['hr', 'md', 'admin'], true), 'icon' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z'],
            ['label' => 'Audit trail', 'desc' => 'Every action, who did it and when', 'route' => 'admin.audit', 'show' => $canAudit, 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
            ['label' => 'Change password', 'desc' => 'Update your sign-in password', 'route' => 'password.edit', 'show' => true, 'icon' => 'M12 15v2M7 10V7a5 5 0 0 1 10 0v3M5 10h14v10H5z'],
        ])->where('show', true);
    @endphp

    <div class="space-y-4">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-faint">{{ $roleLabel }}</p>
            <h1 class="mt-1 font-display text-[26px] font-semibold leading-tight">{{ $headline }}</h1>
            <p class="mt-1 max-w-[70ch] text-sm text-muted">{{ $intro }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4">
            {!! $kpi('Requests I raised', (string) $myRequests->count(), 'Submitted by you', route('approvals.index', ['tab' => 'mine'])) !!}
            @if ($canAudit)
                {!! $kpi('Audit events today', (string) $auditToday, $auditWeek.' in the last 7 days', route('admin.audit')) !!}
            @endif
            @if ($canViewStaff)
                {!! $kpi('People on record', (string) $headcount, 'Active and on leave', route('employees.index')) !!}
            @endif
            @if ($canReports)
                {!! $kpi('Payroll periods', (string) $periods->count(), 'Most recent on record') !!}
            @endif
        </div>

        <section class="rounded-xl border border-line bg-surface p-4">
            <h2 class="font-display text-[15px] font-semibold">Quick access</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($links as $link)
                    <a href="{{ route($link['route']) }}" class="group flex items-start gap-2.5 rounded-lg border border-line bg-paper px-3 py-3 transition hover:border-brand hover:bg-white">
                        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-brand-soft text-brand">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                        </span>
                        <span class="min-w-0">
                            <span class="block text-[13px] font-semibold leading-tight group-hover:text-brand-strong">{{ $link['label'] }}</span>
                            <span class="mt-0.5 block text-xs text-muted">{{ $link['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-3">
            @if ($canAudit)
                <section class="min-w-0 rounded-xl border border-line bg-surface p-4 lg:col-span-2">
                    <div class="mb-3 flex items-center justify-between gap-2">
                        <h2 class="font-display text-[15px] font-semibold">Recent system activity</h2>
                        <a href="{{ route('admin.audit') }}" class="text-[11.5px] font-semibold text-brand hover:underline">Open audit trail</a>
                    </div>
                    <table class="w-full text-left text-[13px]">
                        <thead class="text-[11px] uppercase tracking-wide text-muted">
                            <tr class="border-b border-line">
                                <th class="py-2 font-semibold">Time</th>
                                <th class="py-2 font-semibold">User</th>
                                <th class="py-2 font-semibold">Action</th>
                                <th class="hidden py-2 font-semibold sm:table-cell">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentAudit as $entry)
                                <tr class="border-b border-line last:border-0">
                                    <td class="whitespace-nowrap py-2 text-muted">{{ $entry->created_at?->format('j M, H:i') }}</td>
                                    <td class="whitespace-nowrap py-2">{{ $entry->actor }} <span class="text-[11px] text-faint">{{ $entry->actor_role }}</span></td>
                                    <td class="py-2">{{ $entry->action }}</td>
                                    <td class="hidden py-2 text-muted sm:table-cell">{{ $entry->ref ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-xs text-muted">No activity recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>
            @endif

            <section class="min-w-0 rounded-xl border border-line bg-surface p-4">
                <h2 class="font-display text-[15px] font-semibold">My account</h2>
                <dl class="mt-3 space-y-2 text-[13px]">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Name</dt>
                        <dd class="text-right font-medium">{{ auth()->user()->name }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Role</dt>
                        <dd class="text-right font-medium">{{ $roleLabel }}</dd>
                    </div>
                    @if ($employee)
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-muted">Staff number</dt>
                            <dd class="text-right font-medium">{{ $employee->id }}</dd>
                        </div>
                    @endif
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-muted">Last sign in</dt>
                        <dd class="text-right font-medium">{{ auth()->user()->last_login_at?->format('j M Y, H:i') ?? 'This session' }}</dd>
                    </div>
                </dl>
                <p class="mt-3 rounded-lg bg-paper px-3 py-2 text-[11.5px] leading-snug text-muted">
                    Your access is limited to what this role is granted. Changes to roles are logged.
                </p>
            </section>
        </div>

        @if ($canReports && $periods->isNotEmpty())
            <section class="min-w-0 rounded-xl border border-line bg-surface p-4">
                <h2 class="font-display text-[15px] font-semibold">Payroll periods</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="w-full min-w-[420px] text-left text-[13px]">
                        <thead class="text-[11px] uppercase tracking-wide text-muted">
                            <tr class="border-b border-line">
                                <th class="py-2 font-semibold">Period</th>
                                <th class="py-2 text-right font-semibold">Gross (UGX)</th>
                                <th class="py-2 text-right font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($periods as $period)
                                <tr class="border-b border-line last:border-0">
                                    <td class="py-2">{{ \Carbon\Carbon::createFromFormat('Y-m', $period->period)->format('M Y') }}</td>
                                    <td class="py-2 text-right tabular-nums">{{ number_format($periodGross[$period->id] ?? (float) $period->gross) }}</td>
                                    <td class="py-2 text-right">
                                        <span class="rounded-full px-2 py-0.5 text-[10.5px] font-semibold {{ $period->status === 'Posted' ? 'bg-ok-soft text-ok' : 'bg-warn-soft text-warn' }}">{{ $period->status }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <section class="min-w-0 rounded-xl border border-line bg-surface p-4">
                <h2 class="font-display text-[15px] font-semibold">Requests I raised</h2>
                <div class="mt-2">
                    @forelse ($myRequests as $request)
                        <a href="{{ route('approvals.show', $request) }}" class="flex items-center gap-2.5 border-b border-line py-2.5 last:border-0 hover:bg-paper">
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[13px] font-semibold leading-tight">{{ $request->subject }}</span>
                                <span class="block truncate text-xs text-muted">{{ $request->ref }} · {{ $request->kind }}</span>
                            </span>
                            <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[10.5px] font-semibold {{ $request->status === 'approved' ? 'bg-ok-soft text-ok' : ($request->status === 'rejected' ? 'bg-crit-soft text-crit' : ($request->status === 'returned' ? 'bg-info-soft text-info' : 'bg-warn-soft text-warn')) }}">{{ ucfirst($request->status) }}</span>
                        </a>
                    @empty
                        <p class="py-6 text-center text-xs text-muted">You have not raised any requests.</p>
                    @endforelse
                </div>
            </section>

            <section class="min-w-0 rounded-xl border border-line bg-surface p-4">
                <h2 class="font-display text-[15px] font-semibold">What this role may do</h2>
                <div class="mt-3 space-y-2.5">
                    @foreach ($myPermissions as $module => $items)
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.07em] text-muted">{{ $module }}</p>
                            <p class="mt-0.5 text-[12.5px] leading-snug">{{ $items->pluck('label')->implode(', ') }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
@endsection