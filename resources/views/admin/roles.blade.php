@extends('layouts.app')

@section('title', 'Roles and permissions')
@section('heading', 'Administration')

@section('content')
    @php
        $total = count(\App\Support\PermissionCatalog::names());
        $checkedCount = count($checked);
        $rolePermissions = $roles->mapWithKeys(fn ($r) => [$r['code'] => array_values($r['permissions'])]);
        $pendingRole = $pending?->payload['role'] ?? null;
        $pendingMaker = $pending ? (\App\Models\Employee::find($pending->maker)?->full_name ?? $pending->maker) : null;
        $isApprover = in_array(auth()->user()->role, ['hr', 'md'], true);
        $pendingOpen = $pending && ($isApprover || $pending->maker === auth()->user()->employee_id);
    @endphp

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-muted">Administration</p>
            <h2 class="mt-1 font-display text-xl font-semibold">Roles and permissions</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted">Who can see and do what. One role per person. Changes go to the MD before they apply.</p>
        </div>
        <span class="rounded-full bg-brand-soft px-3 py-1 text-xs font-semibold text-brand">{{ $checkedCount }} of {{ $total }} permissions</span>
    </div>

    @if ($pending)
        <div class="mb-5 rounded-lg border border-warn/25 bg-warn-soft px-4 py-3 text-sm text-warn">
            <p class="font-semibold">{{ $pending->ref }} is waiting for the MD</p>
            <p class="mt-0.5">{{ $pending->subject }}. Submitted {{ $pending->created_at->format('j M Y, H:i') }} by {{ $pendingMaker }}.
                @if ($pendingOpen)
                    <a href="{{ route('approvals.show', $pending) }}" class="font-semibold underline">Open the request</a>
                @endif</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="rounded-xl border border-line bg-surface p-4">
            <h3 class="font-display text-sm font-semibold">Roles</h3>
            <div class="mt-3 flex flex-wrap gap-1.5 lg:flex-col">
                @foreach ($roles as $role)
                    <a href="{{ route('admin.roles', ['role' => $role['code']]) }}"
                       class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm {{ $role['code'] === $selected['code'] ? 'bg-brand-soft font-semibold text-brand' : 'text-ink hover:bg-paper' }}">
                        <span>{{ $role['label'] }}</span>
                        <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-muted">{{ count($role['permissions']) }}</span>
                    </a>
                @endforeach
            </div>
            <p class="mt-4 text-xs leading-relaxed text-muted">{{ $selected['users'] }} {{ Str::plural('person', $selected['users']) }} on this role.
                Built-in roles keep their names so the routes that rely on them stay working.</p>
        </aside>

        <form method="POST" action="{{ route('admin.roles.update', $selected['code']) }}" class="min-w-0" id="roleForm">
            @csrf
            @method('PUT')

            <section class="rounded-xl border border-line bg-surface">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line p-5">
                    <div>
                        <h3 class="font-display text-lg font-semibold">{{ $selected['label'] }}</h3>
                        <p class="mt-0.5 text-sm text-muted">
                            {{ $selected['code'] === 'staff' ? 'Self-service for every employee.' : 'Access for people who hold this role.' }}
                            {{ $selected['permissions'] ? count($selected['permissions']).' permissions granted.' : 'No permissions granted yet.' }}
                        </p>
                    </div>
                    @if ($canEdit)
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.roles', ['role' => $selected['code']]) }}" class="rounded-lg border border-line px-3 py-2 text-sm font-medium text-muted">Discard changes</a>
                            <button type="submit" id="saveRole" class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-strong">Save for MD approval</button>
                        </div>
                    @endif
                </div>

                @if (! $canEdit)
                    <div class="border-b border-line bg-paper px-5 py-3 text-sm text-muted">You can see the access but only HR or the system admin can change it.</div>
                @endif

                <div class="space-y-5 p-5">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <label class="inline-flex items-center gap-2 text-sm font-medium">
                            <input type="checkbox" id="selectAll" {{ $checkedCount === $total ? 'checked' : '' }} class="size-4 rounded border-line text-brand focus:ring-brand">
                            Select all permissions
                        </label>
                        @if ($canEdit)
                            <label class="text-sm font-medium">Copy permission from
                                <select id="copyFrom" class="mt-1 block w-full min-w-[200px] rounded-lg border border-line bg-white px-3 py-2 text-sm font-normal focus:border-brand focus:ring-brand">
                                    <option value="">Choose a role</option>
                                    @foreach ($roles as $role)
                                        @if ($role['code'] !== $selected['code'])
                                            <option value="{{ $role['code'] }}">{{ $role['label'] }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </label>
                        @endif
                    </div>

                    <label class="block text-sm font-medium">Search
                        <input type="search" id="permSearch" placeholder="Filter permissions" class="mt-1 w-full rounded-lg border border-line bg-white px-3 py-2 text-sm font-normal focus:border-brand focus:ring-brand">
                    </label>

                    <div class="space-y-5" id="permGroups">
                        @foreach ($groups as $module => $abilities)
                            <fieldset data-module="{{ $module }}">
                                <legend class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">{{ $module }}</legend>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    @foreach ($abilities as $slug => $label)
                                        <label data-perm="{{ strtolower($label) }}" class="flex items-start gap-2.5 rounded-lg border border-line px-3 py-2 text-sm {{ in_array($slug, $checked, true) ? 'bg-brand-soft/50' : 'bg-white' }}">
                                            <input type="checkbox" name="permissions[]" value="{{ $slug }}" data-perm-box
                                                   {{ in_array($slug, $checked, true) ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}
                                                   class="mt-0.5 size-4 shrink-0 rounded border-line text-brand focus:ring-brand">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                    </div>

                    <div class="border-t border-line pt-5">
                        <h4 class="font-display text-sm font-semibold">Authorisation privileges</h4>
                        <p class="text-xs text-muted">Select all that apply.</p>
                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($allPrivileges as $privilege)
                                <label class="flex items-center gap-2.5 rounded-lg border border-line bg-white px-3 py-2 text-sm">
                                    <input type="checkbox" name="privileges[]" value="{{ $privilege }}" data-priv-box
                                           {{ in_array($privilege, $privileges, true) ? 'checked' : '' }} {{ $canEdit ? '' : 'disabled' }}
                                           class="size-4 rounded border-line text-brand focus:ring-brand">
                                    <span>{{ $privilege }}</span>
                                </label>
                            @endforeach
                            <div class="flex items-center gap-2.5 rounded-lg border border-dashed border-line bg-paper px-3 py-2 text-sm text-muted">
                                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                                <span class="flex-1">Approve own requests</span>
                                <span class="rounded-full bg-paper px-2 py-0.5 text-[10.5px] font-semibold text-muted ring-1 ring-line">Blocked</span>
                            </div>
                        </div>
                    </div>

                    <div id="conflict" class="hidden rounded-lg border border-crit/25 bg-crit-soft px-4 py-3 text-sm text-crit">
                        <p class="font-semibold">Conflict.</p>
                        <p class="mt-0.5">This role can both initiate and approve. One person cannot be maker and checker on the same item. Untick one privilege to save.</p>
                    </div>

                    <div class="rounded-lg border border-info/20 bg-info-soft px-4 py-3 text-sm text-info">
                        No self-approval. A role change is a request: it is logged, sent to the MD, and only applies after the approval.
                    </div>
                </div>
            </section>
        </form>
    </div>

    @if ($history->isNotEmpty())
        <section class="mt-5 rounded-xl border border-line bg-surface p-5">
            <h3 class="font-display text-sm font-semibold">Recent role changes</h3>
            <div class="mt-3 divide-y divide-line">
                @foreach ($history as $change)
                    <div class="flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm">
                        <div class="min-w-0">
                            @if ($isApprover || $change->maker === auth()->user()->employee_id)
                                <a href="{{ route('approvals.show', $change) }}" class="font-medium hover:underline">{{ $change->ref }}</a>
                            @else
                                <span class="font-medium">{{ $change->ref }}</span>
                            @endif
                            <span class="text-muted"> · {{ $change->subject }}</span>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-semibold
                            {{ $change->status === 'approved' ? 'bg-ok-soft text-ok' : ($change->status === 'pending' ? 'bg-warn-soft text-warn' : 'bg-crit-soft text-crit') }}">
                            {{ ucfirst($change->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('roleForm');
            if (!form) return;
            const boxes = [...form.querySelectorAll('[data-perm-box]')];
            const privs = [...form.querySelectorAll('[data-priv-box]')];
            const all = document.getElementById('selectAll');
            const search = document.getElementById('permSearch');
            const copy = document.getElementById('copyFrom');
            const conflict = document.getElementById('conflict');
            const save = document.getElementById('saveRole');
            const rolePermissions = @json($rolePermissions);

            const paint = () => {
                boxes.forEach(box => {
                    box.closest('label').classList.toggle('bg-brand-soft/50', box.checked);
                });
                if (all) all.checked = boxes.length > 0 && boxes.every(box => box.checked);
                const initiate = privs.find(box => box.value === 'Initiate')?.checked;
                const approve = privs.find(box => box.value === 'Approve')?.checked;
                const clash = Boolean(initiate && approve);
                conflict?.classList.toggle('hidden', !clash);
                if (save) save.disabled = clash;
            };

            all?.addEventListener('change', () => {
                boxes.forEach(box => { box.checked = all.checked; });
                paint();
            });

            form.addEventListener('change', event => {
                if (event.target.matches('[data-perm-box], [data-priv-box]')) paint();
            });

            privs.forEach(box => box.addEventListener('change', paint));

            search?.addEventListener('input', () => {
                const term = search.value.trim().toLowerCase();
                document.querySelectorAll('[data-perm]').forEach(label => {
                    label.hidden = term !== '' && !label.dataset.perm.includes(term);
                });
                document.querySelectorAll('[data-module]').forEach(group => {
                    group.hidden = [...group.querySelectorAll('[data-perm]')].every(label => label.hidden);
                });
            });

            copy?.addEventListener('change', () => {
                const picked = rolePermissions[copy.value];
                if (!picked) return;
                boxes.forEach(box => { box.checked = picked.includes(box.value); });
                paint();
                copy.value = '';
            });

            paint();
        })();
    </script>
@endpush
