<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · PlusSave HR</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-paper font-sans text-ink antialiased">
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        <div class="relative h-44 overflow-hidden sm:h-56 lg:h-auto">
            <img src="/images/login-office.jpg" alt="" class="absolute inset-0 h-full w-full object-cover object-center">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-brand-strong/25 via-transparent to-transparent"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-paper to-transparent lg:hidden"></div>
        </div>

        <div class="flex flex-col justify-center px-5 pb-10 sm:px-10 lg:px-12 lg:py-8 xl:px-20">
            <div class="mx-auto w-full max-w-md">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-xl bg-brand text-xl font-black text-white">P</span>
                    <span>
                        <span class="block font-display text-lg font-semibold leading-tight">PlusSave HR</span>
                        <span class="block text-xs uppercase tracking-[0.16em] text-muted">Human resources system</span>
                    </span>
                </div>

                <h1 class="mt-7 font-display text-3xl font-semibold leading-tight xl:text-4xl">
                    Staff records, leave and approvals in one place.
                </h1>
                <p class="mt-3 text-sm leading-relaxed text-muted">
                    Built for PlusSave Financial Solutions. Every change moves through a maker, a checker and the MD before it touches a staff record or payroll.
                </p>
                <ul class="mt-6 space-y-2.5 text-sm text-muted">
                    @foreach (['Employee files and documents', 'Leave balances with working-day checks', 'Maker-checker approvals with a full audit trail'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-brand-soft text-brand">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="m5 12 4 4L19 6"/></svg>
                            </span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-7 rounded-2xl border border-line bg-surface p-6 shadow-sm sm:p-7">
                    <h2 class="font-display text-2xl font-semibold">Sign in</h2>
                    <p class="mt-1 text-sm text-muted">Use the username HR issued to you.</p>

                    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4" novalidate>
                        @csrf
                        <div>
                            <label for="username" class="block text-sm font-medium">Username</label>
                            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" autofocus
                                   class="mt-1.5 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20 {{ $errors->has('username') ? 'border-crit' : 'border-line' }}">
                            @error('username')
                                <p class="mt-1.5 text-xs text-crit">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium">Password</label>
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                   class="mt-1.5 w-full rounded-lg border bg-white px-3 py-2.5 text-sm outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/20 {{ $errors->has('password') ? 'border-crit' : 'border-line' }}">
                            @error('password')
                                <p class="mt-1.5 text-xs text-crit">{{ $message }}</p>
                            @enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm text-muted">
                            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-line text-brand focus:ring-brand/30">
                            Keep me signed in on this device
                        </label>

                        <button type="submit" class="w-full rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-strong focus:outline-none focus:ring-2 focus:ring-brand/30">
                            Sign in
                        </button>
                    </form>

                    <details class="mt-5 text-[11px] text-faint">
                        <summary class="cursor-pointer select-none hover:text-muted">Reviewer access</summary>
                        <p class="mt-2 leading-relaxed">
                            brenda (staff), ruth (HR), daniel (MD), ivan (system admin). Password for all four:
                            <span class="font-medium text-muted">PlusSave#2026</span>.
                        </p>
                    </details>
                </div>

                <p class="mt-4 text-center text-xs text-faint">PlusSave Financial Solutions · Internal use only</p>
            </div>
        </div>
    </div>
</body>
</html>
