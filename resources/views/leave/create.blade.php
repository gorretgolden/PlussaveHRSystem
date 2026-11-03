@extends('layouts.app')

@section('title', 'Apply for leave')
@section('heading', 'Apply for leave')

@section('content')
    @php
        $balances = ['Annual' => $employee?->annual_balance, 'Sick' => $employee?->sick_balance, 'Other' => $employee?->other_balance];
        $chain = auth()->user()->role === 'staff' ? 'HR, then the MD' : 'the MD (HR is the maker)';
    @endphp

    <a href="{{ route('leave.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted hover:text-brand">
        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
        Back to leave
    </a>

    <div class="mt-3 grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
        <form method="POST" action="{{ route('leave.store') }}" class="rounded-xl border border-line bg-surface p-5" id="leave-form">
            @csrf

            <div class="flex items-start justify-between gap-3 border-b border-line pb-4">
                <div>
                    <h1 class="font-display text-xl font-semibold">Leave request</h1>
                    <p class="mt-0.5 text-sm text-muted">{{ $employee?->full_name }} · {{ $employee?->id }}</p>
                </div>
                <span class="rounded-full bg-brand-soft px-2.5 py-1 text-[10px] font-semibold text-brand">Goes to {{ $chain }}</span>
            </div>

            <div class="mt-4 space-y-4">
                <div>
                    <label for="type" class="block text-sm font-medium">Leave type</label>
                    <select id="type" name="type" required
                            class="mt-1.5 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 {{ $errors->has('type') ? 'border-crit' : 'border-line' }}">
                        @foreach ($types as $type)
                            <option value="{{ $type->code }}" @selected(old('type') === $type->code)>
                                {{ $type->name }}@if (array_key_exists($type->code, $balances)) · {{ (float) $balances[$type->code] }} days left @endif
                            </option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-xs text-faint" id="type-note">Balances are checked again on the server before the request is saved.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="from" class="block text-sm font-medium">First day</label>
                        <input id="from" name="from" type="date" value="{{ old('from') }}" required min="{{ now()->toDateString() }}"
                               class="mt-1.5 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 {{ $errors->has('from') ? 'border-crit' : 'border-line' }}">
                        @error('from')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="to" class="block text-sm font-medium">Last day</label>
                        <input id="to" name="to" type="date" value="{{ old('to') }}" required min="{{ now()->toDateString() }}"
                               class="mt-1.5 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 {{ $errors->has('to') ? 'border-crit' : 'border-line' }}">
                        @error('to')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="rounded-lg border border-line bg-paper px-3 py-2.5 text-sm">
                    <span class="text-muted">Working days in this range:</span>
                    <span id="working-days" class="font-semibold">pick both dates</span>
                    <p class="mt-0.5 text-[11px] text-faint">Weekends and public holidays are not counted.</p>
                </div>

                <div>
                    <label for="reason" class="block text-sm font-medium">Reason <span class="font-normal text-muted">(optional)</span></label>
                    <textarea id="reason" name="reason" rows="3" maxlength="400" placeholder="Short note for your supervisor and HR"
                              class="mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/20">{{ old('reason') }}</textarea>
                    @error('reason')<p class="mt-1.5 text-xs text-crit">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-line pt-4">
                    <a href="{{ route('leave.index') }}" class="rounded-lg border border-line px-4 py-2 text-sm font-medium text-muted hover:bg-paper">Cancel</a>
                    <button type="submit" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Submit request</button>
                </div>
            </div>
        </form>

        <aside class="space-y-5">
            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Your balances</h2>
                <dl class="mt-3 space-y-2.5 text-sm">
                    @foreach ($balances as $label => $balance)
                        <div class="flex justify-between gap-3"><dt class="text-muted">{{ $label }}</dt><dd class="font-semibold">{{ (float) $balance }} days</dd></div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl border border-line bg-surface p-5">
                <h2 class="font-display text-base font-semibold">Approval path</h2>
                <ol class="mt-3 space-y-3 text-xs">
                    <li class="flex gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-brand text-[10px] font-bold text-white">1</span>
                        <span><span class="block font-semibold text-ink">You submit</span><span class="text-muted">The request is logged with a reference number.</span></span>
                    </li>
                    @if (auth()->user()->role === 'staff')
                        <li class="flex gap-2.5">
                            <span class="grid size-5 shrink-0 place-items-center rounded-full bg-brand-soft text-[10px] font-bold text-brand">2</span>
                            <span><span class="block font-semibold text-ink">HR checks the balance</span><span class="text-muted">Your supervisor covers the desk, then HR forwards it.</span></span>
                        </li>
                    @endif
                    <li class="flex gap-2.5">
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-brand-soft text-[10px] font-bold text-brand">{{ auth()->user()->role === 'staff' ? 3 : 2 }}</span>
                        <span><span class="block font-semibold text-ink">MD approves</span><span class="text-muted">The balance is reduced and the leave is booked.</span></span>
                    </li>
                </ol>
            </section>
        </aside>
    </div>

    <script>
        (function () {
            const holidays = @json($holidays);
            const from = document.getElementById('from');
            const to = document.getElementById('to');
            const out = document.getElementById('working-days');
            const notes = @json($types->mapWithKeys(fn ($t) => [$t->code => $t->note]));

            function count() {
                if (!from.value || !to.value) { out.textContent = 'pick both dates'; return; }
                const a = new Date(from.value + 'T00:00:00');
                const b = new Date(to.value + 'T00:00:00');
                if (b < a) { out.textContent = 'check the dates'; return; }
                if ((b - a) / 86400000 > 366) { out.textContent = 'keep one request within a year'; return; }
                let days = 0;
                const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                for (const d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                    if (![0, 6].includes(d.getDay()) && !holidays.includes(iso(d))) days++;
                }
                out.textContent = days + (days === 1 ? ' day' : ' days');
            }

            from.addEventListener('change', count);
            to.addEventListener('change', count);
            document.getElementById('type').addEventListener('change', function () {
                document.getElementById('type-note').textContent = notes[this.value] || '';
            });
            count();
        })();
    </script>
@endsection
