<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PlusSave HR') · PlusSave HR</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-paper font-sans text-ink antialiased">
    @php
        $user = auth()->user();
        $roleLabel = \App\Support\PermissionCatalog::roleLabel($user->role);
        $isReviewer = in_array($user->role, ['hr', 'md'], true);
        $awaiting = $isReviewer
            ? \App\Models\HrRequest::where('status', 'pending')->where('maker', '!=', $user->employee_id)->get()
                ->filter(fn ($r) => ($r->chain[$r->stage] ?? null) === $user->role)->count()
            : 0;
        $notes = $user->employee_id
            ? \App\Models\HrNotification::where('employee_id', $user->employee_id)->orderByDesc('created_at')->limit(8)->get()
            : collect();
        $unread = $notes->where('is_read', false)->count();
        $isStaff = $user->role === 'staff';
        $overview = collect([
            ['route' => 'dashboard', 'match' => 'dashboard', 'label' => $isStaff ? 'My dashboard' : 'Dashboard', 'icon' => 'M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z', 'show' => true, 'badge' => null],
            ['route' => 'approvals.index', 'match' => 'approvals.*', 'label' => $isReviewer ? 'Approvals' : 'My requests', 'icon' => 'M3 13h5l2 3h4l2-3h5M5 5h14l2 8v6H3v-6z', 'show' => true, 'badge' => $awaiting],
        ])->where('show', true);
        $people = collect([
            ['route' => 'employees.index', 'match' => 'employees.*', 'label' => 'Employees', 'icon' => 'M16 20v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8', 'show' => $user->can('view-employee-file'), 'badge' => null],
            ['route' => 'leave.index', 'match' => 'leave.*', 'label' => 'My leave', 'icon' => 'M8 3v4M16 3v4M3 9h18M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2z', 'show' => $user->can('apply-leave') || $user->can('leave-approval'), 'badge' => null],
        ])->where('show', true);
        $administration = collect([
            ['route' => 'admin.roles', 'match' => 'admin.roles*', 'label' => 'Roles and permissions', 'icon' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z', 'show' => in_array($user->role, ['hr', 'md', 'admin'], true), 'badge' => null],
            ['route' => 'admin.audit', 'match' => 'admin.audit*', 'label' => 'Audit trail', 'icon' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01', 'show' => $user->can('audit-trail'), 'badge' => null],
        ])->where('show', true);
        $groups = collect([
            ['label' => $isStaff ? 'My workspace' : 'Overview', 'links' => $overview],
            ['label' => 'People', 'links' => $people],
        ])->filter(fn ($group) => $group['links']->isNotEmpty());
        if ($administration->isNotEmpty()) {
            $groups->push(['label' => 'Administration', 'links' => $administration]);
        }
    @endphp

    <div class="lg:grid lg:h-full lg:grid-cols-[250px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">
        <aside class="hidden bg-rail px-3 py-4 lg:flex lg:h-full lg:flex-col">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 border-b border-white/10 px-2 pb-4">
                <span class="grid size-10 place-items-center rounded-xl bg-white text-lg font-black text-brand">P</span>
                <span>
                    <span class="block font-display text-base font-semibold leading-tight text-white">PlusSave HR</span>
                    <span class="block text-[11px] font-medium uppercase tracking-[0.14em] text-rail-muted">People system</span>
                </span>
            </a>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @foreach ($groups as $group)
                    <p class="mt-5 px-3 text-[10px] font-semibold uppercase tracking-[0.14em] text-rail-muted">{{ $group['label'] }}</p>
                    <nav class="mt-1 space-y-0.5">
                        @foreach ($group['links'] as $link)
                            @php $active = request()->routeIs($link['match']); @endphp
                            <a href="{{ route($link['route']) }}"
                               class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-[13.5px] font-medium transition {{ $active ? 'bg-rail-active font-semibold text-white' : 'text-rail-ink hover:bg-white/5' }}">
                                <svg class="size-4 shrink-0 opacity-85" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $link['icon'] }}"/></svg>
                                <span class="flex-1">{{ $link['label'] }}</span>
                                @if ($link['badge'])
                                    <span class="rounded-full bg-accent px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $link['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                @endforeach
            </div>

            <div class="mt-4 rounded-xl bg-white/5 p-3">
                <div class="flex items-center gap-2.5">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent-soft text-xs font-bold text-accent">{{ $user->employee?->initials ?? 'PS' }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-white">{{ $user->name }}</span>
                        <span class="block truncate text-[11px] text-rail-muted">{{ $roleLabel }}</span>
                    </span>
                </div>
                <a href="{{ route('password.edit') }}" class="mt-3 block text-[11px] font-medium text-rail-muted hover:text-white">Change password</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="w-full rounded-md border border-white/15 px-3 py-1.5 text-xs font-medium text-rail-ink hover:bg-white/10">Sign out</button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-0 min-w-0 flex-col lg:h-full">
            <header class="border-b border-line bg-surface">
                <div class="flex flex-wrap items-center gap-2.5 px-4 py-2.5 sm:px-6">
                    <a href="{{ route('dashboard') }}" class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand text-base font-black text-white lg:hidden">P</a>

                    @can('view-employee-file')
                        <form method="GET" action="{{ route('employees.index') }}" class="order-3 min-w-0 flex-1 basis-full sm:order-none sm:basis-[240px]">
                            <label class="flex items-center gap-2 rounded-lg border border-line bg-paper px-3 py-1.5 focus-within:border-brand">
                                <svg class="size-4 shrink-0 text-faint" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                                <input type="search" name="q" value="{{ request()->routeIs('employees.index') && is_string(request('q')) ? request('q') : '' }}" placeholder="Search staff by name, ID, title or branch"
                                       class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm outline-none placeholder:text-faint">
                            </label>
                        </form>
                    @else
                        <div class="order-3 basis-full sm:order-none sm:flex-1"></div>
                    @endcan

                    <details class="relative order-2 ml-auto sm:order-none sm:ml-0">
                        <summary class="relative flex cursor-pointer list-none items-center rounded-lg border border-line p-2 text-muted hover:text-ink [&::-webkit-details-marker]:hidden" aria-label="Notifications">
                            <svg class="size-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 9a6 6 0 1 0-12 0c0 6-2 7-2 7h16s-2-1-2-7M10.3 20a2 2 0 0 0 3.4 0"/></svg>
                            @if ($unread)
                                <span class="absolute -right-1.5 -top-1.5 min-w-4 rounded-full bg-crit px-1 text-center text-[10px] font-bold leading-4 text-white">{{ $unread }}</span>
                            @endif
                        </summary>
                        <div class="fixed inset-x-3 top-16 z-50 max-h-[70vh] overflow-y-auto rounded-xl border border-line bg-surface shadow-lg sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:max-h-none sm:w-80 sm:overflow-hidden">
                            <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-2.5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Notifications</p>
                                @if ($unread)
                                    <form method="POST" action="{{ route('notifications.read') }}">
                                        @csrf
                                        <button class="text-[11px] font-semibold text-brand hover:underline">Mark all as read</button>
                                    </form>
                                @endif
                            </div>
                            @forelse ($notes as $note)
                                <div class="flex items-start gap-2 border-b border-line px-4 py-2.5 last:border-0">
                                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $note->is_read ? 'bg-line' : 'bg-accent' }}"></span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[13px] leading-snug">{{ $note->text }}</span>
                                        <span class="block text-[11px] text-faint">{{ $note->created_at?->diffForHumans() }}</span>
                                    </span>
                                </div>
                            @empty
                                <p class="px-4 py-6 text-center text-xs text-muted">No notifications.</p>
                            @endforelse
                        </div>
                    </details>

                    <div class="order-2 flex items-center gap-2 sm:order-none">
                        <span class="grid size-9 place-items-center rounded-full bg-brand-soft text-xs font-bold text-brand">{{ $user->employee?->initials ?? 'PS' }}</span>
                        <span class="hidden min-w-0 sm:block">
                            <span class="block truncate text-[13px] font-semibold leading-tight">{{ $user->name }}</span>
                            <span class="block truncate text-[11.5px] text-muted">{{ $roleLabel }}</span>
                        </span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="order-2 sm:order-none lg:hidden">
                        @csrf
                        <button class="rounded-md border border-line px-2.5 py-1.5 text-xs font-medium text-muted">Sign out</button>
                    </form>
                </div>

                <nav class="flex flex-wrap gap-1 border-t border-line px-2 py-1.5 lg:hidden">
                    @foreach ($groups as $group)
                        @foreach ($group['links'] as $link)
                            <a href="{{ route($link['route']) }}"
                               class="whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-medium {{ request()->routeIs($link['match']) ? 'bg-brand-soft text-brand' : 'text-muted' }}">{{ $link['label'] }}</a>
                        @endforeach
                    @endforeach
                </nav>
            </header>

            <main class="min-h-0 flex-1 overflow-y-auto">
                <div class="mx-auto w-full max-w-[1320px] px-4 pb-12 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line py-3.5">
                        <span class="flex items-center gap-2.5">
                            <span class="grid size-9 place-items-center rounded-lg bg-brand text-sm font-black text-white">P</span>
                            <span class="flex flex-col leading-tight">
                                <span class="font-display text-[17px] font-bold text-brand-strong">PlusSave</span>
                                <span class="text-[9px] font-semibold uppercase tracking-[0.16em] text-muted">Financial Solutions</span>
                            </span>
                        </span>
                        <span class="flex flex-wrap items-center gap-2 text-xs text-muted">
                            <span class="hidden sm:inline">Human Resource Management System</span>
                            <span class="rounded-full border border-line bg-surface px-2.5 py-1">{{ now()->format('D, j M Y') }}</span>
                            <span class="rounded-full border border-line bg-surface px-2.5 py-1">Sample data</span>
                        </span>
                    </div>

                    <div class="pt-5">
                        @if (session('status'))
                            <div class="mb-4 flex items-start gap-2 rounded-lg border border-ok/25 bg-ok-soft px-4 py-3 text-sm text-ok">
                                <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m5 12 4 4L19 6"/></svg>
                                <span>{{ session('status') }}</span>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-4 rounded-lg border border-crit/25 bg-crit-soft px-4 py-3 text-sm text-crit">
                                <p class="font-semibold">{{ $errors->count() === 1 ? 'Please fix this' : 'Please fix these' }}</p>
                                <ul class="mt-1 list-inside list-disc">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
